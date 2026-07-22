<?php

namespace Database\Factories;

use App\AssessmentType;
use App\Models\PaceAssignment;
use App\Models\PaceRetryApproval;
use App\Models\User;
use App\RetryApprovalStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaceRetryApproval>
 */
class PaceRetryApprovalFactory extends Factory
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
            'attempt_number' => 2,
            'status' => RetryApprovalStatus::Pending,
            'is_over_limit' => false,
            'requested_by' => User::factory(),
            'requested_at' => now(),
            'request_reason' => fake()->sentence(),
            'decided_by' => null,
            'decided_at' => null,
            'decision_reason' => null,
        ];
    }
}
