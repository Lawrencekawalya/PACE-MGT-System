<?php

namespace Database\Factories;

use App\EnrollmentStatus;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Term;
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
            'academic_year_id' => AcademicYear::factory(),
            'term_id' => Term::factory(),
            'level_id' => Level::factory(),
            'status' => EnrollmentStatus::Active,
            'enrolled_on' => now(),
            'decision_by' => null,
            'decision_at' => null,
            'decision_reason' => null,
        ];
    }
}
