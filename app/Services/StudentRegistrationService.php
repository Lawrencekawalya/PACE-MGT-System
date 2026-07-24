<?php

namespace App\Services;

use App\EnrollmentStatus;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Term;
use App\Models\User;
use App\RoleName;
use App\StudentStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StudentRegistrationService
{
    /** @param array<string, mixed> $data */
    public function create(array $data, User $actor): Student
    {
        return DB::transaction(function () use ($data, $actor): Student {
            $level = Level::query()->with('learningCenter')->whereKey((int) $data['level_id'])->firstOrFail();
            unset($data['level_id']);

            if ($level->learningCenter === null || ! $level->learningCenter->is_active) {
                throw ValidationException::withMessages([
                    'level_id' => 'The selected grade must belong to an active learning center.',
                ]);
            }

            if ($actor->hasRole(RoleName::Teacher) && ! $actor->hasRole(RoleName::Administrator)
                && ! $level->learningCenter->teachers()->whereKey($actor->id)->exists()) {
                throw ValidationException::withMessages([
                    'level_id' => 'You may only register students in grades managed by your learning centers.',
                ]);
            }

            $years = AcademicYear::query()->where('is_active', true)->get();
            $terms = Term::query()->where('is_active', true)->get();
            if ($years->count() !== 1 || $terms->count() !== 1) {
                throw ValidationException::withMessages([
                    'level_id' => 'Registration requires exactly one active academic year and one active term.',
                ]);
            }

            $year = $years->sole();
            $term = $terms->sole();
            if ($term->academic_year_id !== $year->id) {
                throw ValidationException::withMessages([
                    'level_id' => 'The active term does not belong to the active academic year.',
                ]);
            }
            if (now()->toDateString() < $term->starts_on->toDateString() || now()->toDateString() > $term->ends_on->toDateString()) {
                throw ValidationException::withMessages([
                    'level_id' => 'Today must fall within the active academic term.',
                ]);
            }

            $student = Student::query()->create([
                ...$data,
                'registered_by' => $actor->id,
                'admission_number' => 'pending-'.Str::uuid(),
                'status' => StudentStatus::Active,
            ]);
            $student->update([
                'admission_number' => sprintf('FICA-%s-%06d', now()->format('Y'), $student->id),
            ]);

            StudentEnrollment::query()->create([
                'student_id' => $student->id,
                'learning_center_id' => $level->learning_center_id,
                'academic_year_id' => $year->id,
                'term_id' => $term->id,
                'level_id' => $level->id,
                'status' => EnrollmentStatus::Active,
                'enrolled_on' => now()->toDateString(),
            ]);

            return $student->load('activeEnrollment.learningCenter');
        });
    }
}
