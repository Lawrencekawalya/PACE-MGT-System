<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Pace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pace>
 */
class PaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'number' => (string) fake()->unique()->numberBetween(1, 9999),
            'title' => null,
            'edition' => '',
            'sequence_order' => fake()->unique()->numberBetween(1, 9999),
            'is_active' => true,
        ];
    }
}
