<?php

use App\Models\InventoryItem;
use App\Models\PaceAssignment;
use App\Models\StockMovement;
use App\Models\Student;
use App\Models\StudentCourse;
use App\Models\StudentEnrollment;
use App\PaceAssignmentStatus;
use App\ReportType;
use App\RoleName;
use App\Services\ReportDataService;
use App\Services\StockLedgerService;
use App\StockMovementType;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
});

test('student progress is calculated from the configured curriculum sequence', function () {
    $fixture = createReportFixture();

    $this->actingAs($fixture['teacher'])->get(route('reports.index', [
        'report_type' => 'student_progress', 'academic_year_id' => $fixture['year']->id,
    ]))->assertOk()->assertInertia(fn ($page) => $page
        ->component('reports/Index')
        ->where('summary.records', 1)
        ->where('summary.completed_paces', 1)
        ->where('rows.data.0.admission_number', 'FICA-0001')
        ->where('rows.data.0.completed_paces', 1)
        ->where('rows.data.0.sequence_total', 3)
        ->where('rows.data.0.progress_percent', 33.3)
        ->where('rows.data.0.inactive', true));
});

test('course comparison aggregates student progress and attention counts', function () {
    $fixture = createReportFixture();

    $this->actingAs($fixture['teacher'])->get(route('reports.index', [
        'report_type' => 'course_progress', 'academic_year_id' => $fixture['year']->id,
    ]))->assertOk()->assertInertia(fn ($page) => $page
        ->where('rows.data.0.level', 'Grade 1')
        ->where('rows.data.0.course', 'Mathematics')
        ->where('rows.data.0.students', 1)
        ->where('rows.data.0.average_progress', 33.3)
        ->where('rows.data.0.inactive_students', 1));
});

test('pending report identifies overdue work and applies assignment filters', function () {
    $fixture = createReportFixture();

    $this->actingAs($fixture['teacher'])->get(route('reports.index', [
        'report_type' => 'pending_work', 'academic_year_id' => $fixture['year']->id,
        'assignment_status' => 'in_progress', 'course_id' => $fixture['course']->id,
    ]))->assertOk()->assertInertia(fn ($page) => $page
        ->where('summary.records', 1)
        ->where('summary.overdue', 1)
        ->where('rows.data.0.assignment_id', $fixture['active']->id)
        ->where('rows.data.0.next_action', 'Submit Self Test')
        ->where('rows.data.0.overdue', true));
});

test('academic and inventory reports enforce their separate permissions', function () {
    $fixture = createReportFixture();
    $storekeeper = createStaffWithRole(RoleName::PaceOfficer);

    $this->actingAs($fixture['teacher'])->get(route('reports.index', ['report_type' => 'inventory']))->assertForbidden();
    $this->actingAs($storekeeper)->get(route('reports.index', ['report_type' => 'student_progress']))->assertForbidden();
    $this->actingAs($storekeeper)->get(route('reports.index', ['report_type' => 'inventory']))->assertOk();
});

test('inventory report reconciles balances and period movements', function () {
    $fixture = createReportFixture();
    $storekeeper = createStaffWithRole(RoleName::PaceOfficer);
    $item = InventoryItem::query()->where('pace_id', $fixture['paces'][0]->id)->sole();
    $item->update(['reorder_level' => 4]);
    $ledger = app(StockLedgerService::class);
    $ledger->postManual($item, StockMovementType::Receipt, 5, 'REPORT-DEL-001', null, $storekeeper);
    $ledger->postManual($item, StockMovementType::Damage, 1, null, 'Damaged during handling.', $storekeeper);

    $this->actingAs($storekeeper)->get(route('reports.index', [
        'report_type' => 'inventory', 'course_id' => $fixture['course']->id,
        'stock' => 'low', 'date_from' => now()->toDateString(), 'date_to' => now()->toDateString(),
    ]))->assertOk()->assertInertia(fn ($page) => $page
        ->where('summary.records', 3)
        ->where('summary.on_hand', 4)
        ->where('rows.data.0.on_hand', 4)
        ->where('rows.data.0.received', 5)
        ->where('rows.data.0.stock_status', 'Low stock'));
});

test('PACE issuing report applies inclusive date and learning centre filters', function () {
    $fixture = createIssuingReportFixture();
    $olderAssignment = PaceAssignment::factory()->create([
        'student_course_id' => $fixture['studentCourse']->id,
        'pace_id' => $fixture['paces'][2]->id,
        'academic_year_id' => $fixture['year']->id,
        'term_id' => $fixture['term']->id,
        'status' => PaceAssignmentStatus::InProgress,
        'assigned_by' => $fixture['teacher']->id,
        'assigned_at' => now()->setDate(2026, 7, 1),
        'issued_by' => $fixture['officer']->id,
        'issued_at' => now()->setDate(2026, 7, 5),
    ]);
    $olderItem = InventoryItem::query()->where('pace_id', $fixture['paces'][2]->id)->sole();
    StockMovement::factory()->create([
        'inventory_item_id' => $olderItem->id,
        'type' => StockMovementType::Issue,
        'quantity' => -1,
        'balance_after' => 0,
        'student_id' => $fixture['student']->id,
        'pace_assignment_id' => $olderAssignment->id,
        'academic_year_id' => $fixture['year']->id,
        'term_id' => $fixture['term']->id,
        'reference' => "ISSUE-{$olderAssignment->id}",
        'recorded_by' => $fixture['officer']->id,
        'recorded_at' => now()->setDate(2026, 7, 5),
    ]);

    $this->actingAs($fixture['officer'])->get(route('reports.index', [
        'report_type' => 'pace_issuing',
        'learning_center_id' => $fixture['enrollment']->learning_center_id,
        'date_from' => '2026-07-15',
        'date_to' => '2026-07-15',
    ]))->assertOk()->assertInertia(fn ($page) => $page
        ->component('reports/Index')
        ->where('reportType', 'pace_issuing')
        ->where('summary.records', 1)
        ->where('summary.copies_issued', 1)
        ->where('summary.students', 1)
        ->where('summary.reversed', 0)
        ->where('rows.data.0.movement_id', $fixture['movement']->id)
        ->where('rows.data.0.admission_number', 'FICA-0001')
        ->where('rows.data.0.issued_date', '2026-07-15')
        ->where('rows.data.0.issued_by', $fixture['officer']->name)
        ->where('rows.data.0.status', 'Issued'));
});

test('PACE issuing report retains reversed issues and enforces inventory report permission', function () {
    $fixture = createIssuingReportFixture();
    StockMovement::factory()->create([
        'inventory_item_id' => $fixture['item']->id,
        'type' => StockMovementType::Correction,
        'quantity' => 1,
        'balance_after' => 1,
        'student_id' => $fixture['student']->id,
        'pace_assignment_id' => $fixture['active']->id,
        'academic_year_id' => $fixture['year']->id,
        'term_id' => $fixture['term']->id,
        'reference' => "CORRECTION-{$fixture['movement']->id}",
        'reason' => 'Issued to the wrong student.',
        'recorded_by' => $fixture['officer']->id,
        'recorded_at' => now()->setDate(2026, 7, 16),
        'corrects_movement_id' => $fixture['movement']->id,
    ]);

    $this->actingAs($fixture['officer'])
        ->get(route('reports.index', ['report_type' => 'pace_issuing']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('summary.records', 1)
            ->where('summary.copies_issued', 0)
            ->where('summary.reversed', 1)
            ->where('rows.data.0.status', 'Reversed'));

    $this->actingAs($fixture['teacher'])
        ->get(route('reports.index', ['report_type' => 'pace_issuing']))
        ->assertForbidden();
});

test('student progress query count remains bounded as representative rows increase', function () {
    $fixture = createReportFixture();
    foreach (range(1, 100) as $index) {
        $student = Student::factory()->create();
        $enrollment = StudentEnrollment::factory()->create([
            'student_id' => $student->id, 'academic_year_id' => $fixture['year']->id,
            'term_id' => $fixture['term']->id, 'level_id' => $fixture['level']->id,
        ]);
        StudentCourse::factory()->create([
            'student_enrollment_id' => $enrollment->id, 'course_id' => $fixture['course']->id,
            'starting_pace_id' => $fixture['paces'][0]->id, 'current_pace_id' => $fixture['paces'][0]->id,
        ]);
    }

    DB::flushQueryLog();
    DB::enableQueryLog();
    $result = app(ReportDataService::class)->data(ReportType::StudentProgress, ['academic_year_id' => $fixture['year']->id]);
    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($result['rows'])->toHaveCount(101)
        ->and($queryCount)->toBeLessThanOrEqual(15);
});
