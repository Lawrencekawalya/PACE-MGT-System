<?php

namespace App\Observers;

use App\InventoryItemType;
use App\Models\Pace;
use Illuminate\Support\Facades\Schema;

class PaceObserver
{
    public function created(Pace $pace): void
    {
        if (! Schema::hasTable('inventory_items')) {
            return;
        }

        $pace->inventoryItems()->createMany([
            [
                'item_type' => InventoryItemType::PaceBooklet,
                'sku' => "PACE-{$pace->number}-{$pace->id}",
                'reorder_level' => 0,
                'target_stock_level' => 0,
                'is_consumable' => true,
                'is_active' => true,
            ],
            [
                'item_type' => InventoryItemType::ScoreKey,
                'sku' => $pace->scoreKeySku(),
                'reorder_level' => 0,
                'target_stock_level' => 0,
                'is_consumable' => false,
                'is_active' => true,
            ],
        ]);
    }
}
