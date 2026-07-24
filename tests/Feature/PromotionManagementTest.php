<?php

use App\EnrollmentStatus;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\CurriculumRequirement;
use App\Models\LearningCenter;
use App\Models\Level;
use App\Models\Pace;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Term;
use App\RoleName;
use App\StudentStatus;
use Database\Seeders\AccessControlSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
});

/** @return array<string, mixed> */
function promotionFixture(): array
{
    $administrator = createStaffWithRole(RoleName::Administrator);
    $sourceYear = AcademicYear::factory()->create([
        'name' => '2026',
        'starts_on' => '2026-01-01',
        'ends_on' => '2026-12-31',
        'is_active' => true,
        'is_closed' => false,
    ]);
    $sourceTerm = Term::factory()->create([
        'academic_year_id' => $sourceYear->id,
        'name' => 'Term 3',
        'sort_order' => 3,
        'starts_on' => '2026-09-01',
        'ends_on' => '2026-12-15',
        'is_active' => true,
        'is_closed' => false,
    ]);
    $targetYear = AcademicYear::factory()->create([
        'name' => '2027',
        'starts_on' => '2027-01-01',
        'ends_on' => '2027-12-31',
        'is_active' => false,
        'is_closed' => false,
    ]);
    $targetTerm = Term::factory()->create([
        'academic_year_id' => $targetYear->id,
        'name' => 'Term 1',
        'sort_order' => 1,
        'starts_on' => '2027-01-10',
        'ends_on' => '2027-04-30',
        'is_active' => false,
        'is_closed' => false,
    ]);
    $lowerCenter = LearningCenter::factory()->create(['name' => 'Lower Center', 'is_active' => true]);
    $middleCenter = LearningCenter::factory()->create(['name' => 'Middle Center', 'is_active' => true]);
    $gradeTwo = Level::factory()->create([
        'learning_center_id' => $lowerCenter->id,
        'name' => 'Grade 2',
        'code' => 'G2',
        'sort_order' => 2,
        'is_active' => true,
    ]);
    $gradeThree = Level::factory()->create([
        'learning_center_id' => $middleCenter->id,
        'name' => 'Grade 3',
        'code' => 'G3',
        'sort_order' => 3,
        'is_active' => true,
    ]);
    $subject = Subject::factory()->create();
    $courses = collect(['English', 'Mathematics'])->map(function (string $name, int $position) use ($subject, $gradeThree) {
        $course = Course::factory()->create(['subject_id' => $subject->id, 'name' => $name, 'is_active' => true]);
        $paces = collect(range(1, 2))->map(fn (int $offset) => Pace::factory()->create([
            'course_id' => $course->id,
            'number' => (string) (1030 + ($position * 10) + $offset),
            'sequence_order' => $offset,
            'is_active' => true,
        ]));
        $requirement = CurriculumRequirement::factory()->create([
            'level_id' => $gradeThree->id,
            'course_id' => $course->id,
            'sort_order' => $position + 1,
            'is_active' => true,
        ]);
        $requirement->paces()->attach(
            $paces->mapWithKeys(fn (Pace $pace, int $pacePosition) => [
                $pace->id => ['sequence_order' => $pacePosition + 1],
            ]),
        );

        return $course->load('paces');
    });
    $student = Student::factory()->registeredBy($administrator)->create();
    $sourceEnrollment = StudentEnrollment::factory()->create([
        'student_id' => $student->id,
        'learning_center_id' => $lowerCenter->id,
        'academic_year_id' => $sourceYear->id,
        'term_id' => $sourceTerm->id,
        'level_id' => $gradeTwo->id,
        'status' => EnrollmentStatus::Active,
        'enrolled_on' => '2026-01-15',
    ]);

    return compact(
        'administrator',
        'sourceYear',
        'sourceTerm',
        'targetYear',
        'targetTerm',
        'gradeTwo',
        'gradeThree',
        'courses',
        'student',
        'sourceEnrollment',
    );
}

test('only administrators can access and manage promotions', function () {
    $fixture = promotionFixture();
    $teacher = createStaffWithRole(RoleName::Teacher);
    $paceOfficer = createStaffWithRole(RoleName::PaceOfficer);

    $this->actingAs($fixture['administrator'])
        ->get(route('admin.promotions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/promotions/Index')
            ->where('filters.source_academic_year_id', $fixture['sourceYear']->id)
            ->where('filters.target_academic_year_id', $fixture['targetYear']->id)
            ->has('enrollments.data', 1));

    $this->actingAs($teacher)->get(route('admin.promotions.index'))->assertForbidden();
    $this->actingAs($paceOfficer)->get(route('admin.promotions.index'))->assertForbidden();
    $this->actingAs($teacher)->post(
        route('admin.promotions.store', $fixture['sourceEnrollment']),
        ['decision' => EnrollmentStatus::Transferred->value],
    )->assertForbidden();
});

test('administrator promotes a student into the next grade and prescribed curriculum', function () {
    $fixture = promotionFixture();

    $this->actingAs($fixture['administrator'])->post(
        route('admin.promotions.store', $fixture['sourceEnrollment']),
        [
            'decision' => EnrollmentStatus::Promoted->value,
            'target_academic_year_id' => $fixture['targetYear']->id,
            'target_term_id' => $fixture['targetTerm']->id,
            'target_level_id' => $fixture['gradeThree']->id,
            'reason' => 'Approved after the annual academic review.',
        ],
    )->assertRedirect();

    $source = $fixture['sourceEnrollment']->fresh();
    $next = StudentEnrollment::query()->where('previous_enrollment_id', $source->id)->sole();

    expect($source->status)->toBe(EnrollmentStatus::Promoted)
        ->and($source->decision_by)->toBe($fixture['administrator']->id)
        ->and($source->decision_at)->not->toBeNull()
        ->and($source->decision_reason)->toBe('Approved after the annual academic review.')
        ->and($next->student_id)->toBe($fixture['student']->id)
        ->and($next->academic_year_id)->toBe($fixture['targetYear']->id)
        ->and($next->term_id)->toBe($fixture['targetTerm']->id)
        ->and($next->level_id)->toBe($fixture['gradeThree']->id)
        ->and($next->learning_center_id)->toBe($fixture['gradeThree']->learning_center_id)
        ->and($next->enrolled_on->toDateString())->toBe('2027-01-10')
        ->and($next->studentCourses()->count())->toBe(2)
        ->and($next->studentCourses()->whereNull('starting_pace_id')->count())->toBe(0)
        ->and($fixture['student']->fresh()->status)->toBe(StudentStatus::Active);

    $this->assertDatabaseHas('activity_logs', [
        'event' => 'student-enrollment.promotion-decided',
        'subject_id' => $source->id,
        'user_id' => $fixture['administrator']->id,
    ]);
});

test('retention creates a linked enrollment in the same grade', function () {
    $fixture = promotionFixture();
    $subject = Subject::factory()->create();
    $course = Course::factory()->create(['subject_id' => $subject->id, 'is_active' => true]);
    $pace = Pace::factory()->create(['course_id' => $course->id, 'is_active' => true]);
    $requirement = CurriculumRequirement::factory()->create([
        'level_id' => $fixture['gradeTwo']->id,
        'course_id' => $course->id,
        'is_active' => true,
    ]);
    $requirement->paces()->attach($pace->id, ['sequence_order' => 1]);

    $this->actingAs($fixture['administrator'])->post(
        route('admin.promotions.store', $fixture['sourceEnrollment']),
        [
            'decision' => EnrollmentStatus::Retained->value,
            'target_academic_year_id' => $fixture['targetYear']->id,
            'target_term_id' => $fixture['targetTerm']->id,
            'target_level_id' => $fixture['gradeTwo']->id,
        ],
    )->assertRedirect();

    $next = StudentEnrollment::query()
        ->where('previous_enrollment_id', $fixture['sourceEnrollment']->id)
        ->sole();

    expect($fixture['sourceEnrollment']->fresh()->status)->toBe(EnrollmentStatus::Retained)
        ->and($next->level_id)->toBe($fixture['gradeTwo']->id)
        ->and($next->studentCourses()->sole()->starting_pace_id)->toBe($pace->id);
});

test('transfer and programme completion close the student without a next enrollment', function (EnrollmentStatus $decision, StudentStatus $studentStatus) {
    $fixture = promotionFixture();

    $this->actingAs($fixture['administrator'])->post(
        route('admin.promotions.store', $fixture['sourceEnrollment']),
        ['decision' => $decision->value, 'reason' => 'Final year-end decision.'],
    )->assertRedirect();

    expect($fixture['sourceEnrollment']->fresh()->status)->toBe($decision)
        ->and($fixture['sourceEnrollment']->nextEnrollment()->exists())->toBeFalse()
        ->and($fixture['student']->fresh()->status)->toBe($studentStatus);
})->with([
    'transferred' => [EnrollmentStatus::Transferred, StudentStatus::Withdrawn],
    'completed' => [EnrollmentStatus::Completed, StudentStatus::Graduated],
]);

test('promotion rejects skipped grades and repeated final decisions', function () {
    $fixture = promotionFixture();
    $upperCenter = LearningCenter::factory()->create(['is_active' => true]);
    $gradeFour = Level::factory()->create([
        'learning_center_id' => $upperCenter->id,
        'name' => 'Grade 4',
        'sort_order' => 4,
        'is_active' => true,
    ]);
    $payload = [
        'decision' => EnrollmentStatus::Promoted->value,
        'target_academic_year_id' => $fixture['targetYear']->id,
        'target_term_id' => $fixture['targetTerm']->id,
        'target_level_id' => $gradeFour->id,
    ];

    $this->actingAs($fixture['administrator'])
        ->post(route('admin.promotions.store', $fixture['sourceEnrollment']), $payload)
        ->assertSessionHasErrors('target_level_id');

    $payload['target_level_id'] = $fixture['gradeThree']->id;
    $this->actingAs($fixture['administrator'])
        ->post(route('admin.promotions.store', $fixture['sourceEnrollment']), $payload)
        ->assertRedirect();
    $this->actingAs($fixture['administrator'])
        ->post(route('admin.promotions.store', $fixture['sourceEnrollment']), $payload)
        ->assertSessionHasErrors('decision');

    expect(StudentEnrollment::query()->where('previous_enrollment_id', $fixture['sourceEnrollment']->id)->count())->toBe(1);
});
