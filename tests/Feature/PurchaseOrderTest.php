<?php

use App\Http\Requests\StorePurchaseOrderRequest;
use App\Models\GoodsReceipt;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Notifications\OperationalNotification;
use App\PurchaseOrderStatus;
use App\RoleName;
use App\Services\PurchaseOrderService;
use App\Services\ReorderService;
use App\Services\StockLedgerService;
use App\StockMovementType;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
});

test('an order requires approval and physical receipt before stock changes', function () {
    Notification::fake();
    $officer = createStaffWithRole(RoleName::PaceOfficer);
    $administrator = createStaffWithRole(RoleName::Administrator);
    $supplier = Supplier::factory()->create();
    $item = InventoryItem::factory()->create([
        'sku' => 'PACE-MATH-1001',
        'reorder_level' => 4,
        'target_stock_level' => 20,
    ]);

    $this->actingAs($officer)->post(route('purchase-orders.store'), [
        'supplier_id' => $supplier->id,
        'source' => 'manual',
        'expected_on' => now()->addWeek()->toDateString(),
    ])->assertRedirect();
    $order = PurchaseOrder::query()->sole();
    expect($order->order_number)->toMatch('/^PO-\d{4}-\d{5}$/')
        ->and($order->status)->toBe(PurchaseOrderStatus::Draft)
        ->and($order->toArray()['expected_on'])->toBe(now()->addWeek()->toDateString());

    $this->actingAs($officer)->post(route('purchase-orders.lines.store', $order), [
        'inventory_item_id' => $item->id,
        'quantity_ordered' => 10,
    ])->assertRedirect();
    $this->actingAs($officer)->post(route('purchase-orders.submit', $order))->assertRedirect();
    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::Submitted)
        ->and($item->onHand())->toBe(0);
    Notification::assertSentTo($administrator, OperationalNotification::class, fn (OperationalNotification $notification): bool => $notification->eventKey === "purchase-order:{$order->id}:submitted");

    $this->actingAs($administrator)->post(route('purchase-orders.decision', $order), [
        'decision' => 'approve',
    ])->assertRedirect();
    Notification::assertSentTo($officer, OperationalNotification::class, fn (OperationalNotification $notification): bool => $notification->eventKey === "purchase-order:{$order->id}:approved");
    $this->actingAs($officer)->post(route('purchase-orders.send', $order))->assertRedirect();
    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::Sent)
        ->and($item->onHand())->toBe(0)
        ->and(StockMovement::query()->count())->toBe(0);

    $line = $order->lines()->sole();
    $this->actingAs($officer)->post(route('purchase-orders.receipts.store', $order), [
        'delivery_reference' => 'DELIVERY-001',
        'received_at' => now()->subMinute()->toDateTimeString(),
        'lines' => [[
            'purchase_order_line_id' => $line->id,
            'quantity_received' => 4,
        ]],
    ])->assertRedirect();

    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::PartiallyReceived)
        ->and($item->onHand())->toBe(4)
        ->and(StockMovement::query()->sole()->type)->toBe(StockMovementType::Receipt)
        ->and(GoodsReceipt::query()->sole()->receipt_number)->toMatch('/^GRN-\d{4}-\d{5}$/');

    $this->actingAs($officer)->post(route('purchase-orders.receipts.store', $order), [
        'delivery_reference' => 'DELIVERY-002',
        'received_at' => now()->subMinute()->toDateTimeString(),
        'lines' => [[
            'purchase_order_line_id' => $line->id,
            'quantity_received' => 6,
        ]],
    ])->assertRedirect();

    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::Received)
        ->and($item->onHand())->toBe(10)
        ->and(StockMovement::query()->count())->toBe(2);
});

test('a draft order can contain more than five hundred catalogue items', function () {
    $officer = createStaffWithRole(RoleName::PaceOfficer);
    $supplier = Supplier::factory()->create();
    $items = InventoryItem::factory()->count(501)->create();

    $this->actingAs($officer)->post(route('purchase-orders.store'), [
        'supplier_id' => $supplier->id,
        'source' => 'reorder',
        'lines' => $items->map(fn (InventoryItem $item): array => [
            'inventory_item_id' => $item->id,
            'quantity_ordered' => 10,
        ])->all(),
    ])->assertRedirect();

    expect(PurchaseOrder::query()->sole()->lines()->count())->toBe(501);
});

test('a draft order accepts five thousand lines and rejects any additional line', function () {
    $officer = createStaffWithRole(RoleName::PaceOfficer);
    $supplier = Supplier::factory()->create();
    $item = InventoryItem::factory()->create();
    $request = StorePurchaseOrderRequest::create(
        route('purchase-orders.store'),
        'POST',
        [
            'supplier_id' => $supplier->id,
            'source' => 'reorder',
            'lines' => array_fill(0, 5001, [
                'inventory_item_id' => $item->id,
                'quantity_ordered' => 10,
            ]),
        ],
    );
    $request->setUserResolver(fn () => $officer);
    $rules = $request->rules();
    $maximumValidator = validator(
        ['lines' => array_fill(0, 5000, [])],
        ['lines' => $rules['lines']],
    );
    $overMaximumValidator = validator(
        ['lines' => $request->input('lines')],
        ['lines' => $rules['lines']],
    );

    expect($maximumValidator->passes())->toBeTrue()
        ->and($overMaximumValidator->fails())->toBeTrue()
        ->and($overMaximumValidator->errors()->has('lines'))->toBeTrue();
});

test('an over receipt posts all delivered units to stock', function () {
    $officer = createStaffWithRole(RoleName::PaceOfficer);
    $supplier = Supplier::factory()->create();
    $order = PurchaseOrder::factory()->create([
        'supplier_id' => $supplier->id,
        'status' => PurchaseOrderStatus::Sent,
    ]);
    $line = PurchaseOrderLine::factory()->create([
        'purchase_order_id' => $order->id,
        'quantity_ordered' => 5,
    ]);

    $this->actingAs($officer)->post(route('purchase-orders.receipts.store', $order), [
        'delivery_reference' => 'DELIVERY-OVER',
        'received_at' => now()->subMinute()->toDateTimeString(),
        'lines' => [[
            'purchase_order_line_id' => $line->id,
            'quantity_received' => 6,
        ]],
    ])->assertRedirect();

    expect(GoodsReceipt::query()->sole()->lines()->sole()->quantity_received)->toBe(6)
        ->and(StockMovement::query()->sole()->quantity)->toBe(6)
        ->and($line->inventoryItem->onHand())->toBe(6)
        ->and($order->fresh()->status)->toBe(PurchaseOrderStatus::Received);
});

test('correcting a goods receipt reopens the order quantity', function () {
    $officer = createStaffWithRole(RoleName::PaceOfficer);
    $order = PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::Sent]);
    $line = PurchaseOrderLine::factory()->create([
        'purchase_order_id' => $order->id,
        'quantity_ordered' => 5,
    ]);
    $receipt = app(PurchaseOrderService::class)->receive($order, [
        'delivery_reference' => 'DELIVERY-CORRECT',
        'received_at' => now()->subMinute(),
        'lines' => [[
            'purchase_order_line_id' => $line->id,
            'quantity_received' => 5,
        ]],
    ], $officer);
    $movement = $receipt->lines->sole()->stockMovement;

    app(StockLedgerService::class)->correct(
        $movement,
        'The delivery was recorded against the wrong order.',
        $officer,
    );

    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::Sent)
        ->and($line->outstandingQuantity())->toBe(5)
        ->and($line->inventoryItem->onHand())->toBe(0);
});

test('the reorder queue subtracts approved open quantities', function () {
    $officer = createStaffWithRole(RoleName::PaceOfficer);
    $item = InventoryItem::factory()->create([
        'sku' => 'PACE-REORDER-1',
        'reorder_level' => 3,
        'target_stock_level' => 10,
    ]);
    app(StockLedgerService::class)->postManual(
        $item,
        StockMovementType::Receipt,
        2,
        'OPENING-001',
        null,
        $officer,
    );
    $approved = PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::Approved]);
    PurchaseOrderLine::factory()->create([
        'purchase_order_id' => $approved->id,
        'inventory_item_id' => $item->id,
        'quantity_ordered' => 5,
    ]);
    $cancelled = PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::Cancelled]);
    PurchaseOrderLine::factory()->create([
        'purchase_order_id' => $cancelled->id,
        'inventory_item_id' => $item->id,
        'quantity_ordered' => 50,
    ]);

    $suggestion = app(ReorderService::class)->suggestions()->sole();

    expect($suggestion['on_hand'])->toBe(2)
        ->and($suggestion['on_order'])->toBe(5)
        ->and($suggestion['suggested_quantity'])->toBe(3);
});

test('a rejected order remains traceable and does not affect stock', function () {
    $officer = createStaffWithRole(RoleName::PaceOfficer);
    $administrator = createStaffWithRole(RoleName::Administrator);
    $order = PurchaseOrder::factory()->create([
        'status' => PurchaseOrderStatus::Submitted,
        'submitted_by' => $officer->id,
        'submitted_at' => now(),
    ]);

    $this->actingAs($administrator)->post(route('purchase-orders.decision', $order), [
        'decision' => 'reject',
        'reason' => 'Supplier details need review.',
    ])->assertRedirect();

    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::Rejected)
        ->and($order->fresh()->decision_reason)->toBe('Supplier details need review.')
        ->and(StockMovement::query()->count())->toBe(0);
});
