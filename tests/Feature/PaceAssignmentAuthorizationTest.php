<?php

use App\Models\InventoryItem;
use App\Models\PaceAssignment;
use App\Models\User;
use App\RoleName;
use App\Services\PaceAssignmentService;
use App\Services\StockLedgerService;
use App\StockMovementType;
use Database\Seeders\AccessControlSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
});

test('teacher can assign but storekeeper cannot create academic assignments', function () {
    $fixture = paceAssignmentFixture();
    $teacher = createStaffWithRole(RoleName::Teacher);
    $fixture['student']->update(['registered_by' => $teacher->id]);
    $fixture['studentCourse']->enrollment->learningCenter->teachers()->attach($teacher);
    $storekeeper = createStaffWithRole(RoleName::PaceOfficer);
    $data = ['student_course_id' => $fixture['studentCourse']->id, 'pace_id' => $fixture['paces'][1]->id];

    $this->actingAs($storekeeper)->post(route('pace-assignments.store'), $data)->assertForbidden();
    $this->actingAs($teacher)->post(route('pace-assignments.store'), $data)->assertRedirect();
});

test('storekeeper can physically issue while teacher cannot', function () {
    $fixture = paceAssignmentFixture();
    $teacher = createStaffWithRole(RoleName::Teacher);
    $fixture['student']->update(['registered_by' => $teacher->id]);
    $fixture['studentCourse']->enrollment->learningCenter->teachers()->attach($teacher);
    $storekeeper = createStaffWithRole(RoleName::PaceOfficer);
    $assignment = app(PaceAssignmentService::class)->assign($fixture['studentCourse'], $fixture['paces'][1], $teacher);
    $item = InventoryItem::query()->where('pace_id', $fixture['paces'][1]->id)->sole();
    app(StockLedgerService::class)->postManual($item, StockMovementType::Receipt, 1, 'AUTH-ISSUE-001', null, $storekeeper);

    $this->actingAs($teacher)->put(route('pace-assignments.status.update', $assignment), ['status' => 'in_progress'])->assertForbidden();
    $this->actingAs($storekeeper)->put(route('pace-assignments.status.update', $assignment), ['status' => 'in_progress'])->assertRedirect();

    expect(PaceAssignment::query()->find($assignment->id)->issued_by)->toBe($storekeeper->id);
});

test('users without academic or issue permissions cannot view the work queue', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('pace-assignments.index'))->assertForbidden();
});
