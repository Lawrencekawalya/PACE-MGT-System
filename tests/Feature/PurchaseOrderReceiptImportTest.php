<?php

use App\GoodsReceiptImportStatus;
use App\Models\GoodsReceipt;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseOrderReceiptImport;
use App\Models\StockMovement;
use App\Models\User;
use App\PurchaseOrderStatus;
use App\ReportFormat;
use App\RoleName;
use App\Services\PurchaseOrderExportService;
use App\Services\PurchaseOrderService;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
    Storage::fake('local');
});

test('a PACE Officer validates a delivery workbook before stock changes', function () {
    $fixture = receiptImportFixture([5]);
    $file = completedDeliveryWorkbook($fixture['order'], [3]);

    $this->actingAs($fixture['officer'])
        ->post(route('purchase-orders.receipt-imports.store', $fixture['order']), [
            'workbook' => $file,
        ])
        ->assertRedirect();

    $import = PurchaseOrderReceiptImport::query()->sole();
    expect($import->status)->toBe(GoodsReceiptImportStatus::Ready)
        ->and($import->valid_rows)->toBe(1)
        ->and($import->invalid_rows)->toBe(0)
        ->and($fixture['item']->onHand())->toBe(0)
        ->and(GoodsReceipt::query()->count())->toBe(0);

    $this->actingAs($fixture['officer'])
        ->get(route('purchase-order-receipt-imports.show', $import))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('purchase-order-receipt-imports/Show')
            ->where('receiptImport.id', $import->id)
            ->where('receiptImport.rows.0.normalized_data.quantity_received', 3)
            ->where('canCommit', true));
});

test('posting a validated delivery creates an audited receipt and updates stock once', function () {
    $fixture = receiptImportFixture([5]);
    $file = completedDeliveryWorkbook($fixture['order'], [5]);

    $this->actingAs($fixture['officer'])->post(
        route('purchase-orders.receipt-imports.store', $fixture['order']),
        ['workbook' => $file],
    );
    $import = PurchaseOrderReceiptImport::query()->sole();

    $this->actingAs($fixture['officer'])
        ->post(route('purchase-order-receipt-imports.commit', $import), [
            'delivery_reference' => 'DN-2026-104',
            'received_at' => now()->subMinute()->toDateTimeString(),
            'notes' => 'Counted and accepted by the PACE Officer.',
        ])
        ->assertRedirect(route('purchase-order-receipt-imports.show', $import));

    $import->refresh();
    expect($import->status)->toBe(GoodsReceiptImportStatus::Committed)
        ->and($import->committed_by)->toBe($fixture['officer']->id)
        ->and($import->goods_receipt_id)->not->toBeNull()
        ->and($fixture['order']->fresh()->status)->toBe(PurchaseOrderStatus::Received)
        ->and($fixture['item']->onHand())->toBe(5)
        ->and(StockMovement::query()->count())->toBe(1);

    $this->actingAs($fixture['officer'])
        ->post(route('purchase-order-receipt-imports.commit', $import), [
            'delivery_reference' => 'DN-2026-104',
            'received_at' => now()->subMinute()->toDateTimeString(),
        ])
        ->assertForbidden();

    expect($fixture['item']->onHand())->toBe(5)
        ->and(GoodsReceipt::query()->count())->toBe(1);
});

test('a partial delivery keeps the order in the sent queue for later receipts', function () {
    $fixture = receiptImportFixture([5, 4]);
    $file = completedDeliveryWorkbook($fixture['order'], [5, 0]);

    $this->actingAs($fixture['officer'])->post(
        route('purchase-orders.receipt-imports.store', $fixture['order']),
        ['workbook' => $file],
    );
    $import = PurchaseOrderReceiptImport::query()->sole();

    $this->actingAs($fixture['officer'])->post(
        route('purchase-order-receipt-imports.commit', $import),
        [
            'delivery_reference' => 'PARTIAL-001',
            'received_at' => now()->subMinute()->toDateTimeString(),
        ],
    );

    expect($fixture['order']->fresh()->status)->toBe(PurchaseOrderStatus::PartiallyReceived)
        ->and($import->fresh()->valid_rows)->toBe(1)
        ->and($import->fresh()->skipped_rows)->toBe(1)
        ->and($fixture['items'][0]->onHand())->toBe(5)
        ->and($fixture['items'][1]->onHand())->toBe(0);

    $this->actingAs($fixture['officer'])
        ->get(route('purchase-orders.sent'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('orders.data.0.id', $fixture['order']->id)
            ->where('orders.data.0.status', PurchaseOrderStatus::PartiallyReceived->value));
});

test('changed identity fields and invalid quantities fail validation without changing stock', function (string $cell, mixed $value) {
    $fixture = receiptImportFixture([5]);
    $file = completedDeliveryWorkbook($fixture['order'], [3], [$cell => $value]);

    $this->actingAs($fixture['officer'])->post(
        route('purchase-orders.receipt-imports.store', $fixture['order']),
        ['workbook' => $file],
    );

    $import = PurchaseOrderReceiptImport::query()->sole();
    expect($import->status)->toBe(GoodsReceiptImportStatus::Failed)
        ->and($import->invalid_rows)->toBe(1)
        ->and($fixture['item']->onHand())->toBe(0)
        ->and(GoodsReceipt::query()->count())->toBe(0);
})->with([
    'changed SKU' => ['C9', 'UNRELATED-SKU'],
    'changed inventory item' => ['B9', 999999],
    'formula quantity' => ['K9', '=2+1'],
    'text quantity' => ['K9', 'five'],
]);

test('an over delivery is validated and posts all received stock', function () {
    $fixture = receiptImportFixture([5]);
    $file = completedDeliveryWorkbook($fixture['order'], [6]);

    $this->actingAs($fixture['officer'])->post(
        route('purchase-orders.receipt-imports.store', $fixture['order']),
        ['workbook' => $file],
    );

    $import = PurchaseOrderReceiptImport::query()->sole();
    expect($import->status)->toBe(GoodsReceiptImportStatus::Ready)
        ->and($import->invalid_rows)->toBe(0)
        ->and($import->rows()->sole()->normalized_data['outstanding_before'])->toBe(5)
        ->and($import->rows()->sole()->normalized_data['outstanding_after'])->toBe(0)
        ->and($import->rows()->sole()->normalized_data['excess_quantity'])->toBe(1);

    $this->actingAs($fixture['officer'])
        ->post(route('purchase-order-receipt-imports.commit', $import), [
            'delivery_reference' => 'DELIVERY-OVER',
            'received_at' => now()->subMinute()->toDateTimeString(),
        ])
        ->assertRedirect(route('purchase-order-receipt-imports.show', $import));

    expect($fixture['item']->onHand())->toBe(6)
        ->and($fixture['order']->fresh()->status)->toBe(PurchaseOrderStatus::Received)
        ->and(GoodsReceipt::query()->sole()->lines()->sole()->quantity_received)->toBe(6);
});

test('removing an order line from the delivery workbook fails validation', function () {
    $fixture = receiptImportFixture([5]);
    $file = completedDeliveryWorkbook($fixture['order'], [3], [
        'A9' => null,
        'B9' => null,
        'C9' => null,
        'K9' => null,
    ]);

    $this->actingAs($fixture['officer'])->post(
        route('purchase-orders.receipt-imports.store', $fixture['order']),
        ['workbook' => $file],
    );

    $import = PurchaseOrderReceiptImport::query()->sole();
    expect($import->status)->toBe(GoodsReceiptImportStatus::Failed)
        ->and($import->invalid_rows)->toBe(1)
        ->and($import->rows()->sole()->errors)->toContain(
            'This purchase-order line is missing from the uploaded file. Export a current copy of the order.',
        )
        ->and($fixture['item']->onHand())->toBe(0);
});

test('teachers cannot upload purchase order delivery files', function () {
    $fixture = receiptImportFixture([5]);

    $this->actingAs(createStaffWithRole(RoleName::Teacher))
        ->post(route('purchase-orders.receipt-imports.store', $fixture['order']), [
            'workbook' => completedDeliveryWorkbook($fixture['order'], [5]),
        ])
        ->assertForbidden();

    expect(PurchaseOrderReceiptImport::query()->count())->toBe(0);
});

test('posting stops when the order changes after the import preview', function () {
    $fixture = receiptImportFixture([5]);
    $this->actingAs($fixture['officer'])->post(
        route('purchase-orders.receipt-imports.store', $fixture['order']),
        ['workbook' => completedDeliveryWorkbook($fixture['order'], [3])],
    );
    $import = PurchaseOrderReceiptImport::query()->sole();

    app(PurchaseOrderService::class)->receive($fixture['order'], [
        'delivery_reference' => 'OTHER-DELIVERY',
        'received_at' => now()->subMinute(),
        'lines' => [[
            'purchase_order_line_id' => $fixture['order']->lines()->sole()->id,
            'quantity_received' => 1,
        ]],
    ], $fixture['officer']);

    $this->actingAs($fixture['officer'])
        ->post(route('purchase-order-receipt-imports.commit', $import), [
            'delivery_reference' => 'STALE-DELIVERY',
            'received_at' => now()->subMinute()->toDateTimeString(),
        ])
        ->assertSessionHasErrors('import');

    expect($import->fresh()->status)->toBe(GoodsReceiptImportStatus::Ready)
        ->and($fixture['item']->onHand())->toBe(1)
        ->and(GoodsReceipt::query()->count())->toBe(1);
});

/** @return array{officer: User, order: PurchaseOrder, item: InventoryItem, items: list<InventoryItem>} */
function receiptImportFixture(array $quantities): array
{
    $officer = createStaffWithRole(RoleName::PaceOfficer);
    $order = PurchaseOrder::factory()->create([
        'status' => PurchaseOrderStatus::Sent,
        'sent_by' => $officer->id,
        'sent_at' => now(),
    ]);
    $items = [];
    foreach ($quantities as $index => $quantity) {
        $item = InventoryItem::factory()->create(['sku' => "DELIVERY-ITEM-{$index}"]);
        PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $order->id,
            'inventory_item_id' => $item->id,
            'quantity_ordered' => $quantity,
        ]);
        $items[] = $item;
    }

    return ['officer' => $officer, 'order' => $order, 'item' => $items[0], 'items' => $items];
}

/** @param list<int> $quantities @param array<string, mixed> $changes */
function completedDeliveryWorkbook(PurchaseOrder $order, array $quantities, array $changes = []): UploadedFile
{
    $contents = app(PurchaseOrderExportService::class)->generate($order, ReportFormat::Xlsx);
    $temporary = tempnam(sys_get_temp_dir(), 'receipt-import-test-');
    expect($temporary)->not->toBeFalse();
    file_put_contents($temporary, $contents);
    $spreadsheet = IOFactory::load($temporary);
    foreach ($quantities as $index => $quantity) {
        $spreadsheet->getActiveSheet()->setCellValue('K'.(9 + $index), $quantity);
    }
    foreach ($changes as $cell => $value) {
        $spreadsheet->getActiveSheet()->setCellValue($cell, $value);
    }
    (new Xlsx($spreadsheet))->save($temporary);
    $completed = file_get_contents($temporary);
    unlink($temporary);
    $spreadsheet->disconnectWorksheets();
    expect($completed)->not->toBeFalse();

    return UploadedFile::fake()->createWithContent('completed-order.xlsx', $completed);
}
