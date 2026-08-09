<?php

use App\InventoryItemType;
use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\InventoryItem;
use App\Models\Pace;
use App\RoleName;
use App\Services\ReorderService;
use Database\Seeders\AccessControlSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
});

test('PACE Officer can configure every active inventory item at once', function () {
    $officer = createStaffWithRole(RoleName::PaceOfficer);
    $activeItems = InventoryItem::factory()->count(3)->create();
    $inactiveItem = InventoryItem::factory()->create([
        'is_active' => false,
        'reorder_level' => 1,
        'target_stock_level' => 2,
    ]);

    $this->actingAs($officer)->put(route('inventory.bulk-settings.update'), [
        'scope' => 'all',
        'inventory_item_ids' => [],
        'item_type' => '',
        'course_id' => '',
        'reorder_level' => 5,
        'target_stock_level' => 20,
    ])->assertRedirect();

    expect($activeItems->every(fn (InventoryItem $item): bool => $item->fresh()->reorder_level === 5
        && $item->fresh()->target_stock_level === 20))->toBeTrue()
        ->and($inactiveItem->fresh()->reorder_level)->toBe(1)
        ->and($inactiveItem->fresh()->target_stock_level)->toBe(2)
        ->and(app(ReorderService::class)->suggestions())->toHaveCount(3)
        ->and(app(ReorderService::class)->suggestions()->pluck('suggested_quantity')->unique()->all())->toBe([20]);

    $log = ActivityLog::query()->where('event', 'inventory-settings.bulk-updated')->sole();
    expect($log->subject_type)->toBeNull()
        ->and($log->subject_id)->toBeNull()
        ->and($log->new_values['scope'])->toBe('all')
        ->and($log->new_values['affected_items'])->toBe(3);
});

test('bulk settings can target one item type', function () {
    $officer = createStaffWithRole(RoleName::PaceOfficer);
    $booklet = InventoryItem::factory()->create(['item_type' => InventoryItemType::PaceBooklet]);
    $scoreKey = InventoryItem::factory()->create(['item_type' => InventoryItemType::ScoreKey]);

    $this->actingAs($officer)->put(route('inventory.bulk-settings.update'), [
        'scope' => 'item_type',
        'item_type' => InventoryItemType::PaceBooklet->value,
        'reorder_level' => 4,
        'target_stock_level' => 12,
    ])->assertRedirect();

    expect($booklet->fresh()->reorder_level)->toBe(4)
        ->and($booklet->fresh()->target_stock_level)->toBe(12)
        ->and($scoreKey->fresh()->reorder_level)->toBe(0)
        ->and($scoreKey->fresh()->target_stock_level)->toBe(0);
});

test('bulk settings can target all items connected to one course', function () {
    $officer = createStaffWithRole(RoleName::PaceOfficer);
    $selectedCourse = Course::factory()->create();
    $otherCourse = Course::factory()->create();
    $selectedPace = Pace::factory()->create(['course_id' => $selectedCourse->id]);
    $otherPace = Pace::factory()->create(['course_id' => $otherCourse->id]);
    $booklet = InventoryItem::query()
        ->where('pace_id', $selectedPace->id)
        ->where('item_type', InventoryItemType::PaceBooklet)
        ->sole();
    $scoreKey = InventoryItem::query()
        ->where('pace_id', $selectedPace->id)
        ->where('item_type', InventoryItemType::ScoreKey)
        ->sole();
    $other = InventoryItem::query()
        ->where('pace_id', $otherPace->id)
        ->where('item_type', InventoryItemType::PaceBooklet)
        ->sole();

    $this->actingAs($officer)->put(route('inventory.bulk-settings.update'), [
        'scope' => 'course',
        'course_id' => $selectedCourse->id,
        'reorder_level' => 3,
        'target_stock_level' => 15,
    ])->assertRedirect();

    expect($booklet->fresh()->target_stock_level)->toBe(15)
        ->and($scoreKey->fresh()->target_stock_level)->toBe(15)
        ->and($other->fresh()->target_stock_level)->toBe(0);
});

test('selected-item scope updates exactly the checked records', function () {
    $officer = createStaffWithRole(RoleName::PaceOfficer);
    $selected = InventoryItem::factory()->create(['is_active' => false]);
    $alsoSelected = InventoryItem::factory()->create();
    $notSelected = InventoryItem::factory()->create();

    $this->actingAs($officer)->put(route('inventory.bulk-settings.update'), [
        'scope' => 'selected',
        'inventory_item_ids' => [$selected->id, $alsoSelected->id],
        'reorder_level' => 6,
        'target_stock_level' => 18,
    ])->assertRedirect();

    expect($selected->fresh()->target_stock_level)->toBe(18)
        ->and($alsoSelected->fresh()->target_stock_level)->toBe(18)
        ->and($notSelected->fresh()->target_stock_level)->toBe(0);
});

test('bulk settings reject a target below the reorder level', function () {
    $officer = createStaffWithRole(RoleName::PaceOfficer);
    InventoryItem::factory()->create();

    $this->actingAs($officer)->put(route('inventory.bulk-settings.update'), [
        'scope' => 'all',
        'reorder_level' => 10,
        'target_stock_level' => 5,
    ])->assertSessionHasErrors('target_stock_level');
});

test('teacher cannot bulk configure inventory', function () {
    $teacher = createStaffWithRole(RoleName::Teacher);
    InventoryItem::factory()->create();

    $this->actingAs($teacher)->put(route('inventory.bulk-settings.update'), [
        'scope' => 'all',
        'reorder_level' => 5,
        'target_stock_level' => 20,
    ])->assertForbidden();
});
