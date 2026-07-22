<?php

use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\Level;
use App\Models\Student;
use App\Models\StudentCourse;
use App\Models\StudentEnrollment;
use App\RoleName;
use Database\Seeders\AccessControlSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
});

function validStudentData(array $overrides = []): array
{
    return [
        'first_name' => 'Grace', 'last_name' => 'Nabirye', 'other_names' => null,
        'date_of_birth' => '2013-06-12', 'gender' => 'female',
        'guardian_name' => 'Sarah Nabirye', 'guardian_phone' => '+256700000000',
        'guardian_email' => 'sarah@example.com', 'notes' => 'Diagnostic placement required.',
        ...$overrides,
    ];
}

test('authorized staff register students with unique generated admission numbers', function () {
    $teacher = createStaffWithRole(RoleName::Teacher);

    $this->actingAs($teacher)->post(route('students.store'), validStudentData())->assertRedirect();
    $this->actingAs($teacher)->post(route('students.store'), validStudentData(['first_name' => 'John']))->assertRedirect();

    $numbers = Student::query()->orderBy('id')->pluck('admission_number');
    expect($numbers)->toHaveCount(2)
        ->and($numbers[0])->toMatch('/^FICA-\d{4}-\d{6}$/')
        ->and($numbers[0])->not->toBe($numbers[1])
        ->and(ActivityLog::query()->where('event', 'student.created')->count())->toBe(2);
});

test('student registration validates identity guardian and birth date', function () {
    $teacher = createStaffWithRole(RoleName::Teacher);
    $this->actingAs($teacher)->post(route('students.store'), validStudentData([
        'first_name' => '', 'guardian_phone' => '', 'date_of_birth' => now()->addDay()->toDateString(),
    ]))->assertSessionHasErrors(['first_name', 'guardian_phone', 'date_of_birth']);
});

test('student status changes require reasons and are audited', function () {
    $teacher = createStaffWithRole(RoleName::Teacher);
    $student = Student::factory()->create();
    $enrollment = StudentEnrollment::factory()->create(['student_id' => $student->id]);
    $placement = StudentCourse::factory()->create(['student_enrollment_id' => $enrollment->id]);

    $this->actingAs($teacher)->put(route('students.status.update', $student), ['status' => 'withdrawn'])->assertSessionHasErrors('reason');
    $this->actingAs($teacher)->put(route('students.status.update', $student), ['status' => 'withdrawn', 'reason' => 'Family relocated.'])->assertRedirect();

    expect($student->fresh()->status->value)->toBe('withdrawn')
        ->and($enrollment->fresh()->status->value)->toBe('withdrawn')
        ->and($placement->fresh()->status->value)->toBe('withdrawn')
        ->and(ActivityLog::query()->where('event', 'student.status-changed')->where('reason', 'Family relocated.')->exists())->toBeTrue();
});

test('student list searches and filters by academic year and level', function () {
    $teacher = createStaffWithRole(RoleName::Teacher);
    $year = AcademicYear::factory()->create();
    $level = Level::factory()->create();
    $student = Student::factory()->create(['first_name' => 'UniqueStudent']);
    StudentEnrollment::factory()->create(['student_id' => $student->id, 'academic_year_id' => $year->id, 'level_id' => $level->id]);
    Student::factory()->create();

    $this->actingAs($teacher)->get(route('students.index', ['search' => 'UniqueStudent', 'academic_year_id' => $year->id, 'level_id' => $level->id]))
        ->assertOk()->assertInertia(fn (Assert $page) => $page->component('students/Index')->has('students.data', 1)->where('students.data.0.id', $student->id));
});
