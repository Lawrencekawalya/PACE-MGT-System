<?php

namespace Database\Factories;

use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoodsReceipt>
 */
class GoodsReceiptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'receipt_number' => fake()->unique()->numerify('GRN-2026-#####'),
            'purchase_order_id' => PurchaseOrder::factory(),
            'delivery_reference' => fake()->bothify('DEL-####'),
            'received_by' => User::factory(),
            'received_at' => now(),
        ];
    }
}
