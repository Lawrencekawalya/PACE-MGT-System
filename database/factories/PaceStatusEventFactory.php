<?php

namespace Database\Factories;

use App\Models\PaceAssignment;
use App\Models\PaceStatusEvent;
use App\Models\User;
use App\PaceAssignmentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaceStatusEvent>
 */
class PaceStatusEventFactory extends Factory
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
            'from_status' => null,
            'to_status' => PaceAssignmentStatus::Assigned,
            'changed_by' => User::factory(),
            'changed_at' => now(),
            'reason' => null,
        ];
    }
}
