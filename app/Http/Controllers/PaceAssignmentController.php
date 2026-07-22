<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaceAssignmentRequest;
use App\InventoryItemType;
use App\Models\Course;
use App\Models\InventoryItem;
use App\Models\Pace;
use App\Models\PaceAssignment;
use App\Models\SchoolSetting;
use App\Models\StudentCourse;
use App\PaceAssignmentStatus;
use App\RoleName;
use App\Services\ActivityLogger;
use App\Services\PaceAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PaceAssignmentController extends Controller
{
    public function __construct(private PaceAssignmentService $assignments, private ActivityLogger $activityLogger) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', PaceAssignment::class);
        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'status' => $request->string('status')->toString(),
            'course_id' => $request->integer('course_id') ?: null,
            'date_from' => $request->date('date_from')?->toDateString(),
            'date_to' => $request->date('date_to')?->toDateString(),
            'exceptions' => $request->boolean('exceptions'),
        ];
        $query = PaceAssignment::query()->visibleTo($request->user())->with([
            'pace:id,course_id,number,title', 'studentCourse.course:id,name',
            'studentCourse.enrollment.student:id,admission_number,first_name,last_name,other_names', 'assignedBy:id,name',
        ]);
        $query->when($filters['search'], fn ($query, $search) => $query->where(fn ($query) => $query
            ->whereHas('pace', fn ($query) => $query->where('number', 'like', "%{$search}%"))
            ->orWhereHas('studentCourse.enrollment.student', fn ($query) => $query
                ->where('admission_number', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%"))))
            ->when(in_array($filters['status'], array_column(PaceAssignmentStatus::cases(), 'value'), true), fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['course_id'], fn ($query, $courseId) => $query->whereHas('studentCourse', fn ($query) => $query->where('course_id', $courseId)))
            ->when($filters['date_from'], fn ($query, $date) => $query->whereDate('assigned_at', '>=', $date))
            ->when($filters['date_to'], fn ($query, $date) => $query->whereDate('assigned_at', '<=', $date))
            ->when($filters['exceptions'], fn ($query) => $query->where(fn ($query) => $query
                ->whereNotNull('override_reason')
                ->orWhere(fn ($query) => $query->whereIn('status', ['assigned', 'in_progress'])->where('assigned_at', '<=', now()->subDays(14)))));

        return Inertia::render('pace-assignments/Index', [
            'assignments' => $query->latest('assigned_at')->paginate(20)->withQueryString(),
            'filters' => $filters,
            'statuses' => collect(PaceAssignmentStatus::cases())->map(fn (PaceAssignmentStatus $status) => ['value' => $status->value, 'label' => $status->label()]),
            'courses' => Course::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'summary' => [
                'active' => PaceAssignment::query()->visibleTo($request->user())->whereIn('status', collect(PaceAssignmentStatus::cases())->reject->isTerminal()->map->value)->count(),
                'awaiting_test' => PaceAssignment::query()->visibleTo($request->user())->whereIn('status', [PaceAssignmentStatus::AwaitingSelfTest, PaceAssignmentStatus::AwaitingPaceTest])->count(),
                'exceptions' => PaceAssignment::query()->visibleTo($request->user())->where(fn ($query) => $query->whereNotNull('override_reason')->orWhere(fn ($query) => $query->whereIn('status', ['assigned', 'in_progress'])->where('assigned_at', '<=', now()->subDays(14))))->count(),
            ],
        ]);
    }

    public function show(PaceAssignment $paceAssignment): Response
    {
        Gate::authorize('view', $paceAssignment);
        $paceAssignment->load([
            'pace.course.subject:id,name', 'academicYear:id,name', 'term:id,name',
            'studentCourse.enrollment.student', 'assignedBy:id,name', 'issuedBy:id,name', 'statusEvents.changedBy:id,name',
            'attempts.recordedBy:id,name', 'attempts.approvedBy:id,name', 'attempts.corrections.correctedBy:id,name',
            'retryApprovals.requestedBy:id,name', 'retryApprovals.decidedBy:id,name',
        ]);
        $paceAssignment->attempts->each(function ($attempt): void {
            if ($attempt->corrections->isEmpty()) {
                $attempt->setAttribute('effective_score', $attempt->score);
                $attempt->setAttribute('effective_outcome', $attempt->outcome->value);

                return;
            }
            $correction = $attempt->corrections->last();
            $attempt->setAttribute('effective_score', $correction->score);
            $attempt->setAttribute('effective_outcome', $correction->outcome->value);
        });
        $settings = SchoolSetting::current();
        $inventoryItem = InventoryItem::query()->where('pace_id', $paceAssignment->pace_id)->where('item_type', InventoryItemType::PaceBooklet)->first();

        return Inertia::render('pace-assignments/Show', [
            'assignment' => $paceAssignment,
            'availableTransitions' => collect($paceAssignment->status->allowedNext())
                ->filter(fn (PaceAssignmentStatus $status) => in_array($status, [PaceAssignmentStatus::InProgress, PaceAssignmentStatus::AwaitingSelfTest, PaceAssignmentStatus::Reassigned, PaceAssignmentStatus::Cancelled], true))
                ->map(fn (PaceAssignmentStatus $status) => ['value' => $status->value, 'label' => $status->label()])->values(),
            'canIssue' => request()->user()?->can('issue-paces') ?? false,
            'canAssign' => request()->user()?->can('assign-paces') ?? false,
            'canApproveRepeat' => request()->user()?->can('approve-retests') ?? false,
            'canEnterResults' => request()->user()?->can('enter-test-results') ?? false,
            'canCorrectResults' => request()->user()?->hasRole(RoleName::Administrator) ?? false,
            'assessmentRules' => [
                'self_test_pass_mark' => $settings->self_test_pass_mark,
                'pace_test_pass_mark' => $settings->pace_test_pass_mark,
                'self_test_retry_limit' => $settings->self_test_retry_limit,
            ],
            'nextRecommendation' => $paceAssignment->status === PaceAssignmentStatus::Passed
                ? $this->assignments->recommend($paceAssignment->studentCourse)?->only(['id', 'number', 'title']) : null,
            'inventory' => $inventoryItem === null ? null : [
                'id' => $inventoryItem->id, 'sku' => $inventoryItem->sku,
                'on_hand' => $inventoryItem->onHand(), 'reorder_level' => $inventoryItem->reorder_level,
                'is_active' => $inventoryItem->is_active,
            ],
        ]);
    }

    public function store(StorePaceAssignmentRequest $request): RedirectResponse
    {
        $studentCourse = StudentCourse::query()->findOrFail($request->integer('student_course_id'));
        $pace = Pace::query()->findOrFail($request->integer('pace_id'));
        $assignment = $this->assignments->assign($studentCourse, $pace, $request->user(), $request->string('override_reason')->trim()->toString() ?: null);
        $this->activityLogger->record($request->user(), 'pace-assignment.created', $assignment, newValues: $assignment->only(['student_course_id', 'pace_id', 'status', 'attempt_cycle']), reason: $assignment->override_reason);
        Inertia::flash('toast', ['type' => 'success', 'message' => "PACE {$pace->number} assigned."]);

        return redirect()->route('pace-assignments.show', $assignment);
    }
}
