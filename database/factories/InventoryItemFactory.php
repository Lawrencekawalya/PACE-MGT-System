<?php

namespace Database\Factories;

use App\InventoryItemType;
use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pace_id' => null,
            'item_type' => InventoryItemType::ScoreKey,
            'sku' => fake()->unique()->bothify('KEY-####-??'),
            'reorder_level' => 0,
            'is_consumable' => false,
            'is_active' => true,
        ];
    }
}
