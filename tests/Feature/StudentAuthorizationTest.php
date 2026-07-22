<?php

use App\Models\Student;
use App\RoleName;
use Database\Seeders\AccessControlSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
});

test('teacher can access student registration and profiles', function () {
    $teacher = createStaffWithRole(RoleName::Teacher);
    $student = Student::factory()->create();

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
