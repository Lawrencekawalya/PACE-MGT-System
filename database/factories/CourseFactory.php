<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            'name' => fake()->unique()->words(3, true),
            'code' => fake()->unique()->bothify('CRS-####'),
            'edition' => '',
            'is_pace_course' => true,
            'is_active' => true,
        ];
    }
}
