<?php

use App\InventoryItemType;
use App\Models\InventoryItem;
use App\Models\Pace;
use App\Models\Permission;
use App\PermissionName;
use App\RoleName;
use App\Services\StockLedgerService;
use App\StockMovementType;
use Database\Seeders\AccessControlSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
});

test('storekeeper can view and adjust inventory while ordinary teacher cannot', function () {
    $item = InventoryItem::factory()->create();
    $storekeeper = createStaffWithRole(RoleName::PaceOfficer);
    $teacher = createStaffWithRole(RoleName::Teacher);
    $movement = ['type' => 'receipt', 'quantity' => 5, 'reference' => 'DEL-200'];

    $this->actingAs($teacher)->get(route('inventory.index'))->assertForbidden();
    $this->actingAs($teacher)->post(route('inventory-items.movements.store', $item), $movement)->assertForbidden();
    $this->actingAs($storekeeper)->get(route('inventory.index'))->assertOk();
    $this->actingAs($storekeeper)->post(route('inventory-items.movements.store', $item), $movement)->assertRedirect();
});

test('teacher with direct inventory permission can post stock movements', function () {
    $teacher = createStaffWithRole(RoleName::Teacher);
    $teacher->directPermissions()->attach(Permission::query()->where('name', PermissionName::AdjustInventory)->sole());
    $item = InventoryItem::factory()->create();

    $this->actingAs($teacher)->post(route('inventory-items.movements.store', $item), [
        'type' => 'adjustment', 'quantity' => 2, 'reason' => 'Opening count.',
    ])->assertRedirect();
});

test('a score key must be linked to one exact PACE', function () {
    $storekeeper = createStaffWithRole(RoleName::PaceOfficer);
    $pace = Pace::factory()->create(['number' => '1008']);
    $data = [
        'item_type' => 'score_key', 'sku' => 'SK-MATH-1008',
        'reorder_level' => 2, 'is_consumable' => false, 'is_active' => true,
    ];

    $this->actingAs($storekeeper)->post(route('inventory-items.store'), $data)
        ->assertSessionHasErrors('pace_id');
    $this->actingAs($storekeeper)->post(route('inventory-items.store'), [...$data, 'pace_id' => $pace->id])
        ->assertSessionHasErrors('pace_id');

    $item = InventoryItem::query()
        ->where('pace_id', $pace->id)
        ->where('item_type', InventoryItemType::ScoreKey)
        ->sole();
    expect($item->pace_id)->toBe($pace->id)
        ->and($item->is_consumable)->toBeFalse();

    $this->actingAs($storekeeper)->post(route('inventory-items.store'), [
        ...$data, 'sku' => 'SK-MATH-1008-B', 'pace_id' => $pace->id,
    ])->assertSessionHasErrors('pace_id');
});

test('an unlinked score key can be repaired without losing its ledger balance', function () {
    $storekeeper = createStaffWithRole(RoleName::PaceOfficer);
    $pace = Pace::factory()->create(['number' => '1008']);
    $pace->inventoryItems()->where('item_type', InventoryItemType::ScoreKey)->delete();
    $item = InventoryItem::factory()->create(['pace_id' => null, 'sku' => 'PACE 1008', 'reorder_level' => 2]);
    app(StockLedgerService::class)->postManual($item, StockMovementType::Receipt, 20, 'DEL-REPAIR-001', null, $storekeeper);

    $this->actingAs($storekeeper)->put(route('inventory-items.update', $item), [
        'pace_id' => $pace->id, 'sku' => 'SK-MATH-1008',
        'reorder_level' => 2, 'is_active' => true,
    ])->assertRedirect();

    expect($item->fresh()->pace_id)->toBe($pace->id)
        ->and($item->fresh()->sku)->toBe('SK-MATH-1008')
        ->and($item->onHand())->toBe(20);
});

test('an unidentified legacy score key can be deactivated without a false PACE link', function () {
    $storekeeper = createStaffWithRole(RoleName::PaceOfficer);
    $item = InventoryItem::factory()->create(['pace_id' => null, 'sku' => 'UNKNOWN-1008']);

    $this->actingAs($storekeeper)->put(route('inventory-items.update', $item), [
        'pace_id' => null, 'sku' => $item->sku,
        'reorder_level' => 0, 'is_active' => false,
    ])->assertRedirect();

    expect($item->fresh()->pace_id)->toBeNull()
        ->and($item->fresh()->is_active)->toBeFalse();
});

test('unauthenticated user cannot access inventory ledger', function () {
    $this->get(route('inventory.ledger'))->assertRedirect(route('login'));
});
