<?php

use App\InventoryItemType;
use App\Models\InventoryItem;
use App\Models\Pace;
use Database\Seeders\InventoryItemSeeder;

test('inventory seeding creates one booklet item per PACE without duplicates', function () {
    $paces = Pace::withoutEvents(fn () => Pace::factory()->count(2)->create());

    $this->seed(InventoryItemSeeder::class);
    $this->seed(InventoryItemSeeder::class);

    expect(InventoryItem::query()->count())->toBe(2);
    foreach ($paces as $pace) {
        $item = InventoryItem::query()
            ->where('pace_id', $pace->id)
            ->where('item_type', InventoryItemType::PaceBooklet)
            ->sole();

        expect($item->sku)->toBe("PACE-{$pace->number}-{$pace->id}")
            ->and($item->is_consumable)->toBeTrue()
            ->and($item->onHand())->toBe(0);
    }
});
