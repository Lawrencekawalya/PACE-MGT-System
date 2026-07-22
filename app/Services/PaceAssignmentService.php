<?php

namespace App\Services;

use App\EnrollmentStatus;
use App\Models\AcademicYear;
use App\Models\CurriculumRequirement;
use App\Models\Pace;
use App\Models\PaceAssignment;
use App\Models\StudentCourse;
use App\Models\Term;
use App\Models\User;
use App\PaceAssignmentStatus;
use App\RoleName;
use App\StudentCourseStatus;
use App\StudentStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaceAssignmentService
{
    /** @return Collection<int, Pace> */
    public function sequence(StudentCourse $studentCourse): Collection
    {
        $studentCourse->loadMissing('enrollment');
        $requirement = CurriculumRequirement::query()
            ->where('level_id', $studentCourse->enrollment->level_id)
            ->where('course_id', $studentCourse->course_id)
            ->where('is_active', true)
            ->first();

        if ($requirement !== null && $requirement->paces()->exists()) {
            return $requirement->paces()->where('paces.is_active', true)->get();
        }

        return Pace::query()
            ->where('course_id', $studentCourse->course_id)
            ->where('is_active', true)
            ->orderBy('sequence_order')
            ->orderBy('number')
            ->get();
    }

    public function recommend(StudentCourse $studentCourse): ?Pace
    {
        $activeStatuses = collect(PaceAssignmentStatus::cases())
            ->reject->isTerminal()->map->value;
        if ($studentCourse->paceAssignments()->whereIn('status', $activeStatuses)->exists()) {
            return null;
        }

        $sequence = $this->sequence($studentCourse)->values();
        if ($sequence->isEmpty()) {
            return null;
        }

        $passedIds = $studentCourse->paceAssignments()
            ->where('status', PaceAssignmentStatus::Passed)
            ->pluck('pace_id');
        $lastPassedPosition = $sequence->search(fn (Pace $pace): bool => $passedIds->contains($pace->id));
        foreach ($sequence as $position => $pace) {
            if ($passedIds->contains($pace->id) && ($lastPassedPosition === false || $position > $lastPassedPosition)) {
                $lastPassedPosition = $position;
            }
        }

        if ($lastPassedPosition !== false) {
            return $sequence->get($lastPassedPosition + 1);
        }

        return $sequence->firstWhere('id', $studentCourse->current_pace_id) ?? $sequence->first();
    }

    public function assign(StudentCourse $studentCourse, Pace $pace, User $actor, ?string $overrideReason = null): PaceAssignment
    {
        return DB::transaction(function () use ($studentCourse, $pace, $actor, $overrideReason): PaceAssignment {
            $studentCourse = StudentCourse::query()->lockForUpdate()->findOrFail($studentCourse->id);
            $studentCourse->loadMissing('enrollment.student');
            $this->validatePlacement($studentCourse, $pace);

            $year = AcademicYear::query()->where('is_active', true)->where('is_closed', false)->sole();
            $term = Term::query()->where('academic_year_id', $year->id)->where('is_active', true)->where('is_closed', false)->sole();
            if ($studentCourse->enrollment->academic_year_id !== $year->id) {
                throw ValidationException::withMessages(['student_course_id' => 'The course placement is not in the active academic year.']);
            }

            $activeStatuses = collect(PaceAssignmentStatus::cases())->reject->isTerminal()->map->value;
            $hasActive = PaceAssignment::query()->where('student_course_id', $studentCourse->id)->whereIn('status', $activeStatuses)->exists();
            $recommended = $this->recommend($studentCourse);
            $isOutOfSequence = $recommended === null || $recommended->id !== $pace->id;
            if ($hasActive || $isOutOfSequence) {
                if (! $actor->hasRole(RoleName::Administrator) || blank($overrideReason)) {
                    $message = $hasActive
                        ? 'This course already has an active PACE. An administrator and override reason are required.'
                        : 'Select the recommended next PACE, or ask an administrator to record an override reason.';
                    throw ValidationException::withMessages(['pace_id' => $message]);
                }
            }

            $cycle = (int) PaceAssignment::query()
                ->where('student_course_id', $studentCourse->id)
                ->where('pace_id', $pace->id)
                ->max('attempt_cycle') + 1;
            $assignment = PaceAssignment::query()->create([
                'student_course_id' => $studentCourse->id,
                'pace_id' => $pace->id,
                'academic_year_id' => $year->id,
                'term_id' => $term->id,
                'status' => PaceAssignmentStatus::Assigned,
                'attempt_cycle' => $cycle,
                'assigned_by' => $actor->id,
                'assigned_at' => now(),
                'override_reason' => filled($overrideReason) ? trim($overrideReason) : null,
            ]);
            $studentCourse->update(['current_pace_id' => $pace->id]);
            $this->event($assignment, null, PaceAssignmentStatus::Assigned, $actor, $overrideReason);

            return $assignment;
        }, 3);
    }

    public function transition(PaceAssignment $assignment, PaceAssignmentStatus $to, User $actor, ?string $reason = null): PaceAssignment
    {
        return DB::transaction(function () use ($assignment, $to, $actor, $reason): PaceAssignment {
            $assignment = PaceAssignment::query()->lockForUpdate()->findOrFail($assignment->id);
            $from = $assignment->status;
            if (! in_array($to, $from->allowedNext(), true)) {
                throw ValidationException::withMessages(['status' => "A PACE cannot move from {$from->label()} to {$to->label()}."]);
            }
            if ($to === PaceAssignmentStatus::Cancelled && blank($reason)) {
                throw ValidationException::withMessages(['reason' => 'A cancellation reason is required.']);
            }

            $timestamps = match ($to) {
                PaceAssignmentStatus::InProgress => ['started_at' => $assignment->started_at ?? now()],
                PaceAssignmentStatus::AwaitingSelfTest => ['submitted_at' => now()],
                PaceAssignmentStatus::Passed => ['completed_at' => now()],
                PaceAssignmentStatus::Cancelled => ['cancelled_at' => now()],
                PaceAssignmentStatus::Reassigned => ['reassigned_at' => now()],
                default => [],
            };
            $assignment->update(['status' => $to, ...$timestamps]);
            $this->event($assignment, $from, $to, $actor, $reason);

            if ($to === PaceAssignmentStatus::Passed) {
                $next = $this->recommend($assignment->studentCourse);
                $assignment->studentCourse->update([
                    'current_pace_id' => $next?->id,
                    'status' => $next === null ? StudentCourseStatus::Completed : StudentCourseStatus::Active,
                ]);
            }

            return $assignment->refresh();
        }, 3);
    }

    public function reassign(PaceAssignment $assignment, User $actor, string $reason): PaceAssignment
    {
        return DB::transaction(function () use ($assignment, $actor, $reason): PaceAssignment {
            $assignment = $this->transition($assignment, PaceAssignmentStatus::Reassigned, $actor, $reason);

            return $this->assign($assignment->studentCourse, $assignment->pace, $actor);
        }, 3);
    }

    private function validatePlacement(StudentCourse $studentCourse, Pace $pace): void
    {
        if ($studentCourse->status !== StudentCourseStatus::Active
            || $studentCourse->enrollment->status !== EnrollmentStatus::Active
            || $studentCourse->enrollment->student->status !== StudentStatus::Active) {
            throw ValidationException::withMessages(['student_course_id' => 'Only active student course placements can receive a PACE.']);
        }
        if ($pace->course_id !== $studentCourse->course_id || ! $pace->is_active) {
            throw ValidationException::withMessages(['pace_id' => 'The selected PACE is not active in this course.']);
        }
    }

    private function event(PaceAssignment $assignment, ?PaceAssignmentStatus $from, PaceAssignmentStatus $to, User $actor, ?string $reason): void
    {
        $assignment->statusEvents()->create([
            'from_status' => $from, 'to_status' => $to, 'changed_by' => $actor->id,
            'changed_at' => now(), 'reason' => filled($reason) ? trim($reason) : null,
        ]);
    }
}
