<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Term>
 */
class TermFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'name' => 'Term '.fake()->numberBetween(1, 3),
            'sort_order' => fake()->unique()->numberBetween(1, 1000),
            'starts_on' => now()->startOfYear(),
            'ends_on' => now()->startOfYear()->addMonths(3),
            'is_active' => false,
            'is_closed' => false,
        ];
    }
}
