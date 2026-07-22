<?php

use App\Models\AcademicYear;
use App\Models\CatalogueImport;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\Term;
use App\Models\User;
use App\Services\DataIntegrityService;
use App\StockMovementType;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    $year = AcademicYear::factory()->create(['is_active' => true]);
    Term::factory()->create([
        'academic_year_id' => $year->id,
        'is_active' => true,
    ]);
});

test('the seeded catalogue reconciles with its committed workbook', function () {
    $result = app(DataIntegrityService::class)->catalogue();

    expect($result['issues'])->toBeEmpty()
        ->and($result['actual'])->toBe($result['expected'])
        ->and($result['checksum'])->toHaveLength(64);

    $this->artisan('catalogue:reconcile')->assertSuccessful();
});

test('catalogue reconciliation detects drift from the committed workbook', function () {
    DB::table('paces')->orderBy('id')->limit(1)->update(['number' => 'DRIFTED']);

    $result = app(DataIntegrityService::class)->catalogue();

    expect($result['issues'])->not->toBeEmpty()
        ->and(collect($result['issues'])->contains(
            fn (string $issue): bool => str_contains($issue, 'PACE sequence'),
        ))->toBeTrue();

    $this->artisan('catalogue:reconcile')->assertFailed();
});

test('stock validation detects a corrupted running balance', function () {
    $item = InventoryItem::query()->firstOrFail();
    $movement = StockMovement::factory()->create([
        'inventory_item_id' => $item->id,
        'type' => StockMovementType::Receipt,
        'quantity' => 10,
        'balance_after' => 10,
    ]);
    DB::table('stock_movements')->where('id', $movement->id)->update(['balance_after' => 7]);

    expect(DB::table('stock_movements')->where('id', $movement->id)->value('balance_after'))->toBe(7);

    $result = app(DataIntegrityService::class)->stockLedger();

    expect(collect($result['issues'])->contains(
        fn (string $issue): bool => str_contains($issue, "Stock movement {$movement->id} records balance 7"),
    ))->toBeTrue();

    $this->artisan('system:validate-data')->assertFailed();
});

test('a specific catalogue import must exist and be committed', function () {
    $administrator = User::query()->firstOrFail();
    $import = CatalogueImport::query()->create([
        'original_name' => 'uncommitted.xlsx',
        'file_path' => 'catalogue-imports/uncommitted.xlsx',
        'checksum' => str_repeat('a', 64),
        'status' => 'ready',
        'uploaded_by' => $administrator->id,
    ]);

    $this->artisan('catalogue:reconcile', ['import' => $import->id])
        ->expectsOutput("Committed catalogue import {$import->id} was not found.")
        ->assertFailed();
});
