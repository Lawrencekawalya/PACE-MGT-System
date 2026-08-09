<?php

namespace Database\Factories;

use App\Models\PaceAccountTransaction;
use App\Models\Student;
use App\Models\User;
use App\PaceAccountTransactionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaceAccountTransaction>
 */
class PaceAccountTransactionFactory extends Factory
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
            'type' => PaceAccountTransactionType::Payment,
            'amount' => '50000.00',
            'balance_after' => '50000.00',
            'reference' => fake()->bothify('RCT-####'),
            'recorded_by' => User::factory(),
            'recorded_at' => now(),
        ];
    }
}
