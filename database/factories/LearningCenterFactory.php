<?php

namespace Database\Factories;

use App\Models\LearningCenter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearningCenter>
 */
class LearningCenterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'code' => fake()->unique()->bothify('LC-###'),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
