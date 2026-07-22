<?php

use App\PaceAssignmentStatus;
use App\RoleName;
use Database\Seeders\AccessControlSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
});

test('teacher academic queues dashboards and reports only include supervised students', function () {
    $fixture = createReportFixture();
    $otherTeacher = createStaffWithRole(RoleName::Teacher);
    $fixture['active']->update([
        'status' => PaceAssignmentStatus::AwaitingSelfTest,
        'submitted_at' => now()->subDays(4),
    ]);

    $this->actingAs($otherTeacher)->get(route('pace-assignments.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('assignments.data', 0));
    $this->actingAs($otherTeacher)->get(route('assessments.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('assignments.data', 0));
    $this->actingAs($otherTeacher)->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('academic.metrics.active_students', 0)
            ->where('academic.metrics.pending_tests', 0));
    $this->actingAs($otherTeacher)->get(route('reports.index', [
        'report_type' => 'student_progress',
        'academic_year_id' => $fixture['year']->id,
    ]))->assertOk()->assertInertia(fn (Assert $page) => $page->where('summary.records', 0));
});

test('teacher cannot access or modify another teachers academic records directly', function () {
    $fixture = createReportFixture();
    $otherTeacher = createStaffWithRole(RoleName::Teacher);

    $this->actingAs($otherTeacher)->get(route('pace-assignments.show', $fixture['active']))->assertForbidden();
    $this->actingAs($otherTeacher)->post(route('students.enrollments.store', $fixture['student']), [])->assertForbidden();
    $this->actingAs($otherTeacher)->put(route('pace-assignments.status.update', $fixture['active']), [
        'status' => PaceAssignmentStatus::AwaitingSelfTest->value,
    ])->assertForbidden();
    $this->actingAs($otherTeacher)->post(route('pace-assignments.attempts.store', $fixture['active']), [
        'assessment_type' => 'self_test', 'score' => 90,
    ])->assertForbidden();
});
