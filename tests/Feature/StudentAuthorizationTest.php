<?php

use App\Models\Student;
use App\RoleName;
use Database\Seeders\AccessControlSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
});

test('teacher can access student registration and profiles', function () {
    $teacher = createStaffWithRole(RoleName::Teacher);
    $student = Student::factory()->supervisedBy($teacher)->create();

    $this->actingAs($teacher)->get(route('students.index'))->assertOk();
    $this->actingAs($teacher)->get(route('students.create'))->assertOk();
    $this->actingAs($teacher)->get(route('students.show', $student))->assertOk();
});

test('storekeeper cannot access student records or registration', function () {
    $storekeeper = createStaffWithRole(RoleName::Storekeeper);
    $student = Student::factory()->create();

    $this->actingAs($storekeeper)->get(route('students.index'))->assertForbidden();
    $this->actingAs($storekeeper)->get(route('students.show', $student))->assertForbidden();
    $this->actingAs($storekeeper)->post(route('students.store'), [])->assertForbidden();
});

test('teacher only lists and manages students assigned to them', function () {
    $teacher = createStaffWithRole(RoleName::Teacher);
    $otherTeacher = createStaffWithRole(RoleName::Teacher);
    $owned = Student::factory()->supervisedBy($teacher)->create();
    $other = Student::factory()->supervisedBy($otherTeacher)->create();

    $this->actingAs($teacher)->get(route('students.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('students.data', 1)
            ->where('students.data.0.id', $owned->id));
    $this->actingAs($teacher)->get(route('students.show', $other))->assertForbidden();
    $this->actingAs($teacher)->get(route('students.edit', $other))->assertForbidden();
    $this->actingAs($teacher)->put(route('students.status.update', $other), [
        'status' => 'withdrawn', 'reason' => 'Unauthorized change.',
    ])->assertForbidden();
});

test('administrator can manage students assigned to any teacher', function () {
    $administrator = createStaffWithRole(RoleName::Administrator);
    $teacher = createStaffWithRole(RoleName::Teacher);
    $student = Student::factory()->supervisedBy($teacher)->create();

    $this->actingAs($administrator)->get(route('students.show', $student))->assertOk();
    $this->actingAs($administrator)->get(route('students.edit', $student))->assertOk();
});
