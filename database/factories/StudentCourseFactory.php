<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\StudentCourse;
use App\Models\StudentEnrollment;
use App\StudentCourseStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentCourse>
 */
class StudentCourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_enrollment_id' => StudentEnrollment::factory(),
            'course_id' => Course::factory(),
            'starting_pace_id' => null,
            'current_pace_id' => null,
            'status' => StudentCourseStatus::Active,
            'is_curriculum_required' => true,
            'placement_reason' => null,
            'assigned_by' => null,
        ];
    }
}
