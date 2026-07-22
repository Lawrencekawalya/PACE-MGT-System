<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Pace;
use App\Models\PaceAssignment;
use App\Models\StudentCourse;
use App\Models\Term;
use App\Models\User;
use App\PaceAssignmentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaceAssignment>
 */
class PaceAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_course_id' => StudentCourse::factory(),
            'pace_id' => Pace::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'term_id' => Term::factory(),
            'status' => PaceAssignmentStatus::Assigned,
            'attempt_cycle' => 1,
            'assigned_by' => User::factory(),
            'assigned_at' => now(),
            'issued_by' => null,
            'issued_at' => null,
            'started_at' => null,
            'submitted_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
            'reassigned_at' => null,
            'override_reason' => null,
        ];
    }
}
