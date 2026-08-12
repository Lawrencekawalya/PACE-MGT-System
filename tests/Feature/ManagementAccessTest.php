<?php

use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\Student;
use App\PermissionName;
use App\RoleName;
use Database\Seeders\AccessControlSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
});

test('management receives school-wide oversight without operational permissions', function () {
    $management = createStaffWithRole(RoleName::Management);

    expect($management->hasPermission(PermissionName::ViewStudents))->toBeTrue()
        ->and($management->hasPermission(PermissionName::ViewAcademicReports))->toBeTrue()
        ->and($management->hasPermission(PermissionName::ViewInventoryReports))->toBeTrue()
        ->and($management->hasPermission(PermissionName::ViewPurchaseOrders))->toBeTrue()
        ->and($management->hasPermission(PermissionName::ViewPaceAccounts))->toBeTrue()
        ->and($management->hasPermission(PermissionName::ViewPaceCatalogue))->toBeTrue()
        ->and($management->hasPermission(PermissionName::RegisterStudents))->toBeFalse()
        ->and($management->hasPermission(PermissionName::AssignPaces))->toBeFalse()
        ->and($management->hasPermission(PermissionName::AdjustInventory))->toBeFalse()
        ->and($management->hasPermission(PermissionName::ManagePurchaseOrders))->toBeFalse()
        ->and($management->hasPermission(PermissionName::ApprovePurchaseOrders))->toBeFalse()
        ->and($management->hasPermission(PermissionName::ManagePaceAccounts))->toBeFalse();
});

test('management can review all oversight workspaces with write controls disabled', function () {
    $fixture = createReportFixture();
    $management = createStaffWithRole(RoleName::Management);
    $item = InventoryItem::factory()->create();
    $order = PurchaseOrder::factory()->create();

    $this->actingAs($management)->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('academic.metrics')
            ->has('inventory.metrics')
            ->has('paceAccounts.metrics')
            ->where('setup', null));

    $this->actingAs($management)->get(route('students.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('canCreate', false)
            ->has('students.data', 1));

    $this->actingAs($management)->get(route('students.show', $fixture['student']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('canAssign', false)
            ->where('canUpdate', false));

    $this->actingAs($management)->get(route('inventory.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('canAdjust', false));

    $this->actingAs($management)->get(route('inventory-items.show', $item))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('canAdjust', false));

    $this->actingAs($management)->get(route('purchase-orders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('canCreate', false));

    $this->actingAs($management)->get(route('purchase-orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('can.update', false)
            ->where('can.submit', false)
            ->where('can.decide', false)
            ->where('can.send', false)
            ->where('can.receive', false)
            ->where('can.cancel', false));

    $this->actingAs($management)->get(route('pace-accounts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('canSetPaceCost', false)
            ->where('canRecordPayments', false));

    $this->actingAs($management)->get(route('reports.index'))->assertOk();
    $this->actingAs($management)->get(route('admin.paces.index'))->assertOk();
});

test('management is forbidden from operational mutations', function () {
    $management = createStaffWithRole(RoleName::Management);
    $student = Student::factory()->create();
    $item = InventoryItem::factory()->create();

    $this->actingAs($management)->post(route('students.store'), [])->assertForbidden();
    $this->actingAs($management)->put(route('students.update', $student), [])->assertForbidden();
    $this->actingAs($management)->post(route('inventory-items.movements.store', $item), [])->assertForbidden();
    $this->actingAs($management)->put(route('inventory-items.update', $item), [])->assertForbidden();
    $this->actingAs($management)->post(route('purchase-orders.store'), [])->assertForbidden();
    $this->actingAs($management)->post(route('pace-accounts.payments.store', $student), [])->assertForbidden();
    $this->actingAs($management)->put(route('pace-accounts.cost.update'), [])->assertForbidden();
});
