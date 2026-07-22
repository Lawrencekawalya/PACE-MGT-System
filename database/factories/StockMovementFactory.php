<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\User;
use App\StockMovementType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inventory_item_id' => InventoryItem::factory(),
            'type' => StockMovementType::Receipt,
            'quantity' => 1,
            'balance_after' => 1,
            'student_id' => null, 'pace_assignment_id' => null,
            'academic_year_id' => null, 'term_id' => null,
            'reference' => fake()->unique()->bothify('DEL-####'), 'reason' => null,
            'recorded_by' => User::factory(), 'recorded_at' => now(), 'corrects_movement_id' => null,
        ];
    }
}
