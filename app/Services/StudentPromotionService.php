<?php

namespace App\Services;

use App\EnrollmentStatus;
use App\Models\AcademicYear;
use App\Models\CurriculumRequirement;
use App\Models\Level;
use App\Models\StudentEnrollment;
use App\Models\Term;
use App\Models\User;
use App\RoleName;
use App\StudentStatus;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentPromotionService
{
    public function __construct(private StudentEnrollmentService $enrollments) {}

    /**
     * @param  array{
     *     decision: string,
     *     target_academic_year_id?: int|null,
     *     target_term_id?: int|null,
     *     target_level_id?: int|null,
     *     reason?: string|null
     * }  $data
     */
    public function decide(StudentEnrollment $sourceEnrollment, array $data, User $actor): StudentEnrollment
    {
        if (! $actor->hasRole(RoleName::Administrator)) {
            throw new AuthorizationException('Only an Administrator can manage promotions.');
        }

        return DB::transaction(function () use ($sourceEnrollment, $data, $actor): StudentEnrollment {
            $sourceEnrollment = StudentEnrollment::query()
                ->with(['academicYear', 'level', 'student'])
                ->lockForUpdate()
                ->findOrFail($sourceEnrollment->id);

            if ($sourceEnrollment->status !== EnrollmentStatus::Active || $sourceEnrollment->decision_at !== null) {
                throw ValidationException::withMessages([
                    'decision' => 'This enrollment already has a final decision.',
                ]);
            }

            $decision = EnrollmentStatus::from($data['decision']);

            if (in_array($decision, [EnrollmentStatus::Promoted, EnrollmentStatus::Retained], true)) {
                $this->createNextEnrollment($sourceEnrollment, $decision, $data, $actor);
            }

            $sourceEnrollment->update([
                'status' => $decision,
                'decision_by' => $actor->id,
                'decision_at' => now(),
                'decision_reason' => filled($data['reason'] ?? null) ? trim((string) $data['reason']) : null,
            ]);

            if ($decision === EnrollmentStatus::Transferred) {
                $sourceEnrollment->student->update(['status' => StudentStatus::Withdrawn]);
            } elseif ($decision === EnrollmentStatus::Completed) {
                $sourceEnrollment->student->update(['status' => StudentStatus::Graduated]);
            }

            return $sourceEnrollment->refresh()->load([
                'student', 'academicYear', 'term', 'level.learningCenter',
                'decisionMaker', 'nextEnrollment.academicYear', 'nextEnrollment.term',
                'nextEnrollment.level.learningCenter',
            ]);
        }, 3);
    }

    /**
     * @param  array{
     *     target_academic_year_id?: int|null,
     *     target_term_id?: int|null,
     *     target_level_id?: int|null
     * }  $data
     */
    private function createNextEnrollment(
        StudentEnrollment $sourceEnrollment,
        EnrollmentStatus $decision,
        array $data,
        User $actor,
    ): StudentEnrollment {
        $targetYear = AcademicYear::query()->findOrFail($data['target_academic_year_id'] ?? null);
        $targetTerm = Term::query()->findOrFail($data['target_term_id'] ?? null);
        $targetLevel = Level::query()->with('learningCenter')->findOrFail($data['target_level_id'] ?? null);

        if ($targetYear->is_closed || $targetYear->starts_on->lte($sourceEnrollment->academicYear->starts_on)) {
            throw ValidationException::withMessages([
                'target_academic_year_id' => "Select an open academic year after the student's current year.",
            ]);
        }

        if ($targetTerm->academic_year_id !== $targetYear->id || $targetTerm->is_closed) {
            throw ValidationException::withMessages([
                'target_term_id' => 'Select an open term belonging to the target academic year.',
            ]);
        }

        if (! $targetLevel->is_active || $targetLevel->learningCenter === null || ! $targetLevel->learningCenter->is_active) {
            throw ValidationException::withMessages([
                'target_level_id' => 'Select an active grade assigned to an active learning center.',
            ]);
        }

        if ($decision === EnrollmentStatus::Retained && $targetLevel->id !== $sourceEnrollment->level_id) {
            throw ValidationException::withMessages([
                'target_level_id' => 'A retained student must remain in the same grade.',
            ]);
        }

        if ($decision === EnrollmentStatus::Promoted) {
            $nextLevel = Level::query()
                ->where('is_active', true)
                ->where('sort_order', '>', $sourceEnrollment->level->sort_order)
                ->whereHas('learningCenter', fn ($query) => $query->where('is_active', true))
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first();

            if ($nextLevel === null || $targetLevel->id !== $nextLevel->id) {
                throw ValidationException::withMessages([
                    'target_level_id' => $nextLevel === null
                        ? 'The current grade has no configured next grade. Record programme completion instead.'
                        : "The next configured grade is {$nextLevel->name}.",
                ]);
            }
        }

        if (StudentEnrollment::query()
            ->where('student_id', $sourceEnrollment->student_id)
            ->where('academic_year_id', $targetYear->id)
            ->exists()) {
            throw ValidationException::withMessages([
                'target_academic_year_id' => 'This student already has an enrollment in the target academic year.',
            ]);
        }

        $requirements = CurriculumRequirement::query()
            ->where('level_id', $targetLevel->id)
            ->where('is_active', true)
            ->whereHas('course', fn ($query) => $query->where('is_active', true))
            ->with(['paces' => fn ($query) => $query->where('paces.is_active', true)])
            ->orderBy('sort_order')
            ->get();

        if ($requirements->isEmpty()) {
            throw ValidationException::withMessages([
                'target_level_id' => 'The target grade has no active prescribed curriculum.',
            ]);
        }

        $nextEnrollment = $this->enrollments->save(
            $sourceEnrollment->student,
            null,
            [
                'academic_year_id' => $targetYear->id,
                'term_id' => $targetTerm->id,
                'level_id' => $targetLevel->id,
                'enrolled_on' => $targetTerm->starts_on->toDateString(),
                'curriculum_override_reason' => null,
                'courses' => $requirements->map(fn (CurriculumRequirement $requirement): array => [
                    'course_id' => $requirement->course_id,
                    'starting_pace_id' => $requirement->paces->first()?->id,
                    'placement_reason' => "Created from {$decision->value} year-end decision.",
                ])->all(),
            ],
            $actor,
        );
        $nextEnrollment->update(['previous_enrollment_id' => $sourceEnrollment->id]);

        return $nextEnrollment;
    }
}
