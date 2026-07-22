<?php

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\CurriculumRequirement;
use App\Models\Level;
use App\Models\Pace;
use App\Models\Student;
use App\Models\StudentCourse;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Term;
use App\RoleName;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
});

function enrollmentFixture(string $yearName = '2026'): array
{
    $year = AcademicYear::factory()->create(['name' => $yearName, 'starts_on' => "{$yearName}-01-01", 'ends_on' => "{$yearName}-12-31", 'is_active' => true]);
    $term = Term::factory()->create(['academic_year_id' => $year->id, 'name' => 'Term 1', 'sort_order' => 1, 'starts_on' => "{$yearName}-01-01", 'ends_on' => "{$yearName}-04-30", 'is_active' => true]);
    $level = Level::factory()->create();
    $subject = Subject::factory()->create();
    $courses = collect(['English', 'Mathematics'])->map(function (string $name, int $index) use ($subject, $level) {
        $course = Course::factory()->create(['subject_id' => $subject->id, 'name' => $name]);
        $paces = collect(range(1, 3))->map(fn (int $offset) => Pace::factory()->create([
            'course_id' => $course->id, 'number' => (string) (1000 + ($index * 20) + $offset), 'sequence_order' => 1000 + ($index * 20) + $offset,
        ]));
        $requirement = CurriculumRequirement::factory()->create(['level_id' => $level->id, 'course_id' => $course->id, 'sort_order' => $index + 1]);
        $requirement->paces()->attach($paces->mapWithKeys(fn (Pace $pace, int $position) => [$pace->id => ['sequence_order' => $position + 1]]));

        return $course->load('paces');
    });

    return compact('year', 'term', 'level', 'courses');
}

function enrollmentData(array $fixture, array $overrides = []): array
{
    return [
        'academic_year_id' => $fixture['year']->id, 'term_id' => $fixture['term']->id,
        'level_id' => $fixture['level']->id, 'enrolled_on' => $fixture['term']->starts_on->addWeek()->toDateString(),
        'curriculum_override_reason' => null,
        'courses' => $fixture['courses']->map(fn (Course $course, int $index) => [
            'course_id' => $course->id, 'starting_pace_id' => $course->paces[$index === 0 ? 0 : 2]->id, 'placement_reason' => null,
        ])->all(),
        ...$overrides,
    ];
}

test('student is independently placed in each prescribed course', function () {
    $teacher = createStaffWithRole(RoleName::Teacher);
    $student = Student::factory()->supervisedBy($teacher)->create();
    $fixture = enrollmentFixture();

    $this->actingAs($teacher)->post(route('students.enrollments.store', $student), enrollmentData($fixture))->assertRedirect();

    $placements = StudentCourse::query()->orderBy('course_id')->get();
    expect($placements)->toHaveCount(2)
        ->and($placements[0]->starting_pace_id)->not->toBe($placements[1]->starting_pace_id)
        ->and($placements[0]->current_pace_id)->toBe($placements[0]->starting_pace_id)
        ->and($placements[1]->current_pace_id)->toBe($placements[1]->starting_pace_id);
});

test('enrollment rejects a starting pace from another course', function () {
    $teacher = createStaffWithRole(RoleName::Teacher);
    $student = Student::factory()->supervisedBy($teacher)->create();
    $fixture = enrollmentFixture();
    $data = enrollmentData($fixture);
    $data['courses'][0]['starting_pace_id'] = $fixture['courses'][1]->paces[0]->id;

    $this->actingAs($teacher)->post(route('students.enrollments.store', $student), $data)->assertSessionHasErrors('courses');
});

test('curriculum additions or removals require an override reason', function () {
    $teacher = createStaffWithRole(RoleName::Teacher);
    $student = Student::factory()->supervisedBy($teacher)->create();
    $fixture = enrollmentFixture();
    $data = enrollmentData($fixture);
    array_pop($data['courses']);

    $this->actingAs($teacher)->post(route('students.enrollments.store', $student), $data)->assertSessionHasErrors('curriculum_override_reason');
    $data['curriculum_override_reason'] = 'Diagnostic review removed this course.';
    $this->actingAs($teacher)->post(route('students.enrollments.store', $student), $data)->assertRedirect();
});

test('editing placement retains removed course history as withdrawn', function () {
    $teacher = createStaffWithRole(RoleName::Teacher);
    $student = Student::factory()->supervisedBy($teacher)->create();
    $fixture = enrollmentFixture();
    $this->actingAs($teacher)->post(route('students.enrollments.store', $student), enrollmentData($fixture))->assertRedirect();
    $enrollment = $student->enrollments()->sole();
    $updated = enrollmentData($fixture, [
        'courses' => [enrollmentData($fixture)['courses'][0]],
        'curriculum_override_reason' => 'Course removed after diagnostic review.',
    ]);

    $this->actingAs($teacher)->put(route('students.enrollments.update', [$student, $enrollment]), $updated)->assertRedirect();

    expect($enrollment->studentCourses()->count())->toBe(2)
        ->and($enrollment->studentCourses()->where('status', 'withdrawn')->count())->toBe(1)
        ->and($enrollment->studentCourses()->where('status', 'active')->count())->toBe(1);
});

test('enrollment history is preserved across academic years and duplicates are rejected', function () {
    $teacher = createStaffWithRole(RoleName::Teacher);
    $student = Student::factory()->supervisedBy($teacher)->create();
    $first = enrollmentFixture('2025');
    $second = enrollmentFixture('2026');

    $this->actingAs($teacher)->post(route('students.enrollments.store', $student), enrollmentData($first))->assertRedirect();
    $this->actingAs($teacher)->post(route('students.enrollments.store', $student), enrollmentData($second))->assertRedirect();
    $this->actingAs($teacher)->post(route('students.enrollments.store', $student), enrollmentData($second))->assertSessionHasErrors('academic_year_id');

    expect($student->enrollments()->count())->toBe(2)
        ->and(StudentEnrollment::query()->where('academic_year_id', $first['year']->id)->exists())->toBeTrue();
});

test('students with enrollment history cannot be destructively deleted', function () {
    $administrator = createStaffWithRole(RoleName::Administrator);
    $student = Student::factory()->create();
    $fixture = enrollmentFixture();
    StudentEnrollment::factory()->create(['student_id' => $student->id, 'academic_year_id' => $fixture['year']->id, 'term_id' => $fixture['term']->id, 'level_id' => $fixture['level']->id]);

    $this->actingAs($administrator)->delete("/students/{$student->id}")->assertMethodNotAllowed();
    expect(fn () => $student->delete())->toThrow(QueryException::class);
});
