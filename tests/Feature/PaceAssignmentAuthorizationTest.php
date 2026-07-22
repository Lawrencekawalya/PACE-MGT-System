<?php

use App\Models\PaceAssignment;
use App\Models\User;
use App\RoleName;
use App\Services\PaceAssignmentService;
use Database\Seeders\AccessControlSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
});

test('teacher can assign but storekeeper cannot create academic assignments', function () {
    $fixture = paceAssignmentFixture();
    $teacher = createStaffWithRole(RoleName::Teacher);
    $storekeeper = createStaffWithRole(RoleName::Storekeeper);
    $data = ['student_course_id' => $fixture['studentCourse']->id, 'pace_id' => $fixture['paces'][1]->id];

    $this->actingAs($storekeeper)->post(route('pace-assignments.store'), $data)->assertForbidden();
    $this->actingAs($teacher)->post(route('pace-assignments.store'), $data)->assertRedirect();
});

test('storekeeper can physically issue while teacher cannot', function () {
    $fixture = paceAssignmentFixture();
    $teacher = createStaffWithRole(RoleName::Teacher);
    $storekeeper = createStaffWithRole(RoleName::Storekeeper);
    $assignment = app(PaceAssignmentService::class)->assign($fixture['studentCourse'], $fixture['paces'][1], $teacher);

    $this->actingAs($teacher)->put(route('pace-assignments.status.update', $assignment), ['status' => 'in_progress'])->assertForbidden();
    $this->actingAs($storekeeper)->put(route('pace-assignments.status.update', $assignment), ['status' => 'in_progress'])->assertRedirect();

    expect(PaceAssignment::query()->find($assignment->id)->issued_by)->toBe($storekeeper->id);
});

test('users without academic or issue permissions cannot view the work queue', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('pace-assignments.index'))->assertForbidden();
});
