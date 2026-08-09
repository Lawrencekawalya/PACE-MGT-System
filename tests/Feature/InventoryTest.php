<?php

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\InventoryItem;
use App\Models\Pace;
use App\Models\PaceAccountTransaction;
use App\Models\StockMovement;
use App\Models\StudentCourse;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Term;
use App\PaceAccountTransactionType;
use App\PaceAssignmentStatus;
use App\RoleName;
use App\Services\PaceAssignmentService;
use App\Services\PaceIssueService;
use App\Services\StockLedgerService;
use App\StockMovementType;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
});

function inventoryAssignmentFixture(): array
{
    AcademicYear::query()->update(['is_active' => false]);
    Term::query()->update(['is_active' => false]);
    $year = AcademicYear::factory()->create(['is_active' => true, 'is_closed' => false]);
    $term = Term::factory()->create(['academic_year_id' => $year->id, 'is_active' => true, 'is_closed' => false]);
    $course = Course::factory()->create(['subject_id' => Subject::factory()]);
    $pace = Pace::factory()->create(['course_id' => $course->id, 'number' => '1301']);
    $enrollment = StudentEnrollment::factory()->create(['academic_year_id' => $year->id, 'term_id' => $term->id]);
    $studentCourse = StudentCourse::factory()->create([
        'student_enrollment_id' => $enrollment->id, 'course_id' => $course->id,
        'starting_pace_id' => $pace->id, 'current_pace_id' => $pace->id,
    ]);
    $teacher = createStaffWithRole(RoleName::Teacher);
    $storekeeper = createStaffWithRole(RoleName::PaceOfficer);
    $assignment = app(PaceAssignmentService::class)->assign($studentCourse, $pace, $teacher);
    $item = InventoryItem::query()->where('pace_id', $pace->id)->sole();
    $term->update(['pace_cost' => 10000]);
    PaceAccountTransaction::factory()->create([
        'student_id' => $enrollment->student_id,
        'type' => PaceAccountTransactionType::Payment,
        'amount' => '50000.00',
        'balance_after' => '50000.00',
        'recorded_by' => $storekeeper->id,
    ]);

    return compact('year', 'term', 'pace', 'enrollment', 'studentCourse', 'teacher', 'storekeeper', 'assignment', 'item');
}

test('all manual movement types calculate a reconciled balance', function () {
    $item = InventoryItem::factory()->create();
    $storekeeper = createStaffWithRole(RoleName::PaceOfficer);
    $stock = app(StockLedgerService::class);

    expect($stock->postManual($item, StockMovementType::Receipt, 10, 'DEL-100', null, $storekeeper)->balance_after)->toBe(10)
        ->and($stock->postManual($item, StockMovementType::Damage, 2, null, 'Water damage.', $storekeeper)->balance_after)->toBe(8)
        ->and($stock->postManual($item, StockMovementType::Loss, 1, null, 'Missing during count.', $storekeeper)->balance_after)->toBe(7)
        ->and($stock->postManual($item, StockMovementType::Adjustment, 3, null, 'Opening count reconciliation.', $storekeeper)->balance_after)->toBe(10)
        ->and($item->onHand())->toBe(10)
        ->and($item->movements()->sum('quantity'))->toBe(10);
});

test('negative stock and zero movements are rejected', function () {
    $item = InventoryItem::factory()->create();
    $storekeeper = createStaffWithRole(RoleName::PaceOfficer);
    $stock = app(StockLedgerService::class);

    expect(fn () => $stock->postManual($item, StockMovementType::Loss, 1, null, 'Missing.', $storekeeper))
        ->toThrow(ValidationException::class)
        ->and(fn () => $stock->postManual($item, StockMovementType::Adjustment, 0, null, 'No change.', $storekeeper))
        ->toThrow(ValidationException::class)
        ->and($item->movements()->count())->toBe(0);
});

test('physical issue deducts one copy and records student period and assignment', function () {
    $fixture = inventoryAssignmentFixture();
    app(StockLedgerService::class)->postManual($fixture['item'], StockMovementType::Receipt, 2, 'DEL-101', null, $fixture['storekeeper']);
    $issued = app(PaceIssueService::class)->issue($fixture['assignment'], $fixture['storekeeper']);
    $movement = StockMovement::query()->where('type', StockMovementType::Issue)->sole();

    expect($issued->status)->toBe(PaceAssignmentStatus::InProgress)
        ->and($fixture['item']->onHand())->toBe(1)
        ->and($movement->quantity)->toBe(-1)
        ->and($movement->pace_assignment_id)->toBe($fixture['assignment']->id)
        ->and($movement->student_id)->toBe($fixture['enrollment']->student_id)
        ->and($movement->academic_year_id)->toBe($fixture['year']->id)
        ->and($movement->term_id)->toBe($fixture['term']->id);
});

test('the last copy cannot be issued to two assignments', function () {
    $fixture = inventoryAssignmentFixture();
    app(StockLedgerService::class)->postManual($fixture['item'], StockMovementType::Receipt, 1, 'DEL-102', null, $fixture['storekeeper']);
    $secondEnrollment = StudentEnrollment::factory()->create(['academic_year_id' => $fixture['year']->id, 'term_id' => $fixture['term']->id, 'level_id' => $fixture['enrollment']->level_id]);
    $secondCourse = StudentCourse::factory()->create(['student_enrollment_id' => $secondEnrollment->id, 'course_id' => $fixture['studentCourse']->course_id, 'starting_pace_id' => $fixture['pace']->id, 'current_pace_id' => $fixture['pace']->id]);
    $second = app(PaceAssignmentService::class)->assign($secondCourse, $fixture['pace'], $fixture['teacher']);
    PaceAccountTransaction::factory()->create([
        'student_id' => $secondEnrollment->student_id,
        'type' => PaceAccountTransactionType::Payment,
        'amount' => '10000.00',
        'balance_after' => '10000.00',
        'recorded_by' => $fixture['storekeeper']->id,
    ]);

    app(PaceIssueService::class)->issue($fixture['assignment'], $fixture['storekeeper']);
    expect(fn () => app(PaceIssueService::class)->issue($second, $fixture['storekeeper']))
        ->toThrow(ValidationException::class)
        ->and($fixture['item']->onHand())->toBe(0)
        ->and(StockMovement::query()->where('type', StockMovementType::Issue)->count())->toBe(1)
        ->and($second->fresh()->status)->toBe(PaceAssignmentStatus::Assigned);
});

test('cancelling an issued assignment does not restore stock', function () {
    $fixture = inventoryAssignmentFixture();
    app(StockLedgerService::class)->postManual($fixture['item'], StockMovementType::Receipt, 1, 'DEL-103', null, $fixture['storekeeper']);
    $issued = app(PaceIssueService::class)->issue($fixture['assignment'], $fixture['storekeeper']);
    app(PaceAssignmentService::class)->transition($issued, PaceAssignmentStatus::Cancelled, $fixture['teacher'], 'Course placement changed.');

    expect($fixture['item']->onHand())->toBe(0)
        ->and($fixture['item']->movements()->where('type', StockMovementType::Correction)->exists())->toBeFalse();
});

test('issuing a full PACE repeat consumes a new copy', function () {
    $fixture = inventoryAssignmentFixture();
    $assignments = app(PaceAssignmentService::class);
    app(StockLedgerService::class)->postManual($fixture['item'], StockMovementType::Receipt, 2, 'DEL-REPEAT-001', null, $fixture['storekeeper']);
    $first = app(PaceIssueService::class)->issue($fixture['assignment'], $fixture['storekeeper']);
    $first = $assignments->transition($first, PaceAssignmentStatus::AwaitingSelfTest, $fixture['teacher']);
    $first = $assignments->transition($first, PaceAssignmentStatus::AwaitingPaceTest, $fixture['teacher']);
    $first = $assignments->transition($first, PaceAssignmentStatus::Failed, $fixture['teacher']);
    $repeat = $assignments->reassign($first, $fixture['teacher'], 'Student must repeat the full PACE.');

    expect($fixture['item']->onHand())->toBe(1)
        ->and($fixture['item']->movements()->where('type', StockMovementType::Issue)->count())->toBe(1)
        ->and($repeat->attempt_cycle)->toBe(2);

    app(PaceIssueService::class)->issue($repeat, $fixture['storekeeper']);

    expect($fixture['item']->onHand())->toBe(0)
        ->and($fixture['item']->movements()->where('type', StockMovementType::Issue)->count())->toBe(2);
});

test('reversing an issue appends a correction and reopens the assignment for issue', function () {
    $fixture = inventoryAssignmentFixture();
    $stock = app(StockLedgerService::class);
    $stock->postManual($fixture['item'], StockMovementType::Receipt, 1, 'DEL-104', null, $fixture['storekeeper']);
    app(PaceIssueService::class)->issue($fixture['assignment'], $fixture['storekeeper']);
    $issue = StockMovement::query()->where('type', StockMovementType::Issue)->sole();
    $correction = app(PaceIssueService::class)->reverse($issue, 'Issued against the wrong student record.', $fixture['storekeeper']);

    expect($correction->type)->toBe(StockMovementType::Correction)
        ->and($correction->corrects_movement_id)->toBe($issue->id)
        ->and($fixture['item']->onHand())->toBe(1)
        ->and($fixture['assignment']->fresh()->status)->toBe(PaceAssignmentStatus::Assigned)
        ->and($fixture['assignment']->fresh()->issued_at)->toBeNull();
});

test('receipt reversal is rejected when it would make current stock negative', function () {
    $fixture = inventoryAssignmentFixture();
    $receipt = app(StockLedgerService::class)->postManual($fixture['item'], StockMovementType::Receipt, 1, 'DEL-105', null, $fixture['storekeeper']);
    app(PaceIssueService::class)->issue($fixture['assignment'], $fixture['storekeeper']);

    expect(fn () => app(StockLedgerService::class)->correct($receipt, 'Delivery was entered twice.', $fixture['storekeeper']))
        ->toThrow(ValidationException::class)
        ->and(StockMovement::query()->where('type', StockMovementType::Correction)->count())->toBe(0);
});

test('posted movements cannot be edited or deleted', function () {
    $movement = StockMovement::factory()->create();

    expect(fn () => $movement->update(['quantity' => 99]))->toThrow(LogicException::class)
        ->and(fn () => $movement->delete())->toThrow(LogicException::class);
});

test('inventory low stock filter uses current ledger balance and reorder level', function () {
    $storekeeper = createStaffWithRole(RoleName::PaceOfficer);
    $item = InventoryItem::factory()->create(['reorder_level' => 3]);
    app(StockLedgerService::class)->postManual($item, StockMovementType::Receipt, 2, 'DEL-106', null, $storekeeper);

    $this->actingAs($storekeeper)->get(route('inventory.index', ['stock' => 'low']))
        ->assertOk()->assertInertia(fn ($page) => $page->component('inventory/Index')->where('summary.low_stock', 1)->has('items.data', 1));
});
