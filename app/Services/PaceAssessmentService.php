<?php

namespace App\Services;

use App\AssessmentOutcome;
use App\AssessmentType;
use App\Models\PaceAssignment;
use App\Models\PaceAttempt;
use App\Models\PaceAttemptCorrection;
use App\Models\PaceRetryApproval;
use App\Models\SchoolSetting;
use App\Models\User;
use App\NotificationCategory;
use App\NotificationPriority;
use App\Notifications\OperationalNotification;
use App\PaceAssignmentStatus;
use App\PermissionName;
use App\RetryApprovalStatus;
use App\RoleName;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaceAssessmentService
{
    public function __construct(
        private PaceAssignmentService $assignments,
        private NotificationRecipientService $recipients,
        private NotificationDispatcher $notifications,
    ) {}

    public function finalize(PaceAssignment $assignment, AssessmentType $type, float $score, ?string $notes, User $actor): PaceAttempt
    {
        return DB::transaction(function () use ($assignment, $type, $score, $notes, $actor): PaceAttempt {
            $assignment = PaceAssignment::query()->lockForUpdate()->findOrFail($assignment->id);
            $requiredStatus = $type === AssessmentType::SelfTest
                ? PaceAssignmentStatus::AwaitingSelfTest : PaceAssignmentStatus::AwaitingPaceTest;
            if ($assignment->status !== $requiredStatus) {
                throw ValidationException::withMessages(['assessment_type' => "This assignment is not awaiting a {$type->label()}."]);
            }

            $attemptNumber = $assignment->attempts()->where('assessment_type', $type)->count() + 1;
            $approval = null;
            if ($attemptNumber > 1) {
                $approval = $assignment->retryApprovals()
                    ->where('assessment_type', $type)
                    ->where('attempt_number', $attemptNumber)
                    ->where('status', RetryApprovalStatus::Approved)
                    ->first();
                if ($approval === null) {
                    throw ValidationException::withMessages(['assessment_type' => 'This retry must be approved before its result is recorded.']);
                }
            }

            $settings = SchoolSetting::current();
            $passMark = (float) ($type === AssessmentType::SelfTest ? $settings->self_test_pass_mark : $settings->pace_test_pass_mark);
            $outcome = $score >= $passMark ? AssessmentOutcome::Passed : AssessmentOutcome::Failed;
            $attempt = $assignment->attempts()->create([
                'assessment_type' => $type, 'attempt_number' => $attemptNumber,
                'score' => $score, 'pass_mark_used' => $passMark, 'outcome' => $outcome,
                'notes' => filled($notes) ? trim($notes) : null,
                'recorded_by' => $actor->id, 'recorded_at' => now(),
                'approved_by' => $approval?->decided_by, 'approved_at' => $approval?->decided_at,
                'approval_reason' => $approval?->decision_reason, 'finalized_at' => now(),
            ]);

            $to = match ([$type, $outcome]) {
                [AssessmentType::SelfTest, AssessmentOutcome::Passed] => PaceAssignmentStatus::AwaitingPaceTest,
                [AssessmentType::SelfTest, AssessmentOutcome::Failed] => PaceAssignmentStatus::InProgress,
                [AssessmentType::PaceTest, AssessmentOutcome::Passed] => PaceAssignmentStatus::Passed,
                [AssessmentType::PaceTest, AssessmentOutcome::Failed] => PaceAssignmentStatus::Failed,
            };
            $this->assignments->transition($assignment, $to, $actor, "{$type->label()} attempt {$attemptNumber}: {$outcome->value} at {$score}%.");

            return $attempt;
        }, 3);
    }

    public function requestRetry(PaceAssignment $assignment, AssessmentType $type, string $reason, User $actor): PaceRetryApproval
    {
        return DB::transaction(function () use ($assignment, $type, $reason, $actor): PaceRetryApproval {
            $assignment = PaceAssignment::query()->lockForUpdate()->findOrFail($assignment->id);
            $latest = $assignment->attempts()->where('assessment_type', $type)->latest('attempt_number')->first();
            if ($latest === null || $this->effectiveOutcome($latest) !== AssessmentOutcome::Failed) {
                throw ValidationException::withMessages(['assessment_type' => 'A retry can be requested only after a failed attempt.']);
            }
            $expectedStatus = $type === AssessmentType::SelfTest ? PaceAssignmentStatus::InProgress : PaceAssignmentStatus::Failed;
            if ($assignment->status !== $expectedStatus) {
                throw ValidationException::withMessages(['assessment_type' => 'The assignment is not eligible for this retry.']);
            }

            $nextAttempt = $latest->attempt_number + 1;
            $isOverLimit = $type === AssessmentType::SelfTest && $nextAttempt > SchoolSetting::current()->self_test_retry_limit;
            $approval = $assignment->retryApprovals()->firstOrNew([
                'assessment_type' => $type, 'attempt_number' => $nextAttempt,
            ]);
            if ($approval->exists && $approval->status !== RetryApprovalStatus::Rejected) {
                throw ValidationException::withMessages(['assessment_type' => 'A retry request for this attempt already exists.']);
            }
            $approval->fill([
                'status' => RetryApprovalStatus::Pending, 'is_over_limit' => $isOverLimit,
                'requested_by' => $actor->id, 'requested_at' => now(), 'request_reason' => trim($reason),
                'decided_by' => null, 'decided_at' => null, 'decision_reason' => null,
            ])->save();

            $assignment->loadMissing('studentCourse.enrollment.student', 'pace');
            $recipients = $isOverLimit
                ? $this->recipients->withRole(RoleName::Administrator)
                : $this->recipients->forLearningCenter(
                    (int) $assignment->studentCourse->enrollment->learning_center_id,
                    PermissionName::ApproveRetests,
                );
            $student = $assignment->studentCourse->enrollment->student;
            $this->notifications->send($recipients, new OperationalNotification(
                'Assessment retry requires approval',
                "{$type->label()} retry {$nextAttempt} for {$student->full_name} requires a decision.",
                route('assessments.index', ['approvals' => 'pending']),
                NotificationCategory::Academic,
                NotificationPriority::ActionRequired,
                "pace-retry:{$approval->id}:requested",
                ['pace_retry_approval_id' => $approval->id, 'is_over_limit' => $isOverLimit],
            ), $actor);

            return $approval;
        }, 3);
    }

    public function decideRetry(PaceRetryApproval $approval, RetryApprovalStatus $decision, string $reason, User $actor): PaceRetryApproval
    {
        return DB::transaction(function () use ($approval, $decision, $reason, $actor): PaceRetryApproval {
            $approval = PaceRetryApproval::query()->lockForUpdate()->findOrFail($approval->id);
            if ($approval->status !== RetryApprovalStatus::Pending) {
                throw ValidationException::withMessages(['decision' => 'This retry request has already been decided.']);
            }
            if ($decision === RetryApprovalStatus::Pending) {
                throw ValidationException::withMessages(['decision' => 'Choose approve or reject.']);
            }
            if ($approval->is_over_limit && ! $actor->hasRole(RoleName::Administrator)) {
                throw ValidationException::withMessages(['decision' => 'Only an Administrator can approve or reject an over-limit attempt.']);
            }

            $approval->update([
                'status' => $decision, 'decided_by' => $actor->id,
                'decided_at' => now(), 'decision_reason' => trim($reason),
            ]);
            if ($decision === RetryApprovalStatus::Approved && $approval->assessment_type === AssessmentType::PaceTest) {
                $this->assignments->transition($approval->assignment, PaceAssignmentStatus::AwaitingPaceTest, $actor, "PACE Test retry {$approval->attempt_number} approved: {$reason}");
            }
            $requester = User::query()->find($approval->requested_by);
            if ($requester !== null) {
                $this->notifications->send([$requester], new OperationalNotification(
                    'Assessment retry decision recorded',
                    "Retry attempt {$approval->attempt_number} was {$decision->value}: ".trim($reason),
                    route('pace-assignments.show', $approval->pace_assignment_id),
                    NotificationCategory::Academic,
                    $decision === RetryApprovalStatus::Approved ? NotificationPriority::Information : NotificationPriority::Warning,
                    "pace-retry:{$approval->id}:{$decision->value}",
                    ['pace_retry_approval_id' => $approval->id],
                ), $actor);
            }

            return $approval->refresh();
        }, 3);
    }

    public function correct(PaceAttempt $attempt, float $score, string $reason, User $actor): PaceAttemptCorrection
    {
        return DB::transaction(function () use ($attempt, $score, $reason, $actor): PaceAttemptCorrection {
            $attempt = PaceAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            $assignment = PaceAssignment::query()->lockForUpdate()->findOrFail($attempt->pace_assignment_id);
            if ($assignment->attempts()->where('assessment_type', $attempt->assessment_type)->where('attempt_number', '>', $attempt->attempt_number)->exists()) {
                throw ValidationException::withMessages(['score' => 'Only the latest attempt of this assessment type can be corrected.']);
            }
            if ($attempt->assessment_type === AssessmentType::PaceTest && $assignment->status === PaceAssignmentStatus::Passed
                && PaceAssignment::query()->where('student_course_id', $assignment->student_course_id)->where('id', '>', $assignment->id)->exists()) {
                throw ValidationException::withMessages(['score' => 'A later PACE assignment exists. Resolve it before correcting this passed result.']);
            }

            $previousOutcome = $this->effectiveOutcome($attempt);
            $outcome = $score >= (float) $attempt->pass_mark_used ? AssessmentOutcome::Passed : AssessmentOutcome::Failed;
            if ((float) $attempt->score === $score && $previousOutcome === $outcome) {
                throw ValidationException::withMessages(['score' => 'The correction must change the effective result.']);
            }
            $correction = $attempt->corrections()->create([
                'score' => $score, 'outcome' => $outcome, 'reason' => trim($reason),
                'corrected_by' => $actor->id, 'corrected_at' => now(),
            ]);
            if ($previousOutcome !== $outcome) {
                $to = match ([$attempt->assessment_type, $outcome]) {
                    [AssessmentType::SelfTest, AssessmentOutcome::Passed] => PaceAssignmentStatus::AwaitingPaceTest,
                    [AssessmentType::SelfTest, AssessmentOutcome::Failed] => PaceAssignmentStatus::InProgress,
                    [AssessmentType::PaceTest, AssessmentOutcome::Passed] => PaceAssignmentStatus::Passed,
                    [AssessmentType::PaceTest, AssessmentOutcome::Failed] => PaceAssignmentStatus::Failed,
                };
                $this->assignments->correctAssessmentStatus($assignment, $to, $actor, $reason);
            }

            return $correction;
        }, 3);
    }

    public function effectiveOutcome(PaceAttempt $attempt): AssessmentOutcome
    {
        if (! $attempt->corrections()->exists()) {
            return $attempt->outcome;
        }

        return $attempt->corrections()->latest('corrected_at')->latest('id')->first()->outcome;
    }
}
