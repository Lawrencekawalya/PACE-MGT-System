<?php

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\CurriculumRequirement;
use App\Models\Level;
use App\Models\Pace;
use App\Models\PaceAssignment;
use App\Models\Student;
use App\Models\StudentCourse;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Term;
use App\Notifications\StalePaceAssignmentNotification;
use App\PaceAssignmentStatus;
use App\RoleName;
use App\Services\PaceAssignmentService;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
});

function paceAssignmentFixture(): array
{
    $year = AcademicYear::factory()->create(['name' => '2026', 'is_active' => true, 'is_closed' => false]);
    $term = Term::factory()->create(['academic_year_id' => $year->id, 'is_active' => true, 'is_closed' => false]);
    $level = Level::factory()->create();
    $course = Course::factory()->create(['subject_id' => Subject::factory()]);
    $paces = collect(range(1, 4))->map(fn (int $position) => Pace::factory()->create([
        'course_id' => $course->id, 'number' => (string) (1100 + $position), 'sequence_order' => $position, 'is_active' => true,
    ]));
    $requirement = CurriculumRequirement::factory()->create(['level_id' => $level->id, 'course_id' => $course->id]);
    $requirement->paces()->attach($paces->mapWithKeys(fn (Pace $pace, int $position) => [$pace->id => ['sequence_order' => $position + 1]]));
    $student = Student::factory()->create();
    $enrollment = StudentEnrollment::factory()->create([
        'student_id' => $student->id, 'academic_year_id' => $year->id, 'term_id' => $term->id, 'level_id' => $level->id,
    ]);
    $studentCourse = StudentCourse::factory()->create([
        'student_enrollment_id' => $enrollment->id, 'course_id' => $course->id,
        'starting_pace_id' => $paces[1]->id, 'current_pace_id' => $paces[1]->id,
    ]);

    return compact('year', 'term', 'course', 'paces', 'student', 'studentCourse');
}

test('recommended PACE follows placement and curriculum sequence', function () {
    $fixture = paceAssignmentFixture();
    $teacher = createStaffWithRole(RoleName::Teacher);
    $service = app(PaceAssignmentService::class);

    expect($service->recommend($fixture['studentCourse'])->id)->toBe($fixture['paces'][1]->id);
    $assignment = $service->assign($fixture['studentCourse'], $fixture['paces'][1], $teacher);

    expect($assignment->status)->toBe(PaceAssignmentStatus::Assigned)
        ->and($assignment->statusEvents)->toHaveCount(1)
        ->and($assignment->statusEvents->first()->from_status)->toBeNull()
        ->and($service->recommend($fixture['studentCourse']))->toBeNull();
});

test('ordinary assignment rejects duplicates and out of sequence PACEs', function () {
    $fixture = paceAssignmentFixture();
    $teacher = createStaffWithRole(RoleName::Teacher);
    $service = app(PaceAssignmentService::class);

    expect(fn () => $service->assign($fixture['studentCourse'], $fixture['paces'][3], $teacher))
        ->toThrow(ValidationException::class);
    $service->assign($fixture['studentCourse'], $fixture['paces'][1], $teacher);
    expect(fn () => $service->assign($fixture['studentCourse'], $fixture['paces'][1], $teacher))
        ->toThrow(ValidationException::class);
});

test('administrator can authorize a documented sequence or duplicate exception', function () {
    $fixture = paceAssignmentFixture();
    $administrator = createStaffWithRole(RoleName::Administrator);
    $service = app(PaceAssignmentService::class);

    $first = $service->assign($fixture['studentCourse'], $fixture['paces'][3], $administrator, 'Diagnostic review requires an advanced starting point.');
    $second = $service->assign($fixture['studentCourse'], $fixture['paces'][3], $administrator, 'Replacement record approved during reconciliation.');

    expect($first->override_reason)->not->toBeNull()
        ->and($second->attempt_cycle)->toBe(2)
        ->and(PaceAssignment::query()->count())->toBe(2);
});

test('status service enforces transitions and records timestamps and history', function () {
    $fixture = paceAssignmentFixture();
    $administrator = createStaffWithRole(RoleName::Administrator);
    $service = app(PaceAssignmentService::class);
    $assignment = $service->assign($fixture['studentCourse'], $fixture['paces'][1], $administrator);

    expect(fn () => $service->transition($assignment, PaceAssignmentStatus::Passed, $administrator))
        ->toThrow(ValidationException::class);
    $assignment = $service->transition($assignment, PaceAssignmentStatus::InProgress, $administrator);
    $assignment = $service->transition($assignment, PaceAssignmentStatus::AwaitingSelfTest, $administrator);
    $assignment = $service->transition($assignment, PaceAssignmentStatus::AwaitingPaceTest, $administrator);
    $assignment = $service->transition($assignment, PaceAssignmentStatus::Passed, $administrator);

    expect($assignment->completed_at)->not->toBeNull()
        ->and($assignment->statusEvents()->count())->toBe(5)
        ->and($assignment->studentCourse->fresh()->current_pace_id)->toBe($fixture['paces'][2]->id);
});

test('cancellation requires a reason and remains in the timeline', function () {
    $fixture = paceAssignmentFixture();
    $teacher = createStaffWithRole(RoleName::Teacher);
    $service = app(PaceAssignmentService::class);
    $assignment = $service->assign($fixture['studentCourse'], $fixture['paces'][1], $teacher);

    expect(fn () => $service->transition($assignment, PaceAssignmentStatus::Cancelled, $teacher))
        ->toThrow(ValidationException::class);
    $cancelled = $service->transition($assignment, PaceAssignmentStatus::Cancelled, $teacher, 'Student transferred course.');

    expect($cancelled->cancelled_at)->not->toBeNull()
        ->and($cancelled->statusEvents()->orderByDesc('id')->first()->reason)->toBe('Student transferred course.');
});

test('approved full repeat closes the failed cycle and creates the next cycle', function () {
    $fixture = paceAssignmentFixture();
    $administrator = createStaffWithRole(RoleName::Administrator);
    $service = app(PaceAssignmentService::class);
    $assignment = $service->assign($fixture['studentCourse'], $fixture['paces'][1], $administrator);
    $assignment = $service->transition($assignment, PaceAssignmentStatus::InProgress, $administrator);
    $assignment = $service->transition($assignment, PaceAssignmentStatus::AwaitingSelfTest, $administrator);
    $assignment = $service->transition($assignment, PaceAssignmentStatus::AwaitingPaceTest, $administrator);
    $assignment = $service->transition($assignment, PaceAssignmentStatus::Failed, $administrator);

    $repeat = $service->reassign($assignment, $administrator, 'Supervisor approved a complete PACE repeat.');

    expect($assignment->fresh()->status)->toBe(PaceAssignmentStatus::Reassigned)
        ->and($assignment->fresh()->reassigned_at)->not->toBeNull()
        ->and($repeat->attempt_cycle)->toBe(2)
        ->and($repeat->status)->toBe(PaceAssignmentStatus::Assigned);
});

test('work queue supports status and exception filtering', function () {
    $fixture = paceAssignmentFixture();
    $administrator = createStaffWithRole(RoleName::Administrator);
    app(PaceAssignmentService::class)->assign($fixture['studentCourse'], $fixture['paces'][3], $administrator, 'Approved diagnostic exception.');

    $this->actingAs($administrator)->get(route('pace-assignments.index', ['exceptions' => 1]))
        ->assertOk()->assertInertia(fn ($page) => $page->component('pace-assignments/Index')->has('assignments.data', 1)->where('summary.exceptions', 1));
});

test('stale assignment command creates one database alert per recipient per day', function () {
    Notification::fake();
    $fixture = paceAssignmentFixture();
    $teacher = createStaffWithRole(RoleName::Teacher);
    $teacher->learningCenters()->attach($fixture['studentCourse']->enrollment->learning_center_id);
    $assignment = app(PaceAssignmentService::class)->assign($fixture['studentCourse'], $fixture['paces'][1], $teacher);
    $assignment->update(['assigned_at' => now()->subDays(15)]);

    $this->artisan('pace-assignments:notify-stale')->assertSuccessful();
    Notification::assertSentTo($teacher, StalePaceAssignmentNotification::class);
});
