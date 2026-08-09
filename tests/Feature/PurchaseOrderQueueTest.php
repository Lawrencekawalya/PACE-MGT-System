<?php

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\PurchaseOrderStatus;
use App\RoleName;
use Database\Seeders\AccessControlSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
});

test('administrators review only submitted orders in the approval queue', function () {
    $administrator = createStaffWithRole(RoleName::Administrator);
    $submitted = PurchaseOrder::factory()->create([
        'status' => PurchaseOrderStatus::Submitted,
        'submitted_by' => $administrator->id,
        'submitted_at' => now(),
    ]);
    PurchaseOrderLine::factory()->create([
        'purchase_order_id' => $submitted->id,
        'quantity_ordered' => 12,
    ]);
    PurchaseOrder::factory()->create([
        'status' => PurchaseOrderStatus::Approved,
        'decided_at' => now(),
    ]);

    $this->actingAs($administrator)
        ->get(route('purchase-orders.submitted'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('purchase-orders/Queue')
            ->where('queue', 'submitted')
            ->has('orders.data', 1)
            ->where('orders.data.0.id', $submitted->id)
            ->where('orders.data.0.lines_count', 1)
            ->where('orders.data.0.units_count', 12)
            ->where('orders.data.0.can_decide', true)
            ->where('orders.data.0.can_send', false)
            ->where('orders.data.0.can_export', false));
});

test('only administrators can access the submitted order queue', function (RoleName $role) {
    $user = createStaffWithRole($role);

    $this->actingAs($user)
        ->get(route('purchase-orders.submitted'))
        ->assertForbidden();
})->with([
    'PACE Officer' => RoleName::PaceOfficer,
    'Teacher' => RoleName::Teacher,
    'Accountant' => RoleName::Accountant,
]);

test('administrators and PACE Officers see only approved orders in the dispatch queue', function (RoleName $role, bool $canSend) {
    $user = createStaffWithRole($role);
    $approved = PurchaseOrder::factory()->create([
        'status' => PurchaseOrderStatus::Approved,
        'decided_by' => $user->id,
        'decided_at' => now(),
    ]);
    PurchaseOrder::factory()->create([
        'status' => PurchaseOrderStatus::Submitted,
        'submitted_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('purchase-orders.approved'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('purchase-orders/Queue')
            ->where('queue', 'approved')
            ->has('orders.data', 1)
            ->where('orders.data.0.id', $approved->id)
            ->where('orders.data.0.can_decide', false)
            ->where('orders.data.0.can_send', $canSend)
            ->where('orders.data.0.can_export', true));
})->with([
    'Administrator oversight' => [RoleName::Administrator, false],
    'PACE Officer dispatch' => [RoleName::PaceOfficer, true],
]);

test('teachers and accountants cannot access the approved order queue', function (RoleName $role) {
    $user = createStaffWithRole($role);

    $this->actingAs($user)
        ->get(route('purchase-orders.approved'))
        ->assertForbidden();
})->with([
    'Teacher' => RoleName::Teacher,
    'Accountant' => RoleName::Accountant,
]);

test('PACE Officers and administrators can view sent and partially received orders', function (RoleName $role, bool $canImport) {
    $user = createStaffWithRole($role);
    $sent = PurchaseOrder::factory()->create([
        'status' => PurchaseOrderStatus::Sent,
        'sent_by' => $user->id,
        'sent_at' => now(),
    ]);
    $partial = PurchaseOrder::factory()->create([
        'status' => PurchaseOrderStatus::PartiallyReceived,
        'sent_by' => $user->id,
        'sent_at' => now()->subDay(),
    ]);
    PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::Received]);

    $this->actingAs($user)
        ->get(route('purchase-orders.sent'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('purchase-orders/Queue')
            ->where('queue', 'sent')
            ->has('orders.data', 2)
            ->where('orders.data.0.id', $sent->id)
            ->where('orders.data.0.can_import', $canImport)
            ->where('orders.data.1.id', $partial->id));
})->with([
    'PACE Officer receiving' => [RoleName::PaceOfficer, true],
    'Administrator oversight' => [RoleName::Administrator, true],
]);

test('teachers and accountants cannot access the sent order queue', function (RoleName $role) {
    $this->actingAs(createStaffWithRole($role))
        ->get(route('purchase-orders.sent'))
        ->assertForbidden();
})->with([
    'Teacher' => RoleName::Teacher,
    'Accountant' => RoleName::Accountant,
]);

test('a PACE Officer can mark an approved order as sent from the dispatch queue', function () {
    $administrator = createStaffWithRole(RoleName::Administrator);
    $officer = createStaffWithRole(RoleName::PaceOfficer);
    $order = PurchaseOrder::factory()->create([
        'status' => PurchaseOrderStatus::Approved,
        'decided_by' => $administrator->id,
        'decided_at' => now(),
    ]);

    $this->actingAs($officer)
        ->post(route('purchase-orders.send', $order))
        ->assertRedirect();

    $order = $order->fresh();

    expect($order->status)->toBe(PurchaseOrderStatus::Sent)
        ->and($order->sent_by)->toBe($officer->id);
});

test('order review returns to the originating workflow queue', function () {
    $administrator = createStaffWithRole(RoleName::Administrator);
    $order = PurchaseOrder::factory()->create([
        'status' => PurchaseOrderStatus::Submitted,
        'submitted_at' => now(),
    ]);

    $this->actingAs($administrator)
        ->get(route('purchase-orders.show', [
            'purchase_order' => $order,
            'from' => 'submitted',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('purchase-orders/Show')
            ->where('backLink.label', 'Submitted orders')
            ->where('backLink.url', route('purchase-orders.submitted')));
});
