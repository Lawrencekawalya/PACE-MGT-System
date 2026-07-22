<?php

namespace App\Http\Controllers;

use App\Models\PaceAssignment;
use App\Models\PaceRetryApproval;
use App\PaceAssignmentStatus;
use App\RetryApprovalStatus;
use App\RoleName;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AssessmentController extends Controller
{
    public function __invoke(Request $request): Response
    {
        abort_unless($request->user()?->can('enter-test-results') || $request->user()?->can('approve-retests') || $request->user()?->can('view-academic-reports'), 403);
        $search = $request->string('search')->trim()->toString();
        $assignments = PaceAssignment::query()
            ->visibleTo($request->user())
            ->with(['pace:id,course_id,number,title', 'studentCourse.course:id,name', 'studentCourse.enrollment.student:id,admission_number,first_name,last_name'])
            ->whereIn('status', [PaceAssignmentStatus::AwaitingSelfTest, PaceAssignmentStatus::AwaitingPaceTest])
            ->when($search, fn ($query) => $query->where(fn ($query) => $query
                ->whereHas('pace', fn ($query) => $query->where('number', 'like', "%{$search}%"))
                ->orWhereHas('studentCourse.enrollment.student', fn ($query) => $query
                    ->where('admission_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%"))))
            ->oldest('submitted_at')->paginate(20)->withQueryString();

        $approvals = PaceRetryApproval::query()
            ->with(['assignment.pace:id,course_id,number', 'assignment.studentCourse.course:id,name', 'assignment.studentCourse.enrollment.student:id,admission_number,first_name,last_name', 'requestedBy:id,name'])
            ->where('status', RetryApprovalStatus::Pending)
            ->when(
                $request->user()->hasRole(RoleName::Teacher) && ! $request->user()->hasRole(RoleName::Administrator),
                fn ($query) => $query->whereHas('assignment.studentCourse.enrollment.student', fn ($query) => $query->where('teacher_id', $request->user()->id)),
            )
            ->oldest('requested_at')->get();

        return Inertia::render('assessments/Index', [
            'assignments' => $assignments, 'approvals' => $approvals, 'search' => $search,
            'canEnterResults' => Gate::allows('enter-test-results'), 'canApprove' => Gate::allows('approve-retests'),
            'canApproveOverLimit' => $request->user()->hasRole(RoleName::Administrator),
        ]);
    }
}
