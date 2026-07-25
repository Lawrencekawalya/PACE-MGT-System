<?php

use App\Models\ActivityLog;
use App\Models\Pace;
use App\Models\PaceAssignment;
use App\Models\TuitionClearance;
use App\Models\TuitionClearanceEvent;
use App\PaceAssignmentStatus;
use App\RoleName;
use App\Services\PaceAssignmentService;
use App\Services\TuitionClearanceService;
use App\TuitionClearanceStatus;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
});

test('Accountant sees the active term roster and can filter by school structure', function () {
    $fixture = createReportFixture();
    $accountant = createStaffWithRole(RoleName::Accountant);

    $this->actingAs($accountant)
        ->get(route('tuition-clearances.index', [
            'learning_center_id' => $fixture['enrollment']->learning_center_id,
            'level_id' => $fixture['level']->id,
            'status' => TuitionClearanceStatus::Unconfirmed->value,
            'search' => 'FICA-0001',
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tuition-clearances/Index')
            ->where('summary.students', 1)
            ->where('summary.unconfirmed', 1)
            ->where('target', 4)
            ->has('enrollments.data', 1)
            ->where('enrollments.data.0.student.admission_number', 'FICA-0001')
            ->where('enrollments.data.0.clearance.status', TuitionClearanceStatus::Unconfirmed->value)
            ->where('enrollments.data.0.course_progress.0.completed', 1));
});

test('Accountant records auditable term clearance without financial amounts', function () {
    $fixture = createReportFixture();
    $accountant = createStaffWithRole(RoleName::Accountant);

    $this->actingAs($accountant)
        ->put(route('tuition-clearances.update', $fixture['enrollment']), [
            'term_id' => $fixture['term']->id,
            'status' => TuitionClearanceStatus::PartiallyPaid->value,
            'reference' => 'RCT-2026-104',
            'notes' => 'Term clearance reviewed.',
        ])
        ->assertRedirect();

    $clearance = TuitionClearance::query()->sole();
    expect($clearance->status)->toBe(TuitionClearanceStatus::PartiallyPaid)
        ->and($clearance->recorded_by)->toBe($accountant->id)
        ->and($clearance->reference)->toBe('RCT-2026-104')
        ->and(TuitionClearanceEvent::query()->count())->toBe(1)
        ->and(ActivityLog::query()->where('event', 'tuition-clearance.recorded')->exists())->toBeTrue();

    $this->actingAs($accountant)
        ->put(route('tuition-clearances.update', $fixture['enrollment']), [
            'term_id' => $fixture['term']->id,
            'status' => TuitionClearanceStatus::FullyPaid->value,
            'reference' => 'RCT-2026-205',
            'notes' => 'Full-term clearance confirmed.',
        ])
        ->assertRedirect();

    expect($clearance->fresh()->status)->toBe(TuitionClearanceStatus::FullyPaid)
        ->and(TuitionClearanceEvent::query()->count())->toBe(2)
        ->and(TuitionClearanceEvent::query()->latest('id')->first()->from_status)
        ->toBe(TuitionClearanceStatus::PartiallyPaid);
});

test('Teacher and PACE Officer cannot manage tuition clearance', function (RoleName $role) {
    $fixture = createReportFixture();
    $staff = createStaffWithRole($role);

    $this->actingAs($staff)
        ->get(route('tuition-clearances.index'))
        ->assertForbidden();
    $this->actingAs($staff)
        ->put(route('tuition-clearances.update', $fixture['enrollment']), [
            'term_id' => $fixture['term']->id,
            'status' => TuitionClearanceStatus::FullyPaid->value,
        ])
        ->assertForbidden();
})->with([
    'teacher' => RoleName::Teacher,
    'PACE Officer' => RoleName::PaceOfficer,
]);

test('additional PACE requires full tuition clearance after the subject target', function () {
    $fixture = createReportFixture();
    $accountant = createStaffWithRole(RoleName::Accountant);
    $fixture['active']->update([
        'status' => PaceAssignmentStatus::Passed,
        'completed_at' => now(),
    ]);
    $assignmentWithinTarget = app(PaceAssignmentService::class)->assign(
        $fixture['studentCourse'],
        $fixture['paces'][2],
        $fixture['teacher'],
    );
    expect($assignmentWithinTarget->status)->toBe(PaceAssignmentStatus::Assigned);
    $assignmentWithinTarget->update([
        'status' => PaceAssignmentStatus::Passed,
        'completed_at' => now(),
    ]);

    $extraPassedPace = Pace::factory()->create([
        'course_id' => $fixture['course']->id,
        'number' => '1004',
        'sequence_order' => 4,
    ]);
    $nextPace = Pace::factory()->create([
        'course_id' => $fixture['course']->id,
        'number' => '1005',
        'sequence_order' => 5,
    ]);
    $requirement = $fixture['level']->curriculumRequirements()
        ->where('course_id', $fixture['course']->id)
        ->sole();
    $requirement->paces()->attach([
        $extraPassedPace->id => ['sequence_order' => 4],
        $nextPace->id => ['sequence_order' => 5],
    ]);
    PaceAssignment::factory()->create([
        'student_course_id' => $fixture['studentCourse']->id,
        'pace_id' => $extraPassedPace->id,
        'academic_year_id' => $fixture['year']->id,
        'term_id' => $fixture['term']->id,
        'status' => PaceAssignmentStatus::Passed,
        'completed_at' => now(),
    ]);

    expect(fn () => app(PaceAssignmentService::class)->assign(
        $fixture['studentCourse'],
        $nextPace,
        $fixture['teacher'],
    ))->toThrow(ValidationException::class, 'requires full tuition clearance');

    app(TuitionClearanceService::class)->record(
        $fixture['enrollment'],
        $fixture['term'],
        TuitionClearanceStatus::PartiallyPaid,
        'RCT-PARTIAL',
        null,
        $accountant,
    );

    expect(fn () => app(PaceAssignmentService::class)->assign(
        $fixture['studentCourse'],
        $nextPace,
        $fixture['teacher'],
    ))->toThrow(ValidationException::class, 'requires full tuition clearance');

    app(TuitionClearanceService::class)->record(
        $fixture['enrollment'],
        $fixture['term'],
        TuitionClearanceStatus::FullyPaid,
        'RCT-FULL',
        null,
        $accountant,
    );
    $assignment = app(PaceAssignmentService::class)->assign(
        $fixture['studentCourse'],
        $nextPace,
        $fixture['teacher'],
    );

    expect($assignment->pace_id)->toBe($nextPace->id)
        ->and($assignment->status)->toBe(PaceAssignmentStatus::Assigned);
});
