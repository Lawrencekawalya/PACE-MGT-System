<?php

namespace App\Services;

use App\EnrollmentStatus;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\CurriculumRequirement;
use App\Models\Pace;
use App\Models\Student;
use App\Models\StudentCourse;
use App\Models\StudentEnrollment;
use App\Models\Term;
use App\Models\User;
use App\StudentCourseStatus;
use App\StudentStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentEnrollmentService
{
    /** @param array<string, mixed> $data */
    public function save(Student $student, ?StudentEnrollment $enrollment, array $data, User $actor): StudentEnrollment
    {
        if ($student->status !== StudentStatus::Active) {
            throw ValidationException::withMessages(['student' => 'Only active students can be enrolled.']);
        }

        $yearId = $this->numericId($data['academic_year_id'] ?? null, 'academic_year_id');
        $termId = $this->numericId($data['term_id'] ?? null, 'term_id');
        $levelId = $this->numericId($data['level_id'] ?? null, 'level_id');
        $placements = $this->normalizePlacements($data['courses'] ?? null);
        $year = AcademicYear::query()->findOrFail($yearId);
        $term = Term::query()->findOrFail($termId);
        if ($term->academic_year_id !== $year->id) {
            throw ValidationException::withMessages(['term_id' => 'The selected term does not belong to this academic year.']);
        }

        if ($year->is_closed || $term->is_closed) {
            throw ValidationException::withMessages(['academic_year_id' => 'Closed academic periods cannot receive ordinary enrolments.']);
        }

        $enrolledOn = CarbonImmutable::parse(is_string($data['enrolled_on'] ?? null) ? $data['enrolled_on'] : '');
        if ($enrolledOn->lt($term->starts_on) || $enrolledOn->gt($term->ends_on)) {
            throw ValidationException::withMessages(['enrolled_on' => 'The enrolment date must fall within the selected term.']);
        }

        $prescribedIds = CurriculumRequirement::query()
            ->where('level_id', $levelId)
            ->where('is_active', true)
            ->whereHas('course', fn ($query) => $query->where('is_active', true))
            ->pluck('course_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
        $selectedIds = array_values(array_unique(array_column($placements, 'course_id')));
        sort($prescribedIds);
        sort($selectedIds);

        if ($selectedIds !== $prescribedIds && blank($data['curriculum_override_reason'] ?? null)) {
            throw ValidationException::withMessages([
                'curriculum_override_reason' => 'Explain why prescribed courses were added or removed.',
            ]);
        }

        return DB::transaction(function () use ($student, $enrollment, $data, $actor, $prescribedIds, $selectedIds, $placements, $yearId, $termId, $levelId): StudentEnrollment {
            $enrollment ??= new StudentEnrollment;
            $enrollment->student()->associate($student);
            $enrollment->fill([
                'academic_year_id' => $yearId,
                'term_id' => $termId,
                'level_id' => $levelId,
                'status' => $enrollment->exists ? $enrollment->status : EnrollmentStatus::Active,
                'enrolled_on' => $data['enrolled_on'],
            ])->save();

            $enrollment->studentCourses()
                ->whereNotIn('course_id', $selectedIds)
                ->where('status', StudentCourseStatus::Active)
                ->update([
                    'status' => StudentCourseStatus::Withdrawn,
                    'placement_reason' => $data['curriculum_override_reason'] ?? 'Removed from current curriculum placement.',
                ]);

            foreach ($placements as $placement) {
                $course = Course::query()->where('is_active', true)->findOrFail($placement['course_id']);
                $paceId = $placement['starting_pace_id'] ?? null;
                if ($paceId !== null && ! Pace::query()->whereKey($paceId)->where('course_id', $course->id)->exists()) {
                    throw ValidationException::withMessages([
                        'courses' => "The starting PACE for {$course->name} does not belong to that course.",
                    ]);
                }

                $isPrescribed = in_array($course->id, $prescribedIds, true);
                $studentCourse = StudentCourse::query()->firstOrNew([
                    'student_enrollment_id' => $enrollment->id,
                    'course_id' => $course->id,
                ]);
                $studentCourse->fill([
                    'starting_pace_id' => $paceId,
                    'current_pace_id' => $paceId,
                    'status' => StudentCourseStatus::Active,
                    'is_curriculum_required' => $isPrescribed,
                    'placement_reason' => $placement['placement_reason'] ?? ($isPrescribed ? null : $data['curriculum_override_reason']),
                    'assigned_by' => $actor->id,
                ])->save();
            }

            return $enrollment;
        });
    }

    private function numericId(mixed $value, string $field): int
    {
        if ((is_int($value) && $value > 0) || (is_string($value) && ctype_digit($value) && (int) $value > 0)) {
            return (int) $value;
        }

        throw ValidationException::withMessages([$field => 'Select a valid record.']);
    }

    /** @return array<int, array{course_id: int, starting_pace_id: int|null, placement_reason: string|null}> */
    private function normalizePlacements(mixed $value): array
    {
        if (! is_array($value)) {
            throw ValidationException::withMessages(['courses' => 'Select at least one course.']);
        }

        $placements = [];
        foreach ($value as $placement) {
            if (! is_array($placement)) {
                throw ValidationException::withMessages(['courses' => 'A course placement is invalid.']);
            }

            $pace = $placement['starting_pace_id'] ?? null;
            $placements[] = [
                'course_id' => $this->numericId($placement['course_id'] ?? null, 'courses'),
                'starting_pace_id' => blank($pace) ? null : $this->numericId($pace, 'courses'),
                'placement_reason' => is_string($placement['placement_reason'] ?? null) ? $placement['placement_reason'] : null,
            ];
        }

        return $placements;
    }
}
