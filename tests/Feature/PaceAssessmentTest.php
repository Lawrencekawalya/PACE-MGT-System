<?php

use App\AssessmentOutcome;
use App\AssessmentType;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Pace;
use App\Models\PaceAssignment;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentCourse;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Term;
use App\Notifications\PaceRetryApprovalRequestedNotification;
use App\PaceAssignmentStatus;
use App\RetryApprovalStatus;
use App\RoleName;
use App\Services\PaceAssessmentService;
use App\Services\PaceAssignmentService;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
});

function assessmentFixture(): array
{
    AcademicYear::query()->update(['is_active' => false]);
    Term::query()->update(['is_active' => false]);
    $year = AcademicYear::factory()->create(['is_active' => true, 'is_closed' => false]);
    $term = Term::factory()->create(['academic_year_id' => $year->id, 'is_active' => true, 'is_closed' => false]);
    $course = Course::factory()->create(['subject_id' => Subject::factory()]);
    $paces = collect([1, 2])->map(fn (int $position) => Pace::factory()->create([
        'course_id' => $course->id, 'number' => (string) (1200 + $position), 'sequence_order' => $position,
    ]));
    $teacher = createStaffWithRole(RoleName::Teacher);
    $student = Student::factory()->supervisedBy($teacher)->create();
    $enrollment = StudentEnrollment::factory()->create(['student_id' => $student->id, 'academic_year_id' => $year->id, 'term_id' => $term->id]);
    $studentCourse = StudentCourse::factory()->create([
        'student_enrollment_id' => $enrollment->id, 'course_id' => $course->id,
        'starting_pace_id' => $paces[0]->id, 'current_pace_id' => $paces[0]->id,
    ]);
    $assignmentService = app(PaceAssignmentService::class);
    $assignment = $assignmentService->assign($studentCourse, $paces[0], $teacher);
    $assignment = $assignmentService->transition($assignment, PaceAssignmentStatus::InProgress, $teacher);
    $assignment = $assignmentService->transition($assignment, PaceAssignmentStatus::AwaitingSelfTest, $teacher);

    return compact('year', 'term', 'course', 'paces', 'studentCourse', 'teacher', 'assignment');
}

test('self test pass boundary snapshots the configured rule and advances readiness', function () {
    $failed = assessmentFixture();
    $service = app(PaceAssessmentService::class);
    $failure = $service->finalize($failed['assignment'], AssessmentType::SelfTest, 79.99, null, $failed['teacher']);

    expect($failure->outcome)->toBe(AssessmentOutcome::Failed)
        ->and($failure->pass_mark_used)->toBe('80.00')
        ->and($failed['assignment']->fresh()->status)->toBe(PaceAssignmentStatus::InProgress);

    $passed = assessmentFixture();
    $success = $service->finalize($passed['assignment'], AssessmentType::SelfTest, 80, 'Ready for final test.', $passed['teacher']);
    expect($success->outcome)->toBe(AssessmentOutcome::Passed)
        ->and($passed['assignment']->fresh()->status)->toBe(PaceAssignmentStatus::AwaitingPaceTest);
});

test('PACE Test uses its configured threshold and preserves failed history', function () {
    $fixture = assessmentFixture();
    SchoolSetting::current()->update(['pace_test_pass_mark' => 85]);
    $service = app(PaceAssessmentService::class);
    $service->finalize($fixture['assignment'], AssessmentType::SelfTest, 90, null, $fixture['teacher']);
    $final = $service->finalize($fixture['assignment']->fresh(), AssessmentType::PaceTest, 84.99, null, $fixture['teacher']);

    expect($final->outcome)->toBe(AssessmentOutcome::Failed)
        ->and($final->pass_mark_used)->toBe('85.00')
        ->and($fixture['assignment']->fresh()->status)->toBe(PaceAssignmentStatus::Failed)
        ->and($fixture['assignment']->attempts()->count())->toBe(2);
});

test('ordinary Self Test retry requires approval and retains both attempts', function () {
    Notification::fake();
    $fixture = assessmentFixture();
    $service = app(PaceAssessmentService::class);
    $assignmentService = app(PaceAssignmentService::class);
    $service->finalize($fixture['assignment'], AssessmentType::SelfTest, 60, null, $fixture['teacher']);
    $approval = $service->requestRetry($fixture['assignment']->fresh(), AssessmentType::SelfTest, 'Student reviewed the missed work.', $fixture['teacher']);
    Notification::assertSentTo($fixture['teacher'], PaceRetryApprovalRequestedNotification::class);

    expect(fn () => $assignmentService->transition($fixture['assignment']->fresh(), PaceAssignmentStatus::AwaitingSelfTest, $fixture['teacher']))
        ->toThrow(ValidationException::class);

    $service->decideRetry($approval, RetryApprovalStatus::Approved, 'Review completed with supervisor.', $fixture['teacher']);
    $assignmentService->transition($fixture['assignment']->fresh(), PaceAssignmentStatus::AwaitingSelfTest, $fixture['teacher']);
    $second = $service->finalize($fixture['assignment']->fresh(), AssessmentType::SelfTest, 90, null, $fixture['teacher']);
    expect($second->attempt_number)->toBe(2)
        ->and($second->approved_by)->toBe($fixture['teacher']->id)
        ->and($fixture['assignment']->attempts()->where('assessment_type', AssessmentType::SelfTest)->count())->toBe(2);
});

test('attempt beyond configured Self Test limit requires administrator decision', function () {
    $fixture = assessmentFixture();
    SchoolSetting::current()->update(['self_test_retry_limit' => 1]);
    $service = app(PaceAssessmentService::class);
    $service->finalize($fixture['assignment'], AssessmentType::SelfTest, 40, null, $fixture['teacher']);
    $approval = $service->requestRetry($fixture['assignment']->fresh(), AssessmentType::SelfTest, 'Additional supervised review requested.', $fixture['teacher']);

    expect($approval->is_over_limit)->toBeTrue()
        ->and(fn () => $service->decideRetry($approval, RetryApprovalStatus::Approved, 'Teacher approval.', $fixture['teacher']))
        ->toThrow(ValidationException::class);

    $administrator = createStaffWithRole(RoleName::Administrator);
    expect($service->decideRetry($approval, RetryApprovalStatus::Approved, 'Administrator approved exceptional retry.', $administrator)->status)
        ->toBe(RetryApprovalStatus::Approved);
});

test('test-only retest keeps the assignment cycle and physical issue unchanged', function () {
    $fixture = assessmentFixture();
    $service = app(PaceAssessmentService::class);
    $service->finalize($fixture['assignment'], AssessmentType::SelfTest, 90, null, $fixture['teacher']);
    $service->finalize($fixture['assignment']->fresh(), AssessmentType::PaceTest, 50, null, $fixture['teacher']);
    $originalIssue = $fixture['assignment']->issued_at;
    $approval = $service->requestRetry($fixture['assignment']->fresh(), AssessmentType::PaceTest, 'Test-only retest requested.', $fixture['teacher']);
    $service->decideRetry($approval, RetryApprovalStatus::Approved, 'Retest approved after review.', $fixture['teacher']);
    $second = $service->finalize($fixture['assignment']->fresh(), AssessmentType::PaceTest, 90, null, $fixture['teacher']);

    expect($second->attempt_number)->toBe(2)
        ->and(PaceAssignment::query()->count())->toBe(1)
        ->and($fixture['assignment']->fresh()->attempt_cycle)->toBe(1)
        ->and($fixture['assignment']->fresh()->issued_at?->toDateTimeString())->toBe($originalIssue?->toDateTimeString())
        ->and($fixture['studentCourse']->fresh()->current_pace_id)->toBe($fixture['paces'][1]->id);
});

test('finalized result correction appends evidence and updates the effective state', function () {
    $fixture = assessmentFixture();
    $service = app(PaceAssessmentService::class);
    $service->finalize($fixture['assignment'], AssessmentType::SelfTest, 90, null, $fixture['teacher']);
    $attempt = $service->finalize($fixture['assignment']->fresh(), AssessmentType::PaceTest, 70, null, $fixture['teacher']);
    $administrator = createStaffWithRole(RoleName::Administrator);
    $correction = $service->correct($attempt, 90, 'Marker transposed the score during entry.', $administrator);

    expect($attempt->fresh()->score)->toBe('70.00')
        ->and($attempt->fresh()->outcome)->toBe(AssessmentOutcome::Failed)
        ->and($correction->outcome)->toBe(AssessmentOutcome::Passed)
        ->and($fixture['assignment']->fresh()->status)->toBe(PaceAssignmentStatus::Passed);
});

test('double finalization creates only one attempt', function () {
    $fixture = assessmentFixture();
    $service = app(PaceAssessmentService::class);
    $service->finalize($fixture['assignment'], AssessmentType::SelfTest, 80, null, $fixture['teacher']);

    expect(fn () => $service->finalize($fixture['assignment']->fresh(), AssessmentType::SelfTest, 90, null, $fixture['teacher']))
        ->toThrow(ValidationException::class)
        ->and($fixture['assignment']->attempts()->count())->toBe(1);
});

test('assessment queue returns waiting tests and pending approvals', function () {
    $fixture = assessmentFixture();

    $this->actingAs($fixture['teacher'])->get(route('assessments.index'))
        ->assertOk()->assertInertia(fn ($page) => $page->component('assessments/Index')->has('assignments.data', 1)->has('approvals', 0));
});
