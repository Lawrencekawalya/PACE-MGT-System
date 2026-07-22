<?php

namespace Database\Factories;

use App\AssessmentOutcome;
use App\Models\PaceAttempt;
use App\Models\PaceAttemptCorrection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaceAttemptCorrection>
 */
class PaceAttemptCorrectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pace_attempt_id' => PaceAttempt::factory(),
            'score' => 85,
            'outcome' => AssessmentOutcome::Passed,
            'reason' => fake()->sentence(),
            'corrected_by' => User::factory(),
            'corrected_at' => now(),
        ];
    }
}
