<?php

use App\Models\AcademicYear;
use App\Models\CatalogueImport;
use App\Models\Course;
use App\Models\CurriculumRequirement;
use App\Models\InventoryItem;
use App\Models\Level;
use App\Models\Pace;
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

test('Physical Science 1 belongs to Grade 9 at curriculum order seven', function () {
    $gradeEight = Level::query()->where('name', 'Grade 8')->firstOrFail();
    $gradeNine = Level::query()->where('name', 'Grade 9')->firstOrFail();
    $course = Course::query()->where('code', 'PHYSICAL-SCIENCE-1')->firstOrFail();
    $requirement = CurriculumRequirement::query()
        ->with('paces:id,number')
        ->where('level_id', $gradeNine->id)
        ->where('course_id', $course->id)
        ->firstOrFail();

    expect($requirement->is_required)->toBeTrue()
        ->and($requirement->sort_order)->toBe(7)
        ->and($requirement->paces->pluck('number')->all())->toBe(array_map('strval', range(1097, 1108)))
        ->and(CurriculumRequirement::query()
            ->where('level_id', $gradeEight->id)
            ->where('course_id', $course->id)
            ->exists())->toBeFalse();
});

test('the Physical Science 1 correction migration preserves existing PACE identifiers', function () {
    $gradeEight = Level::query()->where('name', 'Grade 8')->firstOrFail();
    $gradeNine = Level::query()->where('name', 'Grade 9')->firstOrFail();
    $course = Course::query()->where('code', 'PHYSICAL-SCIENCE-1')->firstOrFail();
    $requirement = CurriculumRequirement::query()
        ->where('level_id', $gradeNine->id)
        ->where('course_id', $course->id)
        ->firstOrFail();
    $paceIds = $course->paces()->pluck('id', 'number')->all();

    $requirement->update(['level_id' => $gradeEight->id, 'sort_order' => 8]);
    CurriculumRequirement::query()
        ->where('level_id', $gradeNine->id)
        ->where('sort_order', '>', 7)
        ->orderBy('sort_order')
        ->get()
        ->each(fn (CurriculumRequirement $item) => $item->update(['sort_order' => $item->sort_order - 1]));
    $course->paces()->get()->each(function (Pace $pace): void {
        $newNumber = $pace->number;
        $oldNumber = (string) ((int) $newNumber - 12);

        InventoryItem::query()
            ->where('pace_id', $pace->id)
            ->where('sku', "PACE-{$newNumber}-{$pace->id}")
            ->update(['sku' => "PACE-{$oldNumber}-{$pace->id}"]);
        $pace->update([
            'number' => $oldNumber,
            'sequence_order' => $pace->sequence_order - 12,
        ]);
    });
    $customSkuPaceId = (int) array_values($paceIds)[1];
    InventoryItem::query()->where('pace_id', $customSkuPaceId)->update(['sku' => 'CUSTOM-PHYSICS-SKU']);

    $migration = require database_path('migrations/2026_08_09_103330_correct_physical_science_one_grade_and_pace_sequence.php');
    $migration->up();

    $requirement->refresh();
    $correctedPaces = $course->paces()->pluck('id', 'number')->all();

    expect($requirement->level_id)->toBe($gradeNine->id)
        ->and($requirement->sort_order)->toBe(7)
        ->and($correctedPaces)->toBe($paceIds)
        ->and(InventoryItem::query()->where('pace_id', (int) array_values($paceIds)[0])->value('sku'))
        ->toBe('PACE-1097-'.array_values($paceIds)[0])
        ->and(InventoryItem::query()->where('pace_id', $customSkuPaceId)->value('sku'))
        ->toBe('CUSTOM-PHYSICS-SKU');
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
