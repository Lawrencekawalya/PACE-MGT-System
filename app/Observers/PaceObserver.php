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

        $pace->inventoryItems()->create([
            'item_type' => InventoryItemType::PaceBooklet,
            'sku' => "PACE-{$pace->number}-{$pace->id}",
            'reorder_level' => 0,
            'is_consumable' => true,
            'is_active' => true,
        ]);
    }
}
