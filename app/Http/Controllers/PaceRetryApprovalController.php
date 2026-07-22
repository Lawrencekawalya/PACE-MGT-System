<?php

namespace App\Http\Controllers;

use App\AssessmentType;
use App\Http\Requests\DecidePaceRetryApprovalRequest;
use App\Http\Requests\StorePaceRetryApprovalRequest;
use App\Models\PaceAssignment;
use App\Models\PaceRetryApproval;
use App\RetryApprovalStatus;
use App\Services\ActivityLogger;
use App\Services\PaceAssessmentService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class PaceRetryApprovalController extends Controller
{
    public function __construct(private PaceAssessmentService $assessments, private ActivityLogger $activityLogger) {}

    public function store(StorePaceRetryApprovalRequest $request, PaceAssignment $paceAssignment): RedirectResponse
    {
        $approval = $this->assessments->requestRetry($paceAssignment, AssessmentType::from($request->string('assessment_type')->toString()), $request->string('reason')->toString(), $request->user());
        $this->activityLogger->record($request->user(), 'pace-retry.requested', $approval, newValues: $approval->only(['assessment_type', 'attempt_number', 'is_over_limit']), reason: $approval->request_reason);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Retry approval requested.']);

        return back();
    }

    public function update(DecidePaceRetryApprovalRequest $request, PaceRetryApproval $paceRetryApproval): RedirectResponse
    {
        $decision = RetryApprovalStatus::from($request->string('decision')->toString());
        $approval = $this->assessments->decideRetry($paceRetryApproval, $decision, $request->string('reason')->toString(), $request->user());
        $this->activityLogger->record($request->user(), 'pace-retry.decided', $approval, ['status' => RetryApprovalStatus::Pending->value], ['status' => $decision->value], $approval->decision_reason);
        Inertia::flash('toast', ['type' => 'success', 'message' => "Retry request {$decision->value}."]);

        return back();
    }
}
