<?php

namespace Database\Factories;

use App\AssessmentOutcome;
use App\AssessmentType;
use App\Models\PaceAssignment;
use App\Models\PaceAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaceAttempt>
 */
class PaceAttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pace_assignment_id' => PaceAssignment::factory(),
            'assessment_type' => AssessmentType::SelfTest,
            'attempt_number' => 1,
            'score' => 80,
            'pass_mark_used' => 80,
            'outcome' => AssessmentOutcome::Passed,
            'notes' => null,
            'recorded_by' => User::factory(),
            'recorded_at' => now(),
            'approved_by' => null,
            'approved_at' => null,
            'approval_reason' => null,
            'finalized_at' => now(),
        ];
    }
}
