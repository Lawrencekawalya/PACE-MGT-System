<?php

namespace App\Services;

use App\EnrollmentStatus;
use App\Models\InventoryItem;
use App\Models\PaceAssignment;
use App\Models\PaceRetryApproval;
use App\Models\SchoolSetting;
use App\Models\StockMovement;
use App\Models\Student;
use App\Models\StudentCourse;
use App\Models\StudentEnrollment;
use App\Models\Term;
use App\Models\TuitionClearance;
use App\Models\User;
use App\PaceAssignmentStatus;
use App\PermissionName;
use App\RetryApprovalStatus;
use App\RoleName;
use App\StockMovementType;
use App\StudentCourseStatus;
use App\StudentStatus;
use App\TuitionClearanceStatus;
use Illuminate\Support\Collection;

class DashboardReportService
{
    public function __construct(private TermPaceTargetService $termTargets) {}

    /** @return array<string, mixed>|null */
    public function academic(User $user): ?array
    {
        if (! $user->can('view-academic-reports')) {
            return null;
        }
        $overdue = PaceAssignment::query()->visibleTo($user)->where(function ($query): void {
            $query->where(fn ($query) => $query->whereIn('status', [PaceAssignmentStatus::Assigned, PaceAssignmentStatus::InProgress])->where('assigned_at', '<=', now()->subDays(14)))
                ->orWhere(fn ($query) => $query->whereIn('status', [PaceAssignmentStatus::AwaitingSelfTest, PaceAssignmentStatus::AwaitingPaceTest])->where('submitted_at', '<=', now()->subDays(3)))
                ->orWhere(fn ($query) => $query->where('status', PaceAssignmentStatus::Failed)->where('updated_at', '<=', now()->subDays(7)));
        });
        $queue = (clone $overdue)->with([
            'pace:id,number', 'studentCourse.course:id,name',
            'studentCourse.enrollment.student:id,admission_number,first_name,last_name',
        ])->oldest('assigned_at')->limit(6)->get()->map(fn (PaceAssignment $assignment): array => [
            'id' => $assignment->id,
            'student' => $assignment->studentCourse->enrollment->student->full_name,
            'admission_number' => $assignment->studentCourse->enrollment->student->admission_number,
            'course' => $assignment->studentCourse->course->name,
            'pace' => $assignment->pace->number,
            'status' => $assignment->status->label(),
        ]);
        $pendingApprovals = PaceRetryApproval::query()->where('status', RetryApprovalStatus::Pending);
        if ($user->hasRole(RoleName::Teacher) && ! $user->hasRole(RoleName::Administrator)) {
            $pendingApprovals->whereHas(
                'assignment.studentCourse.enrollment.learningCenter.teachers',
                fn ($query) => $query->whereKey($user->id),
            );
        }

        return [
            'metrics' => [
                'active_students' => Student::query()->visibleTo($user)->where('status', StudentStatus::Active)->count(),
                'active_assignments' => PaceAssignment::query()->visibleTo($user)->whereIn('status', collect(PaceAssignmentStatus::cases())->reject->isTerminal()->map->value)->count(),
                'pending_tests' => PaceAssignment::query()->visibleTo($user)->whereIn('status', [PaceAssignmentStatus::AwaitingSelfTest, PaceAssignmentStatus::AwaitingPaceTest])->count(),
                'pending_approvals' => $pendingApprovals->count(),
                'completed_this_week' => PaceAssignment::query()->visibleTo($user)->where('status', PaceAssignmentStatus::Passed)->where('completed_at', '>=', now()->subDays(7))->count(),
                'overdue' => (clone $overdue)->count(),
            ],
            'charts' => [
                'target_status_by_subject' => $this->academicTargetStatus($user),
            ],
            'queue' => $queue,
        ];
    }

    /** @return array<string, mixed>|null */
    public function inventory(User $user): ?array
    {
        if (! $user->can('view-inventory-reports')) {
            return null;
        }
        $balanceSql = '(SELECT COALESCE(SUM(quantity), 0) FROM stock_movements WHERE stock_movements.inventory_item_id = inventory_items.id)';
        $lowItems = InventoryItem::query()->where('is_active', true)
            ->whereRaw("{$balanceSql} <= inventory_items.reorder_level")
            ->with(['pace:id,course_id,number', 'pace.course:id,name'])
            ->withSum('movements as on_hand', 'quantity')
            ->orderByRaw("{$balanceSql} ASC")->limit(6)->get()
            ->map(fn (InventoryItem $item): array => [
                'id' => $item->id, 'sku' => $item->sku,
                'course' => $item->pace === null ? 'General inventory' : $item->pace->course->name,
                'pace' => $item->pace === null ? null : $item->pace->number,
                'on_hand' => (int) ($item->on_hand ?? 0), 'reorder_level' => $item->reorder_level,
            ]);

        return [
            'metrics' => [
                'on_hand' => (int) StockMovement::query()->sum('quantity'),
                'issued_this_week' => abs((int) StockMovement::query()->where('type', StockMovementType::Issue)->where('recorded_at', '>=', now()->subDays(7))->sum('quantity')),
                'low_stock' => InventoryItem::query()->where('is_active', true)->whereRaw("{$balanceSql} <= inventory_items.reorder_level")->count(),
                'out_of_stock' => InventoryItem::query()->where('is_active', true)->whereRaw("{$balanceSql} = 0")->count(),
                'awaiting_issue' => PaceAssignment::query()->where('status', PaceAssignmentStatus::Assigned)->count(),
            ],
            'charts' => [
                'issuance_trend' => $this->issuanceTrend(),
                'stock_status' => [
                    'labels' => ['Healthy', 'Low stock', 'Out of stock'],
                    'series' => [
                        InventoryItem::query()->where('is_active', true)
                            ->whereRaw("{$balanceSql} > inventory_items.reorder_level")->count(),
                        InventoryItem::query()->where('is_active', true)
                            ->whereRaw("{$balanceSql} > 0")
                            ->whereRaw("{$balanceSql} <= inventory_items.reorder_level")->count(),
                        InventoryItem::query()->where('is_active', true)
                            ->whereRaw("{$balanceSql} = 0")->count(),
                    ],
                ],
            ],
            'queue' => $lowItems,
        ];
    }

    /** @return array<string, mixed>|null */
    public function clearance(User $user): ?array
    {
        if (! $user->can(PermissionName::ManageTuitionClearance->value)) {
            return null;
        }

        $target = SchoolSetting::current()->term_pace_target;
        $term = Term::query()
            ->with('academicYear:id,name')
            ->where('is_active', true)
            ->whereHas('academicYear', fn ($query) => $query->where('is_active', true))
            ->first();

        if ($term === null) {
            return [
                'period' => null,
                'target' => $target,
                'metrics' => $this->emptyClearanceMetrics(),
                'charts' => $this->emptyClearanceCharts(),
                'queue' => [],
            ];
        }

        $enrollmentIds = StudentEnrollment::query()
            ->where('academic_year_id', $term->academic_year_id)
            ->where('status', EnrollmentStatus::Active)
            ->pluck('id');
        $statuses = TuitionClearance::query()
            ->where('term_id', $term->id)
            ->whereIn('student_enrollment_id', $enrollmentIds)
            ->get(['student_enrollment_id', 'status'])
            ->mapWithKeys(fn (TuitionClearance $clearance): array => [
                $clearance->student_enrollment_id => $clearance->status,
            ]);
        $progress = $this->clearanceProgress($term);
        $leadingProgress = $progress
            ->groupBy('enrollment_id')
            ->map(fn (Collection $rows): array => $rows->sortByDesc('completed')->first());
        $fullyPaidIds = $statuses
            ->filter(fn (TuitionClearanceStatus $status): bool => $status === TuitionClearanceStatus::FullyPaid)
            ->keys();
        $atTargetIds = $leadingProgress
            ->filter(fn (array $row): bool => $row['completed'] >= $target)
            ->keys();
        $nearTargetIds = $leadingProgress
            ->filter(fn (array $row): bool => $row['completed'] >= max(1, $target - 1))
            ->keys();
        $restrictedIds = $atTargetIds->diff($fullyPaidIds);
        $nearOnlyIds = $nearTargetIds->diff($atTargetIds);
        $clearedAtTargetIds = $atTargetIds->intersect($fullyPaidIds);
        $attention = $leadingProgress
            ->filter(fn (array $row, $enrollmentId): bool => $nearTargetIds->contains($enrollmentId)
                && ! $fullyPaidIds->contains($enrollmentId))
            ->sortByDesc(fn (array $row, $enrollmentId): string => sprintf(
                '%d-%08d',
                $restrictedIds->contains($enrollmentId) ? 1 : 0,
                $row['completed'],
            ))
            ->take(6);
        $queueEnrollments = StudentEnrollment::query()
            ->whereKey($attention->keys())
            ->with([
                'student:id,admission_number,first_name,last_name,other_names',
                'level:id,name',
                'learningCenter:id,name',
            ])
            ->get()
            ->keyBy('id');

        return [
            'period' => [
                'academic_year_id' => $term->academic_year_id,
                'academic_year' => $term->academicYear->name,
                'term_id' => $term->id,
                'term' => $term->name,
            ],
            'target' => $target,
            'metrics' => [
                'students' => $enrollmentIds->count(),
                'fully_paid' => $statuses->filter(
                    fn (TuitionClearanceStatus $status): bool => $status === TuitionClearanceStatus::FullyPaid,
                )->count(),
                'partially_paid' => $statuses->filter(
                    fn (TuitionClearanceStatus $status): bool => $status === TuitionClearanceStatus::PartiallyPaid,
                )->count(),
                'unconfirmed' => $enrollmentIds->count()
                    - $statuses->filter(
                        fn (TuitionClearanceStatus $status): bool => $status !== TuitionClearanceStatus::Unconfirmed,
                    )->count(),
                'restricted' => $restrictedIds->count(),
                'approaching_or_at_target' => $nearTargetIds->count(),
            ],
            'charts' => [
                'status_distribution' => [
                    'labels' => ['Fully paid', 'Partially paid', 'Unconfirmed'],
                    'series' => [
                        $statuses->filter(
                            fn (TuitionClearanceStatus $status): bool => $status === TuitionClearanceStatus::FullyPaid,
                        )->count(),
                        $statuses->filter(
                            fn (TuitionClearanceStatus $status): bool => $status === TuitionClearanceStatus::PartiallyPaid,
                        )->count(),
                        $enrollmentIds->count()
                            - $statuses->filter(
                                fn (TuitionClearanceStatus $status): bool => $status !== TuitionClearanceStatus::Unconfirmed,
                            )->count(),
                    ],
                ],
                'target_pressure' => $this->clearanceTargetPressure(
                    $nearTargetIds,
                    $nearOnlyIds,
                    $restrictedIds,
                    $clearedAtTargetIds,
                ),
            ],
            'queue' => $attention->map(function (array $row, $enrollmentId) use (
                $queueEnrollments,
                $restrictedIds,
                $statuses,
                $target,
            ): array {
                $enrollment = $queueEnrollments->get($enrollmentId);
                $status = $statuses->get($enrollmentId, TuitionClearanceStatus::Unconfirmed);

                return [
                    'enrollment_id' => $enrollmentId,
                    'student' => $enrollment->student->full_name,
                    'admission_number' => $enrollment->student->admission_number,
                    'learning_center' => $enrollment->learningCenter->name ?? 'Unassigned',
                    'level' => $enrollment->level->name,
                    'course' => $row['course'],
                    'completed' => $row['completed'],
                    'target' => $target,
                    'clearance_status' => $status->value,
                    'clearance_status_label' => $status->label(),
                    'restricted' => $restrictedIds->contains($enrollmentId),
                ];
            })->values(),
        ];
    }

    /**
     * @return Collection<int, array{
     *     enrollment_id: int,
     *     course: string,
     *     completed: int
     * }>
     */
    private function clearanceProgress(Term $term): Collection
    {
        return PaceAssignment::query()
            ->selectRaw('student_courses.student_enrollment_id as enrollment_id')
            ->selectRaw('courses.name as course_name')
            ->selectRaw('COUNT(DISTINCT pace_assignments.pace_id) as completed_count')
            ->join('student_courses', 'student_courses.id', '=', 'pace_assignments.student_course_id')
            ->join('student_enrollments', 'student_enrollments.id', '=', 'student_courses.student_enrollment_id')
            ->join('courses', 'courses.id', '=', 'student_courses.course_id')
            ->where('student_enrollments.academic_year_id', $term->academic_year_id)
            ->where('student_enrollments.status', EnrollmentStatus::Active)
            ->where('pace_assignments.status', PaceAssignmentStatus::Passed)
            ->whereBetween('pace_assignments.completed_at', [
                $term->starts_on->copy()->startOfDay(),
                $term->ends_on->copy()->endOfDay(),
            ])
            ->groupBy('student_courses.student_enrollment_id', 'student_courses.id', 'courses.name')
            ->get()
            ->map(fn (PaceAssignment $assignment): array => [
                'enrollment_id' => (int) $assignment->getAttribute('enrollment_id'),
                'course' => (string) $assignment->getAttribute('course_name'),
                'completed' => (int) $assignment->getAttribute('completed_count'),
            ]);
    }

    /** @return array{students: int, fully_paid: int, partially_paid: int, unconfirmed: int, restricted: int, approaching_or_at_target: int} */
    private function emptyClearanceMetrics(): array
    {
        return [
            'students' => 0,
            'fully_paid' => 0,
            'partially_paid' => 0,
            'unconfirmed' => 0,
            'restricted' => 0,
            'approaching_or_at_target' => 0,
        ];
    }

    /**
     * @return array{
     *     categories: array<int, string>,
     *     series: array<int, array{name: string, data: array<int, int>}>
     * }
     */
    private function academicTargetStatus(User $user): array
    {
        $term = Term::query()
            ->where('is_active', true)
            ->whereHas('academicYear', fn ($query) => $query->where('is_active', true))
            ->first();

        if ($term === null) {
            return ['categories' => [], 'series' => $this->emptyTargetStatusSeries()];
        }

        $target = SchoolSetting::current()->term_pace_target;
        $subjects = StudentCourse::query()
            ->visibleTo($user)
            ->where('status', StudentCourseStatus::Active)
            ->whereHas('enrollment', fn ($query) => $query
                ->where('academic_year_id', $term->academic_year_id)
                ->where('status', EnrollmentStatus::Active))
            ->with([
                'course:id,name',
                'paceAssignments' => fn ($query) => $query
                    ->where('term_id', $term->id)
                    ->where('status', PaceAssignmentStatus::Passed)
                    ->whereBetween('completed_at', [
                        $term->starts_on->copy()->startOfDay(),
                        $term->ends_on->copy()->endOfDay(),
                    ])
                    ->select(['id', 'student_course_id', 'pace_id', 'status', 'completed_at']),
            ])
            ->get()
            ->groupBy(fn (StudentCourse $studentCourse): string => $studentCourse->course->name)
            ->map(function (Collection $studentCourses) use ($term, $target): array {
                $statuses = $studentCourses->map(
                    fn (StudentCourse $studentCourse): string => $this->termTargets
                        ->summarize($studentCourse->paceAssignments, $term, $target)['status'],
                );

                return [
                    'target_achieved' => $statuses->filter(
                        fn (string $status): bool => $status === 'target_achieved',
                    )->count(),
                    'on_track' => $statuses->filter(
                        fn (string $status): bool => $status === 'on_track',
                    )->count(),
                    'below_target' => $statuses->filter(
                        fn (string $status): bool => $status === 'below_target',
                    )->count(),
                ];
            })
            ->sortByDesc(fn (array $counts): int => array_sum($counts))
            ->take(8);

        return [
            'categories' => $subjects->keys()->values()->all(),
            'series' => [
                [
                    'name' => 'Target achieved',
                    'data' => $subjects->pluck('target_achieved')->map(fn ($count): int => (int) $count)->values()->all(),
                ],
                [
                    'name' => 'On track',
                    'data' => $subjects->pluck('on_track')->map(fn ($count): int => (int) $count)->values()->all(),
                ],
                [
                    'name' => 'Below target',
                    'data' => $subjects->pluck('below_target')->map(fn ($count): int => (int) $count)->values()->all(),
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{name: string, data: array<int, int>}>
     */
    private function emptyTargetStatusSeries(): array
    {
        return [
            ['name' => 'Target achieved', 'data' => []],
            ['name' => 'On track', 'data' => []],
            ['name' => 'Below target', 'data' => []],
        ];
    }

    /**
     * @return array{
     *     categories: array<int, string>,
     *     series: array<int, array{name: string, data: array<int, int>}>
     * }
     */
    private function issuanceTrend(): array
    {
        $weeks = collect(range(7, 0))->map(fn (int $weeksAgo) => now()
            ->startOfWeek()
            ->subWeeks($weeksAgo));
        $movements = StockMovement::query()
            ->where('type', StockMovementType::Issue)
            ->where('recorded_at', '>=', $weeks->first()->copy()->startOfDay())
            ->get(['quantity', 'recorded_at'])
            ->groupBy(fn (StockMovement $movement): string => $movement->recorded_at
                ->copy()
                ->startOfWeek()
                ->toDateString());

        return [
            'categories' => $weeks->map(fn ($week): string => $week->format('M j'))->all(),
            'series' => [[
                'name' => 'PACEs issued',
                'data' => $weeks->map(fn ($week): int => abs((int) $movements
                    ->get($week->toDateString(), collect())
                    ->sum('quantity')))->all(),
            ]],
        ];
    }

    /**
     * @param  Collection<int, int>  $nearTargetIds
     * @param  Collection<int, int>  $nearOnlyIds
     * @param  Collection<int, int>  $restrictedIds
     * @param  Collection<int, int>  $clearedAtTargetIds
     * @return array{
     *     categories: array<int, string>,
     *     series: array<int, array{name: string, data: array<int, int>}>
     * }
     */
    private function clearanceTargetPressure(
        Collection $nearTargetIds,
        Collection $nearOnlyIds,
        Collection $restrictedIds,
        Collection $clearedAtTargetIds,
    ): array {
        $centers = StudentEnrollment::query()
            ->whereKey($nearTargetIds)
            ->with('learningCenter:id,name')
            ->get(['id', 'learning_center_id'])
            ->groupBy(fn (StudentEnrollment $enrollment): string => $enrollment->learning_center_id === null
                ? 'Unassigned'
                : $enrollment->learningCenter->name)
            ->map(fn (Collection $enrollments): array => [
                'near_target' => $enrollments->whereIn('id', $nearOnlyIds)->count(),
                'restricted' => $enrollments->whereIn('id', $restrictedIds)->count(),
                'cleared' => $enrollments->whereIn('id', $clearedAtTargetIds)->count(),
            ])
            ->sortByDesc(fn (array $counts): int => array_sum($counts))
            ->take(8);

        return [
            'categories' => $centers->keys()->values()->all(),
            'series' => [
                ['name' => 'Near target', 'data' => $centers->pluck('near_target')->values()->all()],
                ['name' => 'Restricted', 'data' => $centers->pluck('restricted')->values()->all()],
                ['name' => 'Cleared at target', 'data' => $centers->pluck('cleared')->values()->all()],
            ],
        ];
    }

    /**
     * @return array{
     *     status_distribution: array{labels: array<int, string>, series: array<int, int>},
     *     target_pressure: array{
     *         categories: array<int, string>,
     *         series: array<int, array{name: string, data: array<int, int>}>
     *     }
     * }
     */
    private function emptyClearanceCharts(): array
    {
        return [
            'status_distribution' => [
                'labels' => ['Fully paid', 'Partially paid', 'Unconfirmed'],
                'series' => [0, 0, 0],
            ],
            'target_pressure' => [
                'categories' => [],
                'series' => [
                    ['name' => 'Near target', 'data' => []],
                    ['name' => 'Restricted', 'data' => []],
                    ['name' => 'Cleared at target', 'data' => []],
                ],
            ],
        ];
    }
}
