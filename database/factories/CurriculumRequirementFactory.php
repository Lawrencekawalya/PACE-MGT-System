<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CurriculumRequirement;
use App\Models\Level;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CurriculumRequirement>
 */
class CurriculumRequirementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'level_id' => Level::factory(),
            'course_id' => Course::factory(),
            'is_required' => true,
            'sort_order' => fake()->unique()->numberBetween(1, 9999),
            'is_active' => true,
        ];
    }
}
