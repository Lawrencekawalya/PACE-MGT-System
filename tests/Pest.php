<?php

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\CurriculumRequirement;
use App\Models\Level;
use App\Models\Pace;
use App\Models\PaceAssignment;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentCourse;
use App\Models\StudentEnrollment;
use App\Models\Term;
use App\Models\User;
use App\PaceAssignmentStatus;
use App\RoleName;
use App\StudentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function createStaffWithRole(RoleName $role, array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->roles()->attach(Role::query()->where('name', $role->value)->sole());

    return $user;
}

/** @return array<string, mixed> */
function createReportFixture(): array
{
    AcademicYear::query()->update(['is_active' => false]);
    Term::query()->update(['is_active' => false]);
    $year = AcademicYear::factory()->create(['name' => '2026', 'is_active' => true, 'is_closed' => false]);
    $term = Term::factory()->create(['academic_year_id' => $year->id, 'name' => 'Term 1', 'is_active' => true, 'is_closed' => false]);
    $level = Level::factory()->create(['name' => 'Grade 1']);
    $course = Course::factory()->create(['name' => 'Mathematics']);
    $paces = collect([1001, 1002, 1003])->map(fn (int $number, int $index) => Pace::factory()->create([
        'course_id' => $course->id, 'number' => (string) $number, 'sequence_order' => $index + 1,
    ]));
    $requirement = CurriculumRequirement::factory()->create([
        'level_id' => $level->id, 'course_id' => $course->id, 'is_active' => true,
    ]);
    $requirement->paces()->attach($paces->mapWithKeys(fn ($pace, int $index) => [$pace->id => ['sequence_order' => $index + 1]]));
    $student = Student::factory()->create([
        'admission_number' => 'FICA-0001', 'first_name' => 'Grace', 'last_name' => 'Auma', 'status' => StudentStatus::Active,
    ]);
    $enrollment = StudentEnrollment::factory()->create([
        'student_id' => $student->id, 'academic_year_id' => $year->id,
        'term_id' => $term->id, 'level_id' => $level->id, 'enrolled_on' => now()->subDays(40),
    ]);
    $studentCourse = StudentCourse::factory()->create([
        'student_enrollment_id' => $enrollment->id, 'course_id' => $course->id,
        'starting_pace_id' => $paces[0]->id, 'current_pace_id' => $paces[1]->id,
    ]);
    $teacher = createStaffWithRole(RoleName::Teacher);
    $passed = PaceAssignment::factory()->create([
        'student_course_id' => $studentCourse->id, 'pace_id' => $paces[0]->id,
        'academic_year_id' => $year->id, 'term_id' => $term->id,
        'status' => PaceAssignmentStatus::Passed, 'assigned_by' => $teacher->id,
        'assigned_at' => now()->subDays(35), 'completed_at' => now()->subDays(25),
    ]);
    $active = PaceAssignment::factory()->create([
        'student_course_id' => $studentCourse->id, 'pace_id' => $paces[1]->id,
        'academic_year_id' => $year->id, 'term_id' => $term->id,
        'status' => PaceAssignmentStatus::InProgress, 'assigned_by' => $teacher->id,
        'assigned_at' => now()->subDays(20), 'issued_at' => now()->subDays(20), 'started_at' => now()->subDays(20),
    ]);

    return compact('year', 'term', 'level', 'course', 'paces', 'student', 'enrollment', 'studentCourse', 'teacher', 'passed', 'active');
}
