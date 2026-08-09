<?php

namespace Database\Factories;

use App\InventoryItemType;
use App\Models\InventoryItem;
use App\Models\LearningCenter;
use App\Models\ScoreKeyRequest;
use App\Models\User;
use App\ScoreKeyRequestStatus;
use App\ScoreKeyRequestType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScoreKeyRequest>
 */
class ScoreKeyRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'teacher_id' => User::factory(),
            'learning_center_id' => LearningCenter::factory(),
            'inventory_item_id' => InventoryItem::factory()->state(['item_type' => InventoryItemType::ScoreKey]),
            'request_type' => ScoreKeyRequestType::NewIssue,
            'quantity_requested' => 1,
            'status' => ScoreKeyRequestStatus::Pending,
            'requested_at' => now(),
        ];
    }
}
