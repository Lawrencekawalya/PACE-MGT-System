<?php

namespace App\Http\Controllers;

use App\Http\Requests\IssuePacesRequest;
use App\InventoryItemType;
use App\Models\Course;
use App\Models\InventoryItem;
use App\Models\LearningCenter;
use App\Models\PaceAccountTransaction;
use App\Models\PaceAssignment;
use App\Models\SchoolSetting;
use App\PaceAssignmentStatus;
use App\Services\ActivityLogger;
use App\Services\PaceIssueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PaceIssuingController extends Controller
{
    public function __construct(
        private PaceIssueService $issues,
        private ActivityLogger $activityLogger,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('issue-paces');
        $mode = $request->string('mode')->toString();
        $filters = [
            'mode' => in_array($mode, ['center', 'pace', 'student'], true) ? $mode : 'center',
            'search' => $request->string('search')->trim()->toString(),
            'learning_center_id' => $request->integer('learning_center_id') ?: null,
            'level_id' => $request->integer('level_id') ?: null,
            'course_id' => $request->integer('course_id') ?: null,
        ];

        $query = PaceAssignment::query()
            ->select('pace_assignments.*')
            ->join('student_courses', 'student_courses.id', '=', 'pace_assignments.student_course_id')
            ->join('student_enrollments', 'student_enrollments.id', '=', 'student_courses.student_enrollment_id')
            ->join('students', 'students.id', '=', 'student_enrollments.student_id')
            ->join('paces', 'paces.id', '=', 'pace_assignments.pace_id')
            ->leftJoin('learning_centers', 'learning_centers.id', '=', 'student_enrollments.learning_center_id')
            ->leftJoin('levels', 'levels.id', '=', 'student_enrollments.level_id')
            ->visibleTo($request->user())
            ->where('pace_assignments.status', PaceAssignmentStatus::Assigned)
            ->whereNull('pace_assignments.issued_at')
            ->with([
                'pace:id,course_id,number,title',
                'studentCourse:id,student_enrollment_id,course_id',
                'studentCourse.course:id,name',
                'studentCourse.enrollment:id,student_id,learning_center_id,level_id',
                'studentCourse.enrollment.student:id,admission_number,first_name,last_name,other_names',
                'studentCourse.enrollment.learningCenter:id,name,code',
                'studentCourse.enrollment.level:id,name,code',
            ])
            ->when($filters['learning_center_id'], fn ($query, int $id) => $query->where('student_enrollments.learning_center_id', $id))
            ->when($filters['level_id'], fn ($query, int $id) => $query->where('student_enrollments.level_id', $id))
            ->when($filters['course_id'], fn ($query, int $id) => $query->where('student_courses.course_id', $id))
            ->when($filters['search'], function ($query, string $search) use ($filters): void {
                $query->where(function ($query) use ($filters, $search): void {
                    if ($filters['mode'] === 'pace') {
                        $query->where('paces.number', 'like', "%{$search}%")
                            ->orWhere('paces.title', 'like', "%{$search}%");

                        return;
                    }

                    if ($filters['mode'] === 'student') {
                        $query->where('students.admission_number', 'like', "%{$search}%")
                            ->orWhere('students.first_name', 'like', "%{$search}%")
                            ->orWhere('students.last_name', 'like', "%{$search}%")
                            ->orWhere('students.other_names', 'like', "%{$search}%");

                        return;
                    }

                    $query->where('students.admission_number', 'like', "%{$search}%")
                        ->orWhere('students.first_name', 'like', "%{$search}%")
                        ->orWhere('students.last_name', 'like', "%{$search}%")
                        ->orWhere('paces.number', 'like', "%{$search}%");
                });
            });

        match ($filters['mode']) {
            'pace' => $query->orderBy('paces.number')
                ->orderBy('learning_centers.name')
                ->orderBy('levels.sort_order')
                ->orderBy('students.last_name'),
            'student' => $query->orderBy('students.last_name')
                ->orderBy('students.first_name')
                ->orderBy('paces.number'),
            default => $query->orderBy('learning_centers.name')
                ->orderBy('levels.sort_order')
                ->orderBy('students.last_name')
                ->orderBy('paces.number'),
        };

        $assignments = $query->paginate(50)->withQueryString();
        $itemsByPace = InventoryItem::query()
            ->whereIn('pace_id', $assignments->getCollection()->pluck('pace_id')->unique())
            ->where('item_type', InventoryItemType::PaceBooklet)
            ->withSum('movements as on_hand', 'quantity')
            ->get(['id', 'pace_id', 'sku', 'is_active', 'is_consumable'])
            ->keyBy('pace_id');
        $studentIds = $assignments->getCollection()
            ->pluck('studentCourse.enrollment.student.id')
            ->unique()
            ->values();
        $balances = PaceAccountTransaction::query()
            ->whereIn('student_id', $studentIds)
            ->selectRaw('student_id, SUM(amount) as balance')
            ->groupBy('student_id')
            ->pluck('balance', 'student_id');
        $paceCost = (string) SchoolSetting::current()->pace_cost;

        $assignments->through(function (PaceAssignment $assignment) use ($itemsByPace, $balances, $paceCost): PaceAssignment {
            $item = $itemsByPace->get($assignment->pace_id);
            $studentId = $assignment->studentCourse->enrollment->student->id;
            $balance = (string) ($balances->get($studentId) ?? '0.00');
            $assignment->setAttribute('inventory', $item === null ? null : [
                'id' => $item->id,
                'sku' => $item->sku,
                'on_hand' => (int) $item->on_hand,
                'is_active' => $item->is_active,
                'is_consumable' => $item->is_consumable,
            ]);
            $assignment->setAttribute('pace_account', [
                'balance' => number_format((float) $balance, 2, '.', ''),
                'pace_cost' => $paceCost,
                'can_issue' => (float) $paceCost > 0 && (float) $balance >= (float) $paceCost,
            ]);

            return $assignment;
        });

        return Inertia::render('pace-issuing/Index', [
            'assignments' => $assignments,
            'filters' => $filters,
            'learningCenters' => LearningCenter::query()
                ->where('is_active', true)
                ->with(['levels' => fn ($query) => $query->where('is_active', true)->select('id', 'learning_center_id', 'name', 'code', 'sort_order')])
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'courses' => Course::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'paceCost' => $paceCost,
        ]);
    }

    public function store(IssuePacesRequest $request): RedirectResponse
    {
        /** @var list<int> $assignmentIds */
        $assignmentIds = array_map('intval', $request->validated('assignment_ids'));
        $issued = $this->issues->issueMany($assignmentIds, $request->user());
        $issued->each(fn (PaceAssignment $assignment) => $this->activityLogger->record(
            $request->user(),
            'pace-assignment.status-changed',
            $assignment,
            ['status' => PaceAssignmentStatus::Assigned->value],
            ['status' => PaceAssignmentStatus::InProgress->value],
            'Physical PACE issue to student.',
        ));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$issued->count()} PACE assignment(s) issued and moved to In progress.",
        ]);

        return back();
    }
}
