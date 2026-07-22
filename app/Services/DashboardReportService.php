<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\PaceAssignment;
use App\Models\PaceRetryApproval;
use App\Models\StockMovement;
use App\Models\Student;
use App\Models\User;
use App\PaceAssignmentStatus;
use App\RetryApprovalStatus;
use App\RoleName;
use App\StockMovementType;
use App\StudentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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

        return [
            'metrics' => [
                'active_students' => Student::query()->visibleTo($user)->where('status', StudentStatus::Active)->count(),
                'active_assignments' => PaceAssignment::query()->visibleTo($user)->whereIn('status', collect(PaceAssignmentStatus::cases())->reject->isTerminal()->map->value)->count(),
                'pending_tests' => PaceAssignment::query()->visibleTo($user)->whereIn('status', [PaceAssignmentStatus::AwaitingSelfTest, PaceAssignmentStatus::AwaitingPaceTest])->count(),
                'pending_approvals' => PaceRetryApproval::query()->where('status', RetryApprovalStatus::Pending)->whereHas('assignment.studentCourse.enrollment.student', fn ($query) => $this->scopeStudentTeacher($query, $user))->count(),
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

    /** @param Builder<Model> $query */
    private function scopeStudentTeacher(Builder $query, User $user): void
    {
        if ($user->hasRole(RoleName::Teacher) && ! $user->hasRole(RoleName::Administrator)) {
            $query->where('teacher_id', $user->id);
        }
    }
}
