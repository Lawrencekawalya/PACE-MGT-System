<?php

use App\InventoryItemType;
use App\Models\Course;
use App\Models\Pace;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Subject;
use App\Models\User;
use App\PurchaseOrderStatus;
use App\RoleName;
use Database\Seeders\AccessControlSeeder;
use PhpOffice\PhpSpreadsheet\IOFactory;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
});

test('a PACE Officer can download an approved order as CSV', function () {
    $fixture = createPurchaseOrderExportFixture();

    $response = $this->actingAs($fixture['officer'])->get(route('purchase-orders.download', [
        'purchase_order' => $fixture['order'],
        'format' => 'csv',
    ]));

    $response->assertOk()
        ->assertDownload("{$fixture['order']->order_number}.csv")
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    expect($response->getContent())
        ->toContain('Order number')
        ->toContain('Supplier code')
        ->toContain('PACE-MATH-1001')
        ->toContain('PACE booklet')
        ->toContain('Mathematics')
        ->toContain('Math Grade 4')
        ->toContain('1001')
        ->toContain('Fractions')
        ->toContain("'=Keep boxed");
});

test('a PACE Officer can download a formatted approved order workbook', function () {
    $fixture = createPurchaseOrderExportFixture();

    $response = $this->actingAs($fixture['officer'])->get(route('purchase-orders.download', [
        'purchase_order' => $fixture['order'],
        'format' => 'xlsx',
    ]));

    $response->assertOk()
        ->assertDownload("{$fixture['order']->order_number}.xlsx")
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $temporary = tempnam(sys_get_temp_dir(), 'order-export-test-');
    expect($temporary)->not->toBeFalse();
    file_put_contents($temporary, $response->getContent());
    $sheet = IOFactory::load($temporary)->getActiveSheet();
    unlink($temporary);

    expect($sheet->getCell('A1')->getValue())->toBe("Purchase Order {$fixture['order']->order_number}")
        ->and($sheet->getCell('B4')->getValue())->toBe('ACE Books (ACE-UG)')
        ->and($sheet->rangeToArray('A8:J8')[0])->toBe([
            'SKU', 'Item type', 'Subject', 'Course', 'PACE number',
            'PACE title', 'Ordered', 'Received', 'Outstanding', 'Line notes',
        ])
        ->and($sheet->getCell('A9')->getValue())->toBe('PACE-MATH-1001')
        ->and($sheet->getCell('G9')->getValue())->toBe(25)
        ->and($sheet->getCell('I9')->getValue())->toBe(25);
});

test('purchase order exports require a supported format and eligible order status', function () {
    $officer = createStaffWithRole(RoleName::PaceOfficer);
    $draft = PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::Draft]);
    $approved = PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::Approved]);

    $this->actingAs($officer)->get(route('purchase-orders.download', [
        'purchase_order' => $draft,
        'format' => 'csv',
    ]))->assertForbidden();

    $this->actingAs($officer)->get(route('purchase-orders.download', [
        'purchase_order' => $approved,
        'format' => 'pdf',
    ]))->assertSessionHasErrors('format');
});

test('teachers cannot export purchase orders', function () {
    $teacher = createStaffWithRole(RoleName::Teacher);
    $order = PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::Approved]);

    $this->actingAs($teacher)->get(route('purchase-orders.download', [
        'purchase_order' => $order,
        'format' => 'xlsx',
    ]))->assertForbidden();
});

/**
 * @return array{officer: User, order: PurchaseOrder}
 */
function createPurchaseOrderExportFixture(): array
{
    $officer = createStaffWithRole(RoleName::PaceOfficer);
    $administrator = createStaffWithRole(RoleName::Administrator);
    $subject = Subject::factory()->create(['name' => 'Mathematics']);
    $course = Course::factory()->create([
        'subject_id' => $subject->id,
        'name' => 'Math Grade 4',
        'code' => 'MATH-G4',
    ]);
    $pace = Pace::factory()->create([
        'course_id' => $course->id,
        'number' => '1001',
        'title' => 'Fractions',
    ]);
    $item = $pace->inventoryItems()
        ->where('item_type', InventoryItemType::PaceBooklet)
        ->sole();
    $item->update(['sku' => 'PACE-MATH-1001']);
    $order = PurchaseOrder::factory()->create([
        'order_number' => 'PO-2026-00001',
        'status' => PurchaseOrderStatus::Approved,
        'expected_on' => '2026-08-28',
        'created_by' => $officer->id,
        'submitted_by' => $officer->id,
        'submitted_at' => now()->subHour(),
        'decided_by' => $administrator->id,
        'decided_at' => now(),
        'notes' => 'Term four replenishment',
    ]);
    $order->supplier->update(['name' => 'ACE Books', 'code' => 'ACE-UG']);
    PurchaseOrderLine::factory()->create([
        'purchase_order_id' => $order->id,
        'inventory_item_id' => $item->id,
        'quantity_ordered' => 25,
        'notes' => '=Keep boxed',
    ]);

    return compact('officer', 'order');
}
