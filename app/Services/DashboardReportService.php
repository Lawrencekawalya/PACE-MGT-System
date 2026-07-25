<?php

namespace App\Services;

use App\EnrollmentStatus;
use App\Models\InventoryItem;
use App\Models\PaceAssignment;
use App\Models\PaceRetryApproval;
use App\Models\SchoolSetting;
use App\Models\StockMovement;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Term;
use App\Models\TuitionClearance;
use App\Models\User;
use App\PaceAssignmentStatus;
use App\PermissionName;
use App\RetryApprovalStatus;
use App\RoleName;
use App\StockMovementType;
use App\StudentStatus;
use App\TuitionClearanceStatus;
use Illuminate\Support\Collection;

class DashboardReportService
{
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
}
