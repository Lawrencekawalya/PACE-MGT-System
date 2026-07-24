<?php

namespace Database\Factories;

use App\EnrollmentStatus;
use App\Models\AcademicYear;
use App\Models\LearningCenter;
use App\Models\Level;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Term;
use App\RoleName;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentEnrollment>
 */
class StudentEnrollmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'learning_center_id' => LearningCenter::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'term_id' => fn (array $attributes) => Term::factory()->create([
                'academic_year_id' => $attributes['academic_year_id'],
            ])->id,
            'level_id' => fn (array $attributes) => Level::factory()->create([
                'learning_center_id' => $attributes['learning_center_id'],
            ])->id,
            'status' => EnrollmentStatus::Active,
            'enrolled_on' => now(),
            'decision_by' => null,
            'decision_at' => null,
            'decision_reason' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (StudentEnrollment $enrollment): void {
            if ($enrollment->level->learning_center_id === null) {
                $enrollment->level->update(['learning_center_id' => $enrollment->learning_center_id]);
            } elseif ($enrollment->level->learning_center_id !== $enrollment->learning_center_id) {
                $enrollment->update(['learning_center_id' => $enrollment->level->learning_center_id]);
            }

            $registrar = $enrollment->student->registeredBy;
            if ($registrar?->hasRole(RoleName::Teacher)) {
                $enrollment->fresh()->learningCenter?->teachers()->syncWithoutDetaching([$registrar->id]);
            }
        });
    }
}
