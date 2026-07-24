<?php

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\PurchaseOrderStatus;
use App\RoleName;
use Database\Seeders\AccessControlSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
});

test('PACE Officers manage ordering but teachers cannot access it', function () {
    $officer = createStaffWithRole(RoleName::PaceOfficer);
    $teacher = createStaffWithRole(RoleName::Teacher);
    $supplier = Supplier::factory()->create();

    $this->actingAs($teacher)->get(route('purchase-orders.index'))->assertForbidden();
    $this->actingAs($teacher)->get(route('reorders.index'))->assertForbidden();
    $this->actingAs($teacher)->post(route('purchase-orders.store'), [
        'supplier_id' => $supplier->id,
        'source' => 'manual',
    ])->assertForbidden();

    $this->actingAs($officer)->get(route('purchase-orders.index'))->assertOk();
    $this->actingAs($officer)->get(route('reorders.index'))->assertOk();
    $this->actingAs($officer)->post(route('purchase-orders.store'), [
        'supplier_id' => $supplier->id,
        'source' => 'manual',
    ])->assertRedirect();
});

test('only administrators manage suppliers and approve orders', function () {
    $officer = createStaffWithRole(RoleName::PaceOfficer);
    $administrator = createStaffWithRole(RoleName::Administrator);
    $supplier = Supplier::factory()->create();
    $order = PurchaseOrder::factory()->create([
        'supplier_id' => $supplier->id,
        'status' => PurchaseOrderStatus::Submitted,
    ]);

    $this->actingAs($officer)->get(route('suppliers.index'))->assertOk();
    $this->actingAs($officer)->post(route('suppliers.store'), [
        'name' => 'New Supplier',
        'code' => 'NEW-SUP',
        'is_active' => true,
    ])->assertForbidden();
    $this->actingAs($officer)->post(route('purchase-orders.decision', $order), [
        'decision' => 'approve',
    ])->assertForbidden();

    $this->actingAs($administrator)->post(route('suppliers.store'), [
        'name' => 'New Supplier',
        'code' => 'NEW-SUP',
        'is_active' => true,
    ])->assertRedirect();
    $this->actingAs($administrator)->post(route('purchase-orders.decision', $order), [
        'decision' => 'approve',
    ])->assertRedirect();

    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::Approved);
});
