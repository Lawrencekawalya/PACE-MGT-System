<?php

use App\Models\AcademicYear;
use App\Models\LearningCenter;
use App\Models\Level;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\RoleName;
use Database\Seeders\AccessControlSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
});

function studentForCenterTeacher($teacher): Student
{
    $center = LearningCenter::factory()->create();
    $center->teachers()->attach($teacher);
    $level = Level::factory()->create(['learning_center_id' => $center->id]);
    $year = AcademicYear::factory()->create(['is_active' => true]);
    $student = Student::factory()->registeredBy($teacher)->create();
    StudentEnrollment::factory()->create([
        'student_id' => $student->id,
        'learning_center_id' => $center->id,
        'level_id' => $level->id,
        'academic_year_id' => $year->id,
    ]);

    return $student;
}

test('teacher can access student registration and profiles', function () {
    $teacher = createStaffWithRole(RoleName::Teacher);
    $student = studentForCenterTeacher($teacher);

    $this->actingAs($teacher)->get(route('students.index'))->assertOk();
    $this->actingAs($teacher)->get(route('students.create'))->assertOk();
    $this->actingAs($teacher)->get(route('students.show', $student))->assertOk();
});

test('storekeeper cannot access student records or registration', function () {
    $storekeeper = createStaffWithRole(RoleName::PaceOfficer);
    $student = Student::factory()->create();

    $this->actingAs($storekeeper)->get(route('students.index'))->assertForbidden();
    $this->actingAs($storekeeper)->get(route('students.show', $student))->assertForbidden();
    $this->actingAs($storekeeper)->post(route('students.store'), [])->assertForbidden();
});

test('teacher only lists and manages students assigned to them', function () {
    $teacher = createStaffWithRole(RoleName::Teacher);
    $otherTeacher = createStaffWithRole(RoleName::Teacher);
    $owned = studentForCenterTeacher($teacher);
    AcademicYear::query()->update(['is_active' => false]);
    $other = studentForCenterTeacher($otherTeacher);
    $owned->enrollments()->first()->academicYear()->update(['is_active' => true]);

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
    $student = studentForCenterTeacher($teacher);

    $this->actingAs($administrator)->get(route('students.show', $student))->assertOk();
    $this->actingAs($administrator)->get(route('students.edit', $student))->assertOk();
});
