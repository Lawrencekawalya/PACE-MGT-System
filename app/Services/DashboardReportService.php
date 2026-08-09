<?php

namespace App\Services;

use App\EnrollmentStatus;
use App\Models\InventoryItem;
use App\Models\PaceAccountTransaction;
use App\Models\PaceAssignment;
use App\Models\PaceRetryApproval;
use App\Models\SchoolSetting;
use App\Models\StockMovement;
use App\Models\Student;
use App\Models\StudentCourse;
use App\Models\StudentEnrollment;
use App\Models\Term;
use App\Models\User;
use App\PaceAssignmentStatus;
use App\PermissionName;
use App\RetryApprovalStatus;
use App\RoleName;
use App\StockMovementType;
use App\StudentCourseStatus;
use App\StudentStatus;
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
    public function paceAccounts(User $user): ?array
    {
        if (! $user->can(PermissionName::ManagePaceAccounts->value)) {
            return null;
        }

        $paceCost = (float) SchoolSetting::current()->pace_cost;
        $term = Term::query()
            ->with('academicYear:id,name')
            ->where('is_active', true)
            ->whereHas('academicYear', fn ($query) => $query->where('is_active', true))
            ->first();
        $academicYearId = $term?->academic_year_id;
        $balanceSql = '(select coalesce(sum(pace_account_transactions.amount), 0) from pace_account_transactions where pace_account_transactions.student_id = student_enrollments.student_id)';
        $enrollments = StudentEnrollment::query()
            ->when($academicYearId, fn ($query, $id) => $query->where('academic_year_id', $id))
            ->where('status', EnrollmentStatus::Active)
            ->with([
                'student:id,admission_number,first_name,last_name,other_names',
                'level:id,name',
                'learningCenter:id,name',
            ])
            ->select('student_enrollments.*')
            ->selectRaw("{$balanceSql} as pace_balance")
            ->get();
        $funded = $paceCost > 0
            ? $enrollments->filter(fn (StudentEnrollment $enrollment): bool => (float) $enrollment->getAttribute('pace_balance') >= $paceCost)
            : collect();
        $zero = $enrollments->filter(fn (StudentEnrollment $enrollment): bool => (float) $enrollment->getAttribute('pace_balance') <= 0);
        $insufficient = $enrollments->whereNotIn('id', $funded->pluck('id'))->whereNotIn('id', $zero->pluck('id'));
        $centerBalances = $enrollments
            ->groupBy(fn (StudentEnrollment $enrollment): string => $enrollment->learning_center_id === null
                ? 'Unassigned'
                : $enrollment->learningCenter->name)
            ->map(fn (Collection $rows): float => round($rows->sum(fn (StudentEnrollment $enrollment): float => (float) $enrollment->getAttribute('pace_balance')), 2))
            ->sortDesc()
            ->take(8);
        $attention = $enrollments
            ->filter(fn (StudentEnrollment $enrollment): bool => ! $funded->contains('id', $enrollment->id))
            ->sortBy(fn (StudentEnrollment $enrollment): float => (float) $enrollment->getAttribute('pace_balance'))
            ->take(6);
        $totalBalance = PaceAccountTransaction::query()
            ->whereIn('student_id', $enrollments->pluck('student_id'))
            ->sum('amount');

        return [
            'period' => $term === null ? null : [
                'academic_year_id' => $term->academic_year_id,
                'academic_year' => $term->academicYear->name,
                'term_id' => $term->id,
                'term' => $term->name,
            ],
            'pace_cost' => number_format($paceCost, 2, '.', ''),
            'metrics' => [
                'students' => $enrollments->count(),
                'total_balance' => number_format((float) $totalBalance, 2, '.', ''),
                'funded' => $funded->count(),
                'insufficient' => $insufficient->count(),
                'zero' => $zero->count(),
            ],
            'charts' => [
                'balance_status' => [
                    'labels' => ['Can issue', 'Insufficient', 'Zero balance'],
                    'series' => [$funded->count(), $insufficient->count(), $zero->count()],
                ],
                'balance_by_center' => [
                    'categories' => $centerBalances->keys()->values()->all(),
                    'series' => [['name' => 'PACE credit (UGX)', 'data' => $centerBalances->values()->all()]],
                ],
            ],
            'queue' => $attention->map(fn (StudentEnrollment $enrollment): array => [
                'enrollment_id' => $enrollment->id,
                'student' => $enrollment->student->full_name,
                'admission_number' => $enrollment->student->admission_number,
                'learning_center' => $enrollment->learning_center_id === null
                    ? 'Unassigned'
                    : $enrollment->learningCenter->name,
                'level' => $enrollment->level->name,
                'balance' => number_format((float) $enrollment->getAttribute('pace_balance'), 2, '.', ''),
                'shortfall' => number_format(max(0, $paceCost - (float) $enrollment->getAttribute('pace_balance')), 2, '.', ''),
            ])->values(),
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
}
