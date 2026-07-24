<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\PurchaseOrderSource;
use App\PurchaseOrderStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_number' => fake()->unique()->numerify('PO-2026-#####'),
            'supplier_id' => Supplier::factory(),
            'source' => PurchaseOrderSource::Manual,
            'status' => PurchaseOrderStatus::Draft,
            'created_by' => User::factory(),
        ];
    }
}
