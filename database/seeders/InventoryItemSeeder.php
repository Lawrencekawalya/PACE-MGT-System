<?php

namespace Database\Seeders;

use App\InventoryItemType;
use App\Models\InventoryItem;
use App\Models\Pace;
use Illuminate\Database\Seeder;

class InventoryItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Pace::query()->with('course:id,code')->select(['id', 'course_id', 'number', 'edition'])->eachById(function (Pace $pace): void {
            InventoryItem::query()->firstOrCreate(
                ['pace_id' => $pace->id, 'item_type' => InventoryItemType::PaceBooklet],
                [
                    'sku' => "PACE-{$pace->number}-{$pace->id}",
                    'reorder_level' => 0,
                    'target_stock_level' => 0,
                    'is_consumable' => true,
                    'is_active' => true,
                ],
            );
            InventoryItem::query()->firstOrCreate(
                ['pace_id' => $pace->id, 'item_type' => InventoryItemType::ScoreKey],
                [
                    'sku' => $pace->scoreKeySku(),
                    'reorder_level' => 0,
                    'target_stock_level' => 0,
                    'is_consumable' => false,
                    'is_active' => true,
                ],
            );
        });
    }
}
