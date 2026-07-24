<?php

use App\Models\AcademicYear;
use App\Models\LearningCenter;
use App\Models\Level;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Term;
use App\RoleName;
use Database\Seeders\AccessControlSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);

    $year = AcademicYear::factory()->create([
        'is_active' => true,
        'starts_on' => now()->startOfYear(),
        'ends_on' => now()->endOfYear(),
    ]);
    Term::factory()->create([
        'academic_year_id' => $year->id,
        'is_active' => true,
        'starts_on' => now()->subMonth(),
        'ends_on' => now()->addMonth(),
    ]);
});

function learningCenterStudentData(int $levelId, array $overrides = []): array
{
    return [
        'level_id' => $levelId,
        'first_name' => 'Grace',
        'last_name' => 'Nabirye',
        'guardian_name' => 'Sarah Nabirye',
        'guardian_phone' => '+256700000000',
        ...$overrides,
    ];
}

test('teacher registration is limited to grades in assigned learning centers', function () {
    $teacher = createStaffWithRole(RoleName::Teacher);
    $ownCenter = LearningCenter::factory()->create(['name' => 'Lower Center']);
    $otherCenter = LearningCenter::factory()->create(['name' => 'Upper Center']);
    $ownCenter->teachers()->attach($teacher);
    $ownGrade = Level::factory()->create(['learning_center_id' => $ownCenter->id]);
    $otherGrade = Level::factory()->create(['learning_center_id' => $otherCenter->id]);

    $this->actingAs($teacher)->get(route('students.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('grades', 1)
            ->where('grades.0.id', $ownGrade->id)
            ->where('grades.0.learning_center.id', $ownCenter->id));

    $this->actingAs($teacher)->post(
        route('students.store'),
        learningCenterStudentData($otherGrade->id),
    )->assertSessionHasErrors('level_id');

    $this->actingAs($teacher)->post(
        route('students.store'),
        learningCenterStudentData($ownGrade->id),
    )->assertRedirect();

    $student = Student::query()->sole();
    $enrollment = $student->activeEnrollment()->sole();

    expect($student->registered_by)->toBe($teacher->id)
        ->and($enrollment->level_id)->toBe($ownGrade->id)
        ->and($enrollment->learning_center_id)->toBe($ownCenter->id);
});

test('teacher sees all students in assigned centers regardless of registrar', function () {
    $administrator = createStaffWithRole(RoleName::Administrator);
    $teacher = createStaffWithRole(RoleName::Teacher);
    $otherTeacher = createStaffWithRole(RoleName::Teacher);
    $center = LearningCenter::factory()->create();
    $otherCenter = LearningCenter::factory()->create();
    $center->teachers()->attach($teacher);
    $otherCenter->teachers()->attach($otherTeacher);
    $grade = Level::factory()->create(['learning_center_id' => $center->id]);
    $otherGrade = Level::factory()->create(['learning_center_id' => $otherCenter->id]);
    $year = AcademicYear::query()->where('is_active', true)->sole();
    $term = Term::query()->where('is_active', true)->sole();
    $visible = Student::factory()->registeredBy($administrator)->create();
    $hidden = Student::factory()->registeredBy($otherTeacher)->create();
    StudentEnrollment::factory()->create([
        'student_id' => $visible->id,
        'learning_center_id' => $center->id,
        'level_id' => $grade->id,
        'academic_year_id' => $year->id,
        'term_id' => $term->id,
    ]);
    StudentEnrollment::factory()->create([
        'student_id' => $hidden->id,
        'learning_center_id' => $otherCenter->id,
        'level_id' => $otherGrade->id,
        'academic_year_id' => $year->id,
        'term_id' => $term->id,
    ]);

    $this->actingAs($teacher)->get(route('students.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('students.data', 1)
            ->where('students.data.0.id', $visible->id));
    $this->actingAs($teacher)->get(route('students.show', $visible))->assertOk();
    $this->actingAs($teacher)->get(route('students.show', $hidden))->assertForbidden();
});

test('administrator registration may use any grade assigned to an active center', function () {
    $administrator = createStaffWithRole(RoleName::Administrator);
    $center = LearningCenter::factory()->create();
    $grade = Level::factory()->create(['learning_center_id' => $center->id]);

    $this->actingAs($administrator)->post(
        route('students.store'),
        learningCenterStudentData($grade->id),
    )->assertRedirect();

    expect(Student::query()->sole()->activeEnrollment()->sole()->learning_center_id)->toBe($center->id);
});
