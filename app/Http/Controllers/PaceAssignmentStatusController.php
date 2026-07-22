<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransitionPaceAssignmentRequest;
use App\Models\PaceAssignment;
use App\PaceAssignmentStatus;
use App\Services\ActivityLogger;
use App\Services\PaceAssignmentService;
use App\Services\PaceIssueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PaceAssignmentStatusController extends Controller
{
    public function __construct(
        private PaceAssignmentService $assignments,
        private PaceIssueService $issues,
        private ActivityLogger $activityLogger,
    ) {}

    public function __invoke(TransitionPaceAssignmentRequest $request, PaceAssignment $paceAssignment): RedirectResponse
    {
        $from = $paceAssignment->status;
        $to = PaceAssignmentStatus::from($request->string('status')->toString());
        if ($to === PaceAssignmentStatus::InProgress && $from === PaceAssignmentStatus::Assigned) {
            Gate::authorize('issue-paces');
            $updated = $this->issues->issue($paceAssignment, $request->user());
        } elseif ($to === PaceAssignmentStatus::Reassigned) {
            Gate::authorize('approve-retests');
            $reason = $request->string('reason')->trim()->toString();
            if ($reason === '') {
                throw ValidationException::withMessages(['reason' => 'A full-repeat approval reason is required.']);
            }
            $updated = $this->assignments->reassign($paceAssignment, $request->user(), $reason);
        } else {
            if (in_array($to, [PaceAssignmentStatus::AwaitingSelfTest, PaceAssignmentStatus::Cancelled], true)) {
                Gate::authorize('assign-paces');
            } else {
                Gate::authorize('enter-test-results');
            }
            $updated = $this->assignments->transition($paceAssignment, $to, $request->user(), $request->string('reason')->trim()->toString() ?: null);
        }
        $this->activityLogger->record($request->user(), 'pace-assignment.status-changed', $updated, ['status' => $from->value], ['status' => $to->value], $request->string('reason')->trim()->toString() ?: null);
        Inertia::flash('toast', ['type' => 'success', 'message' => "Assignment moved to {$to->label()}."]);

        return back();
    }
}
