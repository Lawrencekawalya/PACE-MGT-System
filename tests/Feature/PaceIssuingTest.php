<?php

use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\InventoryItem;
use App\Models\LearningCenter;
use App\Models\Level;
use App\Models\Pace;
use App\Models\PaceAccountTransaction;
use App\Models\PaceAssignment;
use App\Models\StockMovement;
use App\Models\Student;
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
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
});

/** @return array<string, mixed> */
function paceIssuingFixture(): array
{
    AcademicYear::query()->update(['is_active' => false]);
    Term::query()->update(['is_active' => false]);
    $year = AcademicYear::factory()->create(['name' => '2026', 'is_active' => true, 'is_closed' => false]);
    $term = Term::factory()->create(['academic_year_id' => $year->id, 'name' => 'Term Two', 'is_active' => true, 'is_closed' => false]);
    $lowerCenter = LearningCenter::factory()->create(['name' => 'Lower Learning Centre', 'code' => 'LOWER']);
    $upperCenter = LearningCenter::factory()->create(['name' => 'Upper Learning Centre', 'code' => 'UPPER']);
    $lowerLevel = Level::factory()->create(['learning_center_id' => $lowerCenter->id, 'name' => 'Grade 3', 'sort_order' => 3]);
    $upperLevel = Level::factory()->create(['learning_center_id' => $upperCenter->id, 'name' => 'Grade 8', 'sort_order' => 8]);
    $science = Course::factory()->create(['subject_id' => Subject::factory(), 'name' => 'Science']);
    $math = Course::factory()->create(['subject_id' => Subject::factory(), 'name' => 'Mathematics']);
    $sciencePace = Pace::factory()->create(['course_id' => $science->id, 'number' => '1008', 'title' => 'Astronomy']);
    $mathPace = Pace::factory()->create(['course_id' => $math->id, 'number' => '1010', 'title' => 'Whole Numbers']);
    $teacher = createStaffWithRole(RoleName::Teacher);
    $officer = createStaffWithRole(RoleName::PaceOfficer);

    $createAssignment = function (
        string $firstName,
        string $lastName,
        string $admissionNumber,
        LearningCenter $center,
        Level $level,
        Course $course,
        Pace $pace,
    ) use ($year, $term, $teacher): PaceAssignment {
        $student = Student::factory()->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'admission_number' => $admissionNumber,
        ]);
        $enrollment = StudentEnrollment::factory()->create([
            'student_id' => $student->id,
            'learning_center_id' => $center->id,
            'level_id' => $level->id,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
        ]);
        $studentCourse = StudentCourse::factory()->create([
            'student_enrollment_id' => $enrollment->id,
            'course_id' => $course->id,
            'starting_pace_id' => $pace->id,
            'current_pace_id' => $pace->id,
        ]);

        return app(PaceAssignmentService::class)->assign($studentCourse, $pace, $teacher);
    };

    $aminaScience = $createAssignment('Amina', 'Nabirye', 'FICA-001', $lowerCenter, $lowerLevel, $science, $sciencePace);
    $benScience = $createAssignment('Ben', 'Okello', 'FICA-002', $upperCenter, $upperLevel, $science, $sciencePace);
    $claireMath = $createAssignment('Claire', 'Auma', 'FICA-003', $lowerCenter, $lowerLevel, $math, $mathPace);
    $scienceItem = InventoryItem::query()->where('pace_id', $sciencePace->id)->sole();
    $mathItem = InventoryItem::query()->where('pace_id', $mathPace->id)->sole();
    $stock = app(StockLedgerService::class);
    $stock->postManual($scienceItem, StockMovementType::Receipt, 2, 'DEL-SCIENCE', null, $officer);
    $stock->postManual($mathItem, StockMovementType::Receipt, 1, 'DEL-MATH', null, $officer);
    $term->update(['pace_cost' => 10000]);
    collect([$aminaScience, $benScience, $claireMath])
        ->map(fn (PaceAssignment $assignment): int => $assignment->studentCourse->enrollment->student_id)
        ->unique()
        ->each(fn (int $studentId) => PaceAccountTransaction::factory()->create([
            'student_id' => $studentId,
            'type' => PaceAccountTransactionType::Payment,
            'amount' => '50000.00',
            'balance_after' => '50000.00',
            'recorded_by' => $officer->id,
        ]));

    return compact(
        'lowerCenter',
        'upperCenter',
        'lowerLevel',
        'sciencePace',
        'mathPace',
        'teacher',
        'officer',
        'aminaScience',
        'benScience',
        'claireMath',
        'scienceItem',
        'mathItem',
    );
}

test('the issuing workspace filters assigned PACEs by learning centre PACE and student', function () {
    $fixture = paceIssuingFixture();

    $this->actingAs($fixture['officer'])
        ->get(route('pace-issuing.index', [
            'mode' => 'center',
            'learning_center_id' => $fixture['lowerCenter']->id,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('pace-issuing/Index')
            ->where('filters.mode', 'center')
            ->has('assignments.data', 2));

    $this->actingAs($fixture['officer'])
        ->get(route('pace-issuing.index', ['mode' => 'pace', 'search' => '1010']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.mode', 'pace')
            ->has('assignments.data', 1)
            ->where('assignments.data.0.id', $fixture['claireMath']->id)
            ->where('assignments.data.0.inventory.on_hand', 1));

    $this->actingAs($fixture['officer'])
        ->get(route('pace-issuing.index', ['mode' => 'student', 'search' => 'Amina']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.mode', 'student')
            ->has('assignments.data', 1)
            ->where('assignments.data.0.id', $fixture['aminaScience']->id));
});

test('a PACE Officer can atomically issue a mixed PACE selection', function () {
    $fixture = paceIssuingFixture();

    $this->actingAs($fixture['officer'])
        ->post(route('pace-issuing.store'), [
            'assignment_ids' => [$fixture['aminaScience']->id, $fixture['claireMath']->id],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($fixture['aminaScience']->fresh()->status)->toBe(PaceAssignmentStatus::InProgress)
        ->and($fixture['claireMath']->fresh()->status)->toBe(PaceAssignmentStatus::InProgress)
        ->and($fixture['aminaScience']->fresh()->issued_by)->toBe($fixture['officer']->id)
        ->and($fixture['scienceItem']->onHand())->toBe(1)
        ->and($fixture['mathItem']->onHand())->toBe(0)
        ->and(StockMovement::query()->where('type', StockMovementType::Issue)->count())->toBe(2)
        ->and(ActivityLog::query()->where('event', 'pace-assignment.status-changed')->count())->toBe(2);
});

test('an aggregate stock shortage rolls back the whole selection', function () {
    $fixture = paceIssuingFixture();
    app(StockLedgerService::class)->postManual(
        $fixture['scienceItem'],
        StockMovementType::Loss,
        1,
        null,
        'One copy was damaged before distribution.',
        $fixture['officer'],
    );

    $this->actingAs($fixture['officer'])
        ->post(route('pace-issuing.store'), [
            'assignment_ids' => [$fixture['aminaScience']->id, $fixture['benScience']->id],
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('stock');

    expect($fixture['aminaScience']->fresh()->status)->toBe(PaceAssignmentStatus::Assigned)
        ->and($fixture['benScience']->fresh()->status)->toBe(PaceAssignmentStatus::Assigned)
        ->and($fixture['scienceItem']->onHand())->toBe(1)
        ->and(StockMovement::query()->where('type', StockMovementType::Issue)->count())->toBe(0);
});

test('a stale assignment rolls back every other selected issue', function () {
    $fixture = paceIssuingFixture();
    app(PaceIssueService::class)->issue($fixture['aminaScience'], $fixture['officer']);

    $this->actingAs($fixture['officer'])
        ->post(route('pace-issuing.store'), [
            'assignment_ids' => [$fixture['aminaScience']->id, $fixture['claireMath']->id],
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('assignment_ids');

    expect($fixture['claireMath']->fresh()->status)->toBe(PaceAssignmentStatus::Assigned)
        ->and($fixture['mathItem']->onHand())->toBe(1)
        ->and(StockMovement::query()->where('type', StockMovementType::Issue)->count())->toBe(1);
});

test('staff without issue permission cannot access or submit the issuing workspace', function () {
    $fixture = paceIssuingFixture();

    $this->actingAs($fixture['teacher'])
        ->get(route('pace-issuing.index'))
        ->assertForbidden();

    $this->actingAs($fixture['teacher'])
        ->post(route('pace-issuing.store'), [
            'assignment_ids' => [$fixture['aminaScience']->id],
        ])
        ->assertForbidden();
});
