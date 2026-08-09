<?php

use App\InventoryItemType;
use App\Models\InventoryItem;
use App\Models\Pace;
use Database\Seeders\InventoryItemSeeder;

test('inventory seeding creates one booklet and one Score Key per PACE without duplicates', function () {
    $paces = Pace::withoutEvents(fn () => Pace::factory()->count(2)->create());

    $this->seed(InventoryItemSeeder::class);
    $this->seed(InventoryItemSeeder::class);

    expect(InventoryItem::query()->count())->toBe(4);
    foreach ($paces as $pace) {
        $item = InventoryItem::query()
            ->where('pace_id', $pace->id)
            ->where('item_type', InventoryItemType::PaceBooklet)
            ->sole();

        expect($item->sku)->toBe("PACE-{$pace->number}-{$pace->id}")
            ->and($item->is_consumable)->toBeTrue()
            ->and($item->onHand())->toBe(0);

        $scoreKey = InventoryItem::query()
            ->where('pace_id', $pace->id)
            ->where('item_type', InventoryItemType::ScoreKey)
            ->sole();

        expect($scoreKey->sku)->toBe($pace->scoreKeySku())
            ->and($scoreKey->is_consumable)->toBeFalse()
            ->and($scoreKey->reorder_level)->toBe(0)
            ->and($scoreKey->target_stock_level)->toBe(0)
            ->and($scoreKey->onHand())->toBe(0);
    }
});

test('creating a PACE automatically creates its booklet and Score Key inventory items', function () {
    $pace = Pace::factory()->create(['number' => 'RR01']);

    expect($pace->inventoryItems()->count())->toBe(2)
        ->and($pace->inventoryItems()->where('item_type', InventoryItemType::PaceBooklet)->exists())->toBeTrue()
        ->and($pace->inventoryItems()->where('item_type', InventoryItemType::ScoreKey)->value('sku'))->toBe($pace->scoreKeySku());
});

test('inventory seeding preserves an existing manually configured Score Key', function () {
    $pace = Pace::withoutEvents(fn () => Pace::factory()->create());
    InventoryItem::factory()->create([
        'pace_id' => $pace->id,
        'item_type' => InventoryItemType::ScoreKey,
        'sku' => 'CUSTOM-SCORE-KEY',
        'reorder_level' => 7,
        'target_stock_level' => 12,
        'is_consumable' => false,
    ]);

    $this->seed(InventoryItemSeeder::class);

    $scoreKey = InventoryItem::query()
        ->where('pace_id', $pace->id)
        ->where('item_type', InventoryItemType::ScoreKey)
        ->sole();

    expect($scoreKey->sku)->toBe('CUSTOM-SCORE-KEY')
        ->and($scoreKey->reorder_level)->toBe(7)
        ->and($scoreKey->target_stock_level)->toBe(12);
});
