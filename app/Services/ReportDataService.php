<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CurriculumRequirement;
use App\Models\InventoryItem;
use App\Models\Pace;
use App\Models\PaceAssignment;
use App\Models\StockMovement;
use App\Models\StudentCourse;
use App\Models\User;
use App\PaceAssignmentStatus;
use App\PermissionName;
use App\ReportType;
use App\StockMovementType;
use App\StudentCourseStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReportDataService
{
    /** @param array<string, mixed> $filters
     * @return array{rows: Collection<int, covariant array<string, mixed>>, summary: array<string, int|float>}
     */
    public function data(ReportType $type, array $filters): array
    {
        $rows = match ($type) {
            ReportType::StudentProgress => $this->studentProgressRows($filters),
            ReportType::CourseProgress => $this->courseProgressRows($filters),
            ReportType::PendingWork => $this->pendingWorkRows($filters),
            ReportType::PaceIssuing => $this->issuingRows($filters),
            ReportType::Inventory => $this->inventoryRows($filters),
        };

        return ['rows' => $rows, 'summary' => $this->summary($type, $rows)];
    }

    public function authorize(User $user, ReportType $type): void
    {
        $permission = $type->isInventory()
            ? PermissionName::ViewInventoryReports
            : PermissionName::ViewAcademicReports;

        if (! $user->can($permission->value)) {
            abort(403);
        }
    }

    /** @param array<string, mixed> $filters */
    public function rowCount(ReportType $type, array $filters): int
    {
        return match ($type) {
            ReportType::StudentProgress => $this->studentCourseCount($filters),
            ReportType::CourseProgress => $this->courseProgressCount($filters),
            ReportType::PendingWork => $this->pendingWorkCount($filters),
            ReportType::PaceIssuing => $this->issuingCount($filters),
            ReportType::Inventory => $this->inventoryCount($filters),
        };
    }

    /** @param Collection<int, covariant array<string, mixed>> $rows
     * @return array{headers: list<string>, rows: Collection<int, covariant list<string|int|float|null>>}
     */
    public function exportData(ReportType $type, Collection $rows): array
    {
        $fields = match ($type) {
            ReportType::StudentProgress => [
                'admission_number' => 'Admission number', 'student' => 'Student', 'level' => 'Level',
                'course' => 'Course', 'current_pace' => 'Current PACE', 'assignment_status' => 'Assignment status',
                'completed_paces' => 'Completed PACEs', 'sequence_total' => 'Sequence PACEs',
                'progress_percent' => 'Progress %', 'failed_cycles' => 'Failed/repeated cycles', 'days_inactive' => 'Days inactive',
            ],
            ReportType::CourseProgress => [
                'level' => 'Level', 'course' => 'Course', 'students' => 'Students',
                'active_students' => 'Active students', 'completed_courses' => 'Completed courses',
                'completed_paces' => 'Completed PACEs', 'average_progress' => 'Average progress %',
                'failed_cycles' => 'Failed/repeated cycles', 'inactive_students' => 'Inactive students',
            ],
            ReportType::PendingWork => [
                'admission_number' => 'Admission number', 'student' => 'Student', 'level' => 'Level',
                'course' => 'Course', 'pace' => 'PACE', 'status' => 'Status', 'next_action' => 'Next action',
                'waiting_since' => 'Waiting since', 'age_days' => 'Age days', 'overdue' => 'Overdue',
            ],
            ReportType::PaceIssuing => [
                'admission_number' => 'Admission number', 'student' => 'Student',
                'learning_center' => 'Learning centre', 'level' => 'Level', 'course' => 'Course',
                'pace' => 'PACE', 'pace_title' => 'PACE title', 'quantity' => 'Quantity',
                'issued_date' => 'Issue date', 'issued_time' => 'Issue time',
                'academic_year' => 'Academic year', 'term' => 'Term', 'issued_by' => 'Issued by',
                'status' => 'Status', 'reference' => 'Reference',
            ],
            ReportType::Inventory => [
                'sku' => 'SKU', 'item_type' => 'Item type', 'course' => 'Course', 'pace' => 'PACE',
                'on_hand' => 'On hand', 'received' => 'Received in period', 'issued' => 'Issued in period',
                'reorder_level' => 'Reorder level', 'stock_status' => 'Stock status',
            ],
        };

        return [
            'headers' => array_values($fields),
            'rows' => $rows->map(fn (array $row): array => $this->exportRow(array_keys($fields), $row)),
        ];
    }

    /** @param array<string, mixed> $filters
     * @return Collection<int, covariant array<string, mixed>>
     */
    private function issuingRows(array $filters): Collection
    {
        $query = StockMovement::query()->with([
            'paceAssignment:id,student_course_id,pace_id',
            'paceAssignment.pace:id,course_id,number,title',
            'paceAssignment.studentCourse:id,student_enrollment_id,course_id',
            'paceAssignment.studentCourse.course:id,name',
            'paceAssignment.studentCourse.enrollment:id,student_id,learning_center_id,level_id',
            'paceAssignment.studentCourse.enrollment.student:id,admission_number,first_name,last_name,other_names',
            'paceAssignment.studentCourse.enrollment.learningCenter:id,name',
            'paceAssignment.studentCourse.enrollment.level:id,name',
            'academicYear:id,name', 'term:id,name', 'recordedBy:id,name',
            'correction:id,corrects_movement_id',
        ]);
        $this->applyIssuingFilters($query, $filters);

        return $query->latest('recorded_at')->latest('id')->get()->map(function (StockMovement $movement): array {
            $assignment = $movement->paceAssignment;
            $enrollment = $assignment->studentCourse->enrollment;
            $student = $enrollment->student;

            return [
                'movement_id' => $movement->id,
                'assignment_id' => $assignment->id,
                'student_id' => $student->id,
                'admission_number' => $student->admission_number,
                'student' => $student->full_name,
                'learning_center_id' => $enrollment->learning_center_id,
                'learning_center' => $enrollment->learning_center_id === null
                    ? 'Unassigned'
                    : $enrollment->learningCenter->name,
                'level' => $enrollment->level->name,
                'course' => $assignment->studentCourse->course->name,
                'pace' => $assignment->pace->number,
                'pace_title' => $assignment->pace->title,
                'quantity' => abs($movement->quantity),
                'issued_date' => $movement->recorded_at->toDateString(),
                'issued_time' => $movement->recorded_at->format('g:i A'),
                'issued_at' => $movement->recorded_at->toIso8601String(),
                'academic_year' => $movement->academicYear?->name,
                'term' => $movement->term?->name,
                'issued_by' => $movement->recordedBy->name,
                'status' => $movement->correction === null ? 'Issued' : 'Reversed',
                'reference' => $movement->reference,
            ];
        })->values();
    }

    /** @param array<string, mixed> $filters
     * @return Collection<int, covariant array<string, mixed>>
     */
    private function studentProgressRows(array $filters): Collection
    {
        $query = StudentCourse::query()->with([
            'enrollment.student:id,admission_number,first_name,last_name,other_names,status',
            'enrollment.level:id,name', 'enrollment.academicYear:id,name', 'enrollment.term:id,name',
            'course:id,name,subject_id', 'course.subject:id,name', 'currentPace:id,number,title',
            'paceAssignments:id,student_course_id,pace_id,status,attempt_cycle,assigned_at,issued_at,started_at,submitted_at,completed_at',
            'paceAssignments.pace:id,number',
        ]);
        $this->applyStudentCourseFilters($query, $filters);

        $requirements = CurriculumRequirement::query()->where('is_active', true)->withCount('paces')->get()
            ->keyBy(fn (CurriculumRequirement $requirement): string => "{$requirement->level_id}:{$requirement->course_id}");
        $courseCounts = Course::query()->withCount(['paces' => fn ($query) => $query->where('is_active', true)])
            ->pluck('paces_count', 'id');

        return $query->orderBy('course_id')->get()->map(function (StudentCourse $studentCourse) use ($requirements, $courseCounts): array {
            $assignments = $studentCourse->paceAssignments;
            $completed = $assignments->where('status', PaceAssignmentStatus::Passed)->pluck('pace_id')->unique()->count();
            $requirementKey = "{$studentCourse->enrollment->level_id}:{$studentCourse->course_id}";
            $sequenceTotal = (int) ($requirements->has($requirementKey)
                ? $requirements->get($requirementKey)->paces_count
                : $courseCounts->get($studentCourse->course_id, 0));
            $current = $assignments->reject(fn (PaceAssignment $assignment): bool => $assignment->status->isTerminal())
                ->sortByDesc('assigned_at')->first();
            $lastActivity = $assignments->flatMap(fn (PaceAssignment $assignment): array => array_filter([
                $assignment->assigned_at, $assignment->issued_at, $assignment->started_at,
                $assignment->submitted_at, $assignment->completed_at,
            ]))->sortDesc()->first() ?? $studentCourse->enrollment->enrolled_on;
            $daysInactive = (int) $lastActivity->diffInDays(now());
            $student = $studentCourse->enrollment->student;

            return [
                'student_id' => $student->id, 'student_course_id' => $studentCourse->id,
                'admission_number' => $student->admission_number, 'student' => $student->full_name,
                'student_status' => $student->status->value, 'level_id' => $studentCourse->enrollment->level_id,
                'level' => $studentCourse->enrollment->level->name, 'course_id' => $studentCourse->course_id,
                'course' => $studentCourse->course->name, 'current_assignment_id' => $current?->id,
                'current_pace' => $current !== null ? $current->pace->number : $this->paceNumber($studentCourse->currentPace),
                'assignment_status' => $current?->status->label() ?? Str::headline($studentCourse->status->value),
                'completed_paces' => $completed, 'sequence_total' => $sequenceTotal,
                'progress_percent' => $sequenceTotal > 0 ? round(($completed / $sequenceTotal) * 100, 1) : 0.0,
                'failed_cycles' => $assignments->filter(fn (PaceAssignment $assignment): bool => $assignment->status === PaceAssignmentStatus::Failed || $assignment->attempt_cycle > 1)->count(),
                'days_inactive' => $daysInactive, 'inactive' => $studentCourse->status === StudentCourseStatus::Active && $daysInactive >= 14,
            ];
        })->values();
    }

    /** @param array<string, mixed> $filters
     * @return Collection<int, covariant array<string, mixed>>
     */
    private function courseProgressRows(array $filters): Collection
    {
        return $this->studentProgressRows($filters)
            ->groupBy(fn (array $row): string => "{$row['level_id']}:{$row['course_id']}")
            ->map(function (Collection $rows): array {
                $first = $rows->first();

                return [
                    'level_id' => $first['level_id'], 'level' => $first['level'],
                    'course_id' => $first['course_id'], 'course' => $first['course'],
                    'students' => $rows->count(),
                    'active_students' => $rows->where('student_status', 'active')->count(),
                    'completed_courses' => $rows->where('assignment_status', 'Completed')->count(),
                    'completed_paces' => $rows->sum('completed_paces'),
                    'average_progress' => round((float) $rows->avg('progress_percent'), 1),
                    'failed_cycles' => $rows->sum('failed_cycles'),
                    'inactive_students' => $rows->where('inactive', true)->count(),
                ];
            })->sortBy([['level', 'asc'], ['course', 'asc']])->values();
    }

    /** @param array<string, mixed> $filters
     * @return Collection<int, covariant array<string, mixed>>
     */
    private function pendingWorkRows(array $filters): Collection
    {
        $statuses = [
            PaceAssignmentStatus::Assigned, PaceAssignmentStatus::InProgress,
            PaceAssignmentStatus::AwaitingSelfTest, PaceAssignmentStatus::AwaitingPaceTest,
            PaceAssignmentStatus::Failed,
        ];
        $query = PaceAssignment::query()->whereIn('status', $statuses)->with([
            'pace:id,course_id,number,title', 'studentCourse.course:id,name',
            'studentCourse.enrollment.level:id,name',
            'studentCourse.enrollment.student:id,admission_number,first_name,last_name,other_names,status',
        ]);
        $this->applyAssignmentFilters($query, $filters);

        return $query->oldest('assigned_at')->get()->map(function (PaceAssignment $assignment): array {
            $waitingSince = $assignment->submitted_at ?? $assignment->started_at ?? $assignment->issued_at ?? $assignment->assigned_at;
            $ageDays = (int) $waitingSince->diffInDays(now());
            $threshold = in_array($assignment->status, [PaceAssignmentStatus::AwaitingSelfTest, PaceAssignmentStatus::AwaitingPaceTest], true) ? 3
                : ($assignment->status === PaceAssignmentStatus::Failed ? 7 : 14);
            $student = $assignment->studentCourse->enrollment->student;

            return [
                'assignment_id' => $assignment->id, 'student_id' => $student->id,
                'admission_number' => $student->admission_number, 'student' => $student->full_name,
                'level' => $assignment->studentCourse->enrollment->level->name,
                'course' => $assignment->studentCourse->course->name,
                'pace' => $assignment->pace->number, 'status' => $assignment->status->label(),
                'next_action' => match ($assignment->status) {
                    PaceAssignmentStatus::Assigned => 'Physical issue', PaceAssignmentStatus::InProgress => 'Submit Self Test',
                    PaceAssignmentStatus::AwaitingSelfTest => 'Record Self Test', PaceAssignmentStatus::AwaitingPaceTest => 'Record PACE Test',
                    PaceAssignmentStatus::Failed => 'Approve retest or repeat', default => 'Review',
                },
                'waiting_since' => $waitingSince->toDateString(), 'age_days' => $ageDays, 'overdue' => $ageDays >= $threshold,
            ];
        })->values();
    }

    /** @param array<string, mixed> $filters
     * @return Collection<int, covariant array<string, mixed>>
     */
    private function inventoryRows(array $filters): Collection
    {
        $query = InventoryItem::query()->with([
            'pace:id,course_id,number,title', 'pace.course:id,name,subject_id', 'pace.course.subject:id,name',
            'movements:id,inventory_item_id,type,quantity,recorded_at',
        ])->when($filters['course_id'] ?? null, fn ($query, $courseId) => $query->whereHas('pace', fn ($query) => $query->where('course_id', $courseId)));

        $rows = $query->orderBy('sku')->get()->map(function (InventoryItem $item) use ($filters): array {
            $periodMovements = $item->movements
                ->when($filters['date_from'] ?? null, fn (Collection $movements, $date) => $movements->filter(fn ($movement): bool => $movement->recorded_at->toDateString() >= $date))
                ->when($filters['date_to'] ?? null, fn (Collection $movements, $date) => $movements->filter(fn ($movement): bool => $movement->recorded_at->toDateString() <= $date));
            $onHand = (int) $item->movements->sum('quantity');
            $stockStatus = $onHand === 0 ? 'Out of stock' : ($onHand <= $item->reorder_level ? 'Low stock' : 'Available');

            return [
                'inventory_item_id' => $item->id, 'sku' => $item->sku,
                'item_type' => $item->item_type->label(), 'course' => $this->paceCourseName($item->pace),
                'pace' => $this->paceNumber($item->pace), 'on_hand' => $onHand,
                'received' => (int) $periodMovements->where('type', StockMovementType::Receipt)->sum('quantity'),
                'issued' => abs((int) $periodMovements->where('type', StockMovementType::Issue)->sum('quantity')),
                'reorder_level' => $item->reorder_level, 'stock_status' => $stockStatus,
            ];
        });

        return $rows->when(($filters['stock'] ?? '') === 'low', fn (Collection $rows) => $rows->filter(fn (array $row): bool => $row['on_hand'] <= $row['reorder_level']))
            ->when(($filters['stock'] ?? '') === 'out', fn (Collection $rows) => $rows->where('on_hand', 0))
            ->when(($filters['stock'] ?? '') === 'available', fn (Collection $rows) => $rows->filter(fn (array $row): bool => $row['on_hand'] > 0))
            ->values();
    }

    /** @param Builder<StudentCourse> $query
     * @param  array<string, mixed>  $filters
     */
    private function applyStudentCourseFilters($query, array $filters): void
    {
        $query->when($filters['academic_year_id'] ?? null, fn ($query, $id) => $query->whereHas('enrollment', fn ($query) => $query->where('academic_year_id', $id)))
            ->when($filters['term_id'] ?? null, fn ($query, $id) => $query->whereHas('enrollment', fn ($query) => $query->where('term_id', $id)))
            ->when($filters['level_id'] ?? null, fn ($query, $id) => $query->whereHas('enrollment', fn ($query) => $query->where('level_id', $id)))
            ->when($filters['course_id'] ?? null, fn ($query, $id) => $query->where('course_id', $id))
            ->when($filters['student_status'] ?? null, fn ($query, $status) => $query->whereHas('enrollment.student', fn ($query) => $query->where('status', $status)))
            ->when(array_key_exists('learning_center_ids', $filters), fn ($query) => $query->whereHas(
                'enrollment',
                fn ($query) => $query->whereIn('learning_center_id', $filters['learning_center_ids']),
            ))
            ->when($filters['assignment_status'] ?? null, fn ($query, $status) => $query->whereHas('paceAssignments', fn ($query) => $query->where('status', $status)))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereHas('paceAssignments', fn ($query) => $query->whereDate('assigned_at', '>=', $date)))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereHas('paceAssignments', fn ($query) => $query->whereDate('assigned_at', '<=', $date)));
    }

    /** @param Builder<PaceAssignment> $query
     * @param  array<string, mixed>  $filters
     */
    private function applyAssignmentFilters($query, array $filters): void
    {
        $query->when($filters['academic_year_id'] ?? null, fn ($query, $id) => $query->where('academic_year_id', $id))
            ->when($filters['term_id'] ?? null, fn ($query, $id) => $query->where('term_id', $id))
            ->when($filters['level_id'] ?? null, fn ($query, $id) => $query->whereHas('studentCourse.enrollment', fn ($query) => $query->where('level_id', $id)))
            ->when($filters['course_id'] ?? null, fn ($query, $id) => $query->whereHas('studentCourse', fn ($query) => $query->where('course_id', $id)))
            ->when($filters['student_status'] ?? null, fn ($query, $status) => $query->whereHas('studentCourse.enrollment.student', fn ($query) => $query->where('status', $status)))
            ->when(array_key_exists('learning_center_ids', $filters), fn ($query) => $query->whereHas(
                'studentCourse.enrollment',
                fn ($query) => $query->whereIn('learning_center_id', $filters['learning_center_ids']),
            ))
            ->when($filters['assignment_status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('assigned_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('assigned_at', '<=', $date));
    }

    /** @param Builder<StockMovement> $query
     * @param  array<string, mixed>  $filters
     */
    private function applyIssuingFilters($query, array $filters): void
    {
        $query->where('type', StockMovementType::Issue)
            ->whereNotNull('pace_assignment_id')
            ->when($filters['academic_year_id'] ?? null, fn ($query, $id) => $query->where('academic_year_id', $id))
            ->when($filters['term_id'] ?? null, fn ($query, $id) => $query->where('term_id', $id))
            ->when($filters['learning_center_id'] ?? null, fn ($query, $id) => $query->whereHas(
                'paceAssignment.studentCourse.enrollment',
                fn ($query) => $query->where('learning_center_id', $id),
            ))
            ->when($filters['level_id'] ?? null, fn ($query, $id) => $query->whereHas(
                'paceAssignment.studentCourse.enrollment',
                fn ($query) => $query->where('level_id', $id),
            ))
            ->when($filters['course_id'] ?? null, fn ($query, $id) => $query->whereHas(
                'paceAssignment.studentCourse',
                fn ($query) => $query->where('course_id', $id),
            ))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('recorded_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('recorded_at', '<=', $date));
    }

    /** @param array<string, mixed> $filters */
    private function studentCourseCount(array $filters): int
    {
        $query = StudentCourse::query();
        $this->applyStudentCourseFilters($query, $filters);

        return $query->count();
    }

    /** @param array<string, mixed> $filters */
    private function courseProgressCount(array $filters): int
    {
        $query = StudentCourse::query();
        $this->applyStudentCourseFilters($query, $filters);

        $groups = $query
            ->join('enrollments', 'enrollments.id', '=', 'student_courses.enrollment_id')
            ->select(['student_courses.course_id', 'enrollments.level_id'])
            ->groupBy('student_courses.course_id', 'enrollments.level_id');

        return DB::query()->fromSub($groups, 'course_progress_groups')->count();
    }

    /** @param array<string, mixed> $filters */
    private function pendingWorkCount(array $filters): int
    {
        $query = PaceAssignment::query()->whereIn('status', [
            PaceAssignmentStatus::Assigned, PaceAssignmentStatus::InProgress,
            PaceAssignmentStatus::AwaitingSelfTest, PaceAssignmentStatus::AwaitingPaceTest,
            PaceAssignmentStatus::Failed,
        ]);
        $this->applyAssignmentFilters($query, $filters);

        return $query->count();
    }

    /** @param array<string, mixed> $filters */
    private function issuingCount(array $filters): int
    {
        $query = StockMovement::query();
        $this->applyIssuingFilters($query, $filters);

        return $query->count();
    }

    /** @param array<string, mixed> $filters */
    private function inventoryCount(array $filters): int
    {
        $balanceSql = '(SELECT COALESCE(SUM(quantity), 0) FROM stock_movements WHERE stock_movements.inventory_item_id = inventory_items.id)';

        return InventoryItem::query()
            ->when($filters['course_id'] ?? null, fn ($query, $courseId) => $query->whereHas('pace', fn ($query) => $query->where('course_id', $courseId)))
            ->when(($filters['stock'] ?? '') === 'low', fn ($query) => $query->whereRaw("{$balanceSql} <= inventory_items.reorder_level"))
            ->when(($filters['stock'] ?? '') === 'out', fn ($query) => $query->whereRaw("{$balanceSql} = 0"))
            ->when(($filters['stock'] ?? '') === 'available', fn ($query) => $query->whereRaw("{$balanceSql} > 0"))
            ->count();
    }

    /** @param Collection<int, covariant array<string, mixed>> $rows
     * @return array<string, int|float>
     */
    private function summary(ReportType $type, Collection $rows): array
    {
        return match ($type) {
            ReportType::StudentProgress => [
                'records' => $rows->count(), 'completed_paces' => $rows->sum('completed_paces'),
                'average_progress' => round((float) ($rows->avg('progress_percent') ?? 0), 1), 'attention' => $rows->where('inactive', true)->count(),
            ],
            ReportType::CourseProgress => [
                'records' => $rows->count(), 'students' => $rows->sum('students'),
                'average_progress' => round((float) ($rows->avg('average_progress') ?? 0), 1), 'attention' => $rows->sum('inactive_students'),
            ],
            ReportType::PendingWork => [
                'records' => $rows->count(), 'overdue' => $rows->where('overdue', true)->count(),
                'awaiting_tests' => $rows->whereIn('status', ['Awaiting Self Test', 'Awaiting PACE Test'])->count(),
                'failed' => $rows->where('status', 'Failed')->count(),
            ],
            ReportType::PaceIssuing => [
                'records' => $rows->count(),
                'copies_issued' => $rows->where('status', 'Issued')->sum('quantity'),
                'students' => $rows->where('status', 'Issued')->pluck('student_id')->unique()->count(),
                'reversed' => $rows->where('status', 'Reversed')->count(),
            ],
            ReportType::Inventory => [
                'records' => $rows->count(), 'on_hand' => $rows->sum('on_hand'),
                'low_stock' => $rows->filter(fn (array $row): bool => $row['on_hand'] <= $row['reorder_level'])->count(),
                'out_of_stock' => $rows->where('on_hand', 0)->count(),
            ],
        };
    }

    /**
     * @param  list<string>  $fields
     * @param  array<string, mixed>  $row
     * @return list<string|int|float|null>
     */
    private function exportRow(array $fields, array $row): array
    {
        return array_values(collect($fields)
            ->map(function (string $field) use ($row): string|int|float|null {
                $value = $row[$field] ?? null;

                if (is_bool($value)) {
                    return $value ? 'Yes' : 'No';
                }

                return is_string($value) || is_int($value) || is_float($value) ? $value : null;
            })
            ->all());
    }

    private function paceNumber(?Pace $pace): string
    {
        return $pace === null ? '—' : $pace->number;
    }

    private function paceCourseName(?Pace $pace): string
    {
        return $pace === null ? '—' : $pace->course->name;
    }
}
