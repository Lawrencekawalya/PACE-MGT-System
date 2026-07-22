<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->year(),
            'starts_on' => now()->startOfYear(),
            'ends_on' => now()->endOfYear(),
            'is_active' => false,
            'is_closed' => false,
        ];
    }
}
