<?php

namespace App\Services;

use App\Models\PaceAssignment;
use App\Models\Term;
use App\PaceAssignmentStatus;
use Illuminate\Support\Collection;

class TermPaceTargetService
{
    /**
     * @param  Collection<int, PaceAssignment>  $assignments
     * @return array{
     *     term_id: int,
     *     term: string,
     *     completed: int,
     *     target: int,
     *     remaining: int,
     *     exceeded_by: int,
     *     expected_by_now: int,
     *     progress_percent: float,
     *     status: string,
     *     status_label: string
     * }
     */
    public function summarize(Collection $assignments, Term $term, int $target): array
    {
        $completed = $assignments
            ->filter(fn (PaceAssignment $assignment): bool => $assignment->status === PaceAssignmentStatus::Passed
                && $assignment->completed_at !== null
                && $assignment->completed_at->betweenIncluded(
                    $term->starts_on->copy()->startOfDay(),
                    $term->ends_on->copy()->endOfDay(),
                ))
            ->pluck('pace_id')
            ->unique()
            ->count();
        $expectedByNow = $this->expectedByNow($term, $target);
        $status = match (true) {
            $completed >= $target => 'target_achieved',
            $completed >= $expectedByNow => 'on_track',
            default => 'below_target',
        };

        return [
            'term_id' => $term->id,
            'term' => $term->name,
            'completed' => $completed,
            'target' => $target,
            'remaining' => max(0, $target - $completed),
            'exceeded_by' => max(0, $completed - $target),
            'expected_by_now' => $expectedByNow,
            'progress_percent' => round(min(100, ($completed / $target) * 100), 1),
            'status' => $status,
            'status_label' => match ($status) {
                'target_achieved' => 'Target achieved',
                'on_track' => 'On track',
                default => 'Below target',
            },
        ];
    }

    private function expectedByNow(Term $term, int $target): int
    {
        $startsOn = $term->starts_on->copy()->startOfDay();
        $endsOn = $term->ends_on->copy()->endOfDay();
        $today = now();

        if ($today->lessThanOrEqualTo($startsOn)) {
            return 0;
        }

        if ($today->greaterThanOrEqualTo($endsOn)) {
            return $target;
        }

        $termSeconds = max(1, $startsOn->diffInSeconds($endsOn));
        $elapsedSeconds = $startsOn->diffInSeconds($today);

        return min($target, (int) floor($target * ($elapsedSeconds / $termSeconds)));
    }
}
