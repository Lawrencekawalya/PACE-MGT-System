<?php

namespace App\Services;

use App\Models\SchoolSetting;
use App\Models\StudentCourse;
use App\Models\StudentEnrollment;
use App\Models\Term;
use App\Models\TuitionClearance;
use App\Models\User;
use App\TuitionClearanceStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TuitionClearanceService
{
    public function __construct(
        private TermPaceTargetService $termTargets,
        private ActivityLogger $activityLogger,
    ) {}

    public function record(
        StudentEnrollment $enrollment,
        Term $term,
        TuitionClearanceStatus $status,
        ?string $reference,
        ?string $notes,
        User $actor,
    ): TuitionClearance {
        return DB::transaction(function () use ($enrollment, $term, $status, $reference, $notes, $actor): TuitionClearance {
            $enrollment = StudentEnrollment::query()->lockForUpdate()->findOrFail($enrollment->id);
            $term = Term::query()->lockForUpdate()->findOrFail($term->id);
            if ($enrollment->academic_year_id !== $term->academic_year_id) {
                throw ValidationException::withMessages([
                    'term_id' => 'The clearance term must belong to the student enrollment academic year.',
                ]);
            }

            $clearance = TuitionClearance::query()
                ->where('student_enrollment_id', $enrollment->id)
                ->where('term_id', $term->id)
                ->lockForUpdate()
                ->first();
            $oldValues = $clearance === null ? [] : [
                'status' => $clearance->status->value,
                'reference' => $clearance->reference,
                'notes' => $clearance->notes,
            ];
            $fromStatus = $clearance?->status;
            $clearance ??= new TuitionClearance([
                'student_enrollment_id' => $enrollment->id,
                'term_id' => $term->id,
            ]);
            $clearance->fill([
                'status' => $status,
                'reference' => filled($reference) ? trim($reference) : null,
                'notes' => filled($notes) ? trim($notes) : null,
                'recorded_by' => $actor->id,
                'recorded_at' => now(),
            ])->save();

            $clearance->events()->create([
                'from_status' => $fromStatus,
                'to_status' => $status,
                'reference' => $clearance->reference,
                'notes' => $clearance->notes,
                'changed_by' => $actor->id,
                'changed_at' => now(),
            ]);
            $this->activityLogger->record(
                $actor,
                'tuition-clearance.recorded',
                $clearance,
                $oldValues,
                [
                    'status' => $status->value,
                    'reference' => $clearance->reference,
                    'notes' => $clearance->notes,
                    'student_enrollment_id' => $enrollment->id,
                    'term_id' => $term->id,
                ],
            );

            return $clearance->load(['recordedBy:id,name', 'events.changedBy:id,name']);
        }, 3);
    }

    /**
     * @return array{
     *     clearance_status: string,
     *     clearance_status_label: string,
     *     completed: int,
     *     target: int,
     *     requires_full_clearance: bool,
     *     additional_pace_allowed: bool
     * }
     */
    public function eligibility(StudentCourse $studentCourse, Term $term): array
    {
        $studentCourse->loadMissing('enrollment');
        $target = SchoolSetting::current()->term_pace_target;
        $progress = $this->termTargets->summarize(
            $studentCourse->paceAssignments()->get(),
            $term,
            $target,
        );
        $status = TuitionClearance::query()
            ->where('student_enrollment_id', $studentCourse->student_enrollment_id)
            ->where('term_id', $term->id)
            ->value('status');
        $clearanceStatus = $status instanceof TuitionClearanceStatus
            ? $status
            : TuitionClearanceStatus::tryFrom((string) $status);
        $clearanceStatus ??= TuitionClearanceStatus::Unconfirmed;
        $requiresFullClearance = $progress['completed'] >= $target;

        return [
            'clearance_status' => $clearanceStatus->value,
            'clearance_status_label' => $clearanceStatus->label(),
            'completed' => $progress['completed'],
            'target' => $target,
            'requires_full_clearance' => $requiresFullClearance,
            'additional_pace_allowed' => ! $requiresFullClearance
                || $clearanceStatus === TuitionClearanceStatus::FullyPaid,
        ];
    }
}
