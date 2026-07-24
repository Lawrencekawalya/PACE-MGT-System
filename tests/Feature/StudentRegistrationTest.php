<?php

use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\LearningCenter;
use App\Models\Level;
use App\Models\Student;
use App\Models\StudentCourse;
use App\Models\StudentEnrollment;
use App\Models\Term;
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

function activeRegistrationPeriod(): array
{
    $year = AcademicYear::factory()->create([
        'is_active' => true,
        'starts_on' => now()->startOfYear(),
        'ends_on' => now()->endOfYear(),
    ]);
    $term = Term::factory()->create([
        'academic_year_id' => $year->id,
        'is_active' => true,
        'starts_on' => now()->subMonth(),
        'ends_on' => now()->addMonth(),
    ]);

    return compact('year', 'term');
}

test('authorized staff register students with unique generated admission numbers', function () {
    $teacher = createStaffWithRole(RoleName::Teacher);
    activeRegistrationPeriod();
    $center = LearningCenter::factory()->create();
    $center->teachers()->attach($teacher);
    $level = Level::factory()->create(['learning_center_id' => $center->id]);

    $this->actingAs($teacher)->post(route('students.store'), validStudentData(['level_id' => $level->id]))->assertRedirect();
    $this->actingAs($teacher)->post(route('students.store'), validStudentData(['level_id' => $level->id, 'first_name' => 'John']))->assertRedirect();

    $numbers = Student::query()->orderBy('id')->pluck('admission_number');
    expect($numbers)->toHaveCount(2)
        ->and($numbers[0])->toMatch('/^FICA-\d{4}-\d{6}$/')
        ->and($numbers[0])->not->toBe($numbers[1])
        ->and(Student::query()->where('registered_by', $teacher->id)->count())->toBe(2)
        ->and(StudentEnrollment::query()->where('learning_center_id', $center->id)->count())->toBe(2)
        ->and(ActivityLog::query()->where('event', 'student.created')->count())->toBe(2);
});

test('administrator registers a student into a configured grade without direct teacher assignment', function () {
    $administrator = createStaffWithRole(RoleName::Administrator);
    activeRegistrationPeriod();
    $center = LearningCenter::factory()->create();
    $level = Level::factory()->create(['learning_center_id' => $center->id]);

    $this->actingAs($administrator)->post(route('students.store'), validStudentData([
        'level_id' => $level->id,
    ]))->assertRedirect();

    $student = Student::query()->sole();
    expect($student->registered_by)->toBe($administrator->id)
        ->and($student->activeEnrollment()->sole()->learning_center_id)->toBe($center->id);

    $this->actingAs($administrator)->put(route('students.update', $student), validStudentData([
        'first_name' => 'Updated',
    ]))->assertRedirect();

    expect($student->fresh()->first_name)->toBe('Updated')
        ->and($student->fresh()->registered_by)->toBe($administrator->id);
});

test('teacher cannot register into a grade managed by another center', function () {
    $teacher = createStaffWithRole(RoleName::Teacher);
    activeRegistrationPeriod();
    $otherCenter = LearningCenter::factory()->create();
    $level = Level::factory()->create(['learning_center_id' => $otherCenter->id]);

    $this->actingAs($teacher)->post(route('students.store'), validStudentData([
        'level_id' => $level->id,
    ]))->assertSessionHasErrors('level_id');
});

test('student registration validates identity and birth date without guardian contact details', function () {
    $teacher = createStaffWithRole(RoleName::Teacher);
    activeRegistrationPeriod();
    $center = LearningCenter::factory()->create();
    $center->teachers()->attach($teacher);
    $level = Level::factory()->create(['learning_center_id' => $center->id]);

    $this->actingAs($teacher)->post(route('students.store'), validStudentData([
        'level_id' => $level->id,
        'first_name' => '',
        'guardian_name' => null,
        'guardian_phone' => null,
        'date_of_birth' => now()->addDay()->toDateString(),
    ]))->assertSessionHasErrors(['first_name', 'date_of_birth'])
        ->assertSessionDoesntHaveErrors(['guardian_name', 'guardian_phone']);
});

test('student status changes require reasons and are audited', function () {
    $teacher = createStaffWithRole(RoleName::Teacher);
    $student = Student::factory()->supervisedBy($teacher)->create();
    $year = AcademicYear::factory()->create(['is_active' => true]);
    $enrollment = StudentEnrollment::factory()->create(['student_id' => $student->id, 'academic_year_id' => $year->id]);
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
    $year = AcademicYear::factory()->create(['is_active' => true]);
    $level = Level::factory()->create();
    $student = Student::factory()->supervisedBy($teacher)->create(['first_name' => 'UniqueStudent']);
    StudentEnrollment::factory()->create(['student_id' => $student->id, 'academic_year_id' => $year->id, 'level_id' => $level->id]);
    Student::factory()->create();

    $this->actingAs($teacher)->get(route('students.index', ['search' => 'UniqueStudent', 'academic_year_id' => $year->id, 'level_id' => $level->id]))
        ->assertOk()->assertInertia(fn (Assert $page) => $page->component('students/Index')->has('students.data', 1)->where('students.data.0.id', $student->id));
});
