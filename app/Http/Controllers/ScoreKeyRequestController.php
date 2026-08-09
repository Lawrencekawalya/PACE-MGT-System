<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelScoreKeyRequestRequest;
use App\Http\Requests\StoreScoreKeyRequestRequest;
use App\InventoryItemType;
use App\Models\InventoryItem;
use App\Models\LearningCenter;
use App\Models\ScoreKeyRequest;
use App\Models\StockMovement;
use App\PermissionName;
use App\RoleName;
use App\ScoreKeyRequestStatus;
use App\ScoreKeyRequestType;
use App\Services\ScoreKeyRequestService;
use App\StockMovementType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ScoreKeyRequestController extends Controller
{
    public function __construct(private ScoreKeyRequestService $requests) {}

    public function index(Request $request): Response
    {
        $canRequest = $request->user()->hasRole(RoleName::Teacher)
            && $request->user()->can(PermissionName::RequestScoreKeys->value);
        $canIssue = $request->user()->can(PermissionName::IssueScoreKeys->value);
        abort_unless($canRequest || $canIssue, 403);
        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'status' => $request->string('status')->toString(),
            'learning_center_id' => $request->integer('learning_center_id') ?: null,
        ];
        $baseQuery = ScoreKeyRequest::query()
            ->when(! $canIssue, fn ($query) => $query->where('teacher_id', $request->user()->id));
        $query = (clone $baseQuery)
            ->with([
                'teacher:id,name',
                'learningCenter:id,name,code',
                'inventoryItem:id,pace_id,sku',
                'inventoryItem.pace:id,course_id,number,title',
                'inventoryItem.pace.course:id,name',
                'issueMovements' => fn ($query) => $query
                    ->with(['recordedBy:id,name', 'academicYear:id,name', 'term:id,name'])
                    ->latest('recorded_at'),
            ])
            ->withSum('issueMovements as issued_quantity', 'quantity')
            ->when($filters['learning_center_id'], fn ($query, $id) => $query->where('learning_center_id', $id))
            ->when(in_array($filters['status'], array_column(ScoreKeyRequestStatus::cases(), 'value'), true), fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['search'], fn ($query, $search) => $query->where(fn ($query) => $query
                ->whereHas('teacher', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                ->orWhereHas('inventoryItem', fn ($query) => $query->where('sku', 'like', "%{$search}%"))
                ->orWhereHas('inventoryItem.pace', fn ($query) => $query->where('number', 'like', "%{$search}%")->orWhere('title', 'like', "%{$search}%"))
                ->orWhereHas('inventoryItem.pace.course', fn ($query) => $query->where('name', 'like', "%{$search}%"))))
            ->latest('requested_at')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (ScoreKeyRequest $scoreKeyRequest): array => $this->requestRow($scoreKeyRequest, $request->user()->id));
        $scoreKeys = InventoryItem::query()
            ->where('item_type', InventoryItemType::ScoreKey)
            ->where('is_active', true)
            ->whereNotNull('pace_id')
            ->with(['pace:id,course_id,number,title', 'pace.course:id,name'])
            ->withSum('movements as on_hand', 'quantity')
            ->orderBy('sku')
            ->get()
            ->map(fn (InventoryItem $item): array => [
                'id' => $item->id,
                'label' => "{$item->pace->course->name} · PACE {$item->pace->number}".($item->pace->title ? " · {$item->pace->title}" : '')." · {$item->sku}",
                'on_hand' => (int) ($item->on_hand ?? 0),
            ]);
        $learningCenters = $canRequest
            ? $request->user()->learningCenters()
                ->where('learning_centers.is_active', true)
                ->orderBy('learning_centers.name')
                ->get(['learning_centers.id', 'name', 'code'])
            : LearningCenter::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']);
        $assignedLearningCenter = $canRequest && $learningCenters->count() === 1
            ? $learningCenters->first()
            : null;
        $learningCenterAssignmentIssue = $canRequest && $assignedLearningCenter === null
            ? ($learningCenters->isEmpty()
                ? 'No active learning center is assigned to your account.'
                : 'More than one active learning center is assigned to your account. Ask an administrator to retain the correct assignment.')
            : null;

        return Inertia::render('score-key-requests/Index', [
            'requests' => $query,
            'filters' => $filters,
            'summary' => [
                'pending' => (clone $baseQuery)->where('status', ScoreKeyRequestStatus::Pending)->count(),
                'partially_issued' => (clone $baseQuery)->where('status', ScoreKeyRequestStatus::PartiallyIssued)->count(),
                'issued' => (clone $baseQuery)->where('status', ScoreKeyRequestStatus::Issued)->count(),
            ],
            'scoreKeys' => $scoreKeys,
            'learningCenters' => $learningCenters,
            'assignedLearningCenter' => $assignedLearningCenter,
            'learningCenterAssignmentIssue' => $learningCenterAssignmentIssue,
            'requestTypes' => collect(ScoreKeyRequestType::cases())->map(fn ($type) => ['value' => $type->value, 'label' => $type->label()]),
            'statuses' => collect(ScoreKeyRequestStatus::cases())->map(fn ($status) => ['value' => $status->value, 'label' => $status->label()]),
            'myScoreKeys' => $canRequest ? $this->teacherScoreKeys($request->user()->id) : [],
            'canRequest' => $canRequest,
            'canIssue' => $canIssue,
        ]);
    }

    public function store(StoreScoreKeyRequestRequest $request): RedirectResponse
    {
        $this->requests->create($request->validated(), $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Score Key request submitted.']);

        return back();
    }

    public function cancel(CancelScoreKeyRequestRequest $request, ScoreKeyRequest $scoreKeyRequest): RedirectResponse
    {
        $this->requests->cancel($scoreKeyRequest, $request->validated('reason'), $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Score Key request cancelled.']);

        return back();
    }

    /** @return array<string, mixed> */
    private function requestRow(ScoreKeyRequest $request, int $userId): array
    {
        $issued = abs((int) ($request->getAttribute('issued_quantity') ?? 0));

        return [
            'id' => $request->id,
            'teacher' => $request->teacher->name,
            'learning_center' => $request->learningCenter->name,
            'inventory_item' => [
                'id' => $request->inventoryItem->id,
                'sku' => $request->inventoryItem->sku,
                'course' => $request->inventoryItem->pace->course->name,
                'pace' => $request->inventoryItem->pace->number,
                'title' => $request->inventoryItem->pace->title,
            ],
            'request_type' => $request->request_type->value,
            'request_type_label' => $request->request_type->label(),
            'quantity_requested' => $request->quantity_requested,
            'quantity_issued' => $issued,
            'quantity_outstanding' => max(0, $request->quantity_requested - $issued),
            'status' => $request->status->value,
            'status_label' => $request->status->label(),
            'request_reason' => $request->request_reason,
            'notes' => $request->notes,
            'rejection_reason' => $request->rejection_reason,
            'requested_at' => $request->requested_at->toIso8601String(),
            'can_cancel' => $request->teacher_id === $userId && $request->status === ScoreKeyRequestStatus::Pending,
            'can_issue' => $request->status->canIssue(),
            'can_reject' => $request->status === ScoreKeyRequestStatus::Pending,
            'issues' => $request->issueMovements->map(fn (StockMovement $movement): array => [
                'id' => $movement->id,
                'quantity' => abs($movement->quantity),
                'issued_by' => $movement->recordedBy?->name,
                'issued_at' => $movement->recorded_at->toIso8601String(),
                'period' => $movement->academicYear === null ? null : $movement->academicYear->name.' · '.$movement->term?->name,
                'notes' => $movement->reason,
            ])->values(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function teacherScoreKeys(int $teacherId): array
    {
        return StockMovement::query()
            ->where('issued_to_user_id', $teacherId)
            ->where('type', StockMovementType::Issue)
            ->whereNotNull('score_key_request_id')
            ->whereDoesntHave('correction')
            ->with(['inventoryItem.pace.course:id,name', 'inventoryItem.pace:id,course_id,number,title'])
            ->latest('recorded_at')
            ->get()
            ->groupBy('inventory_item_id')
            ->map(function (Collection $movements): array {
                $latest = $movements->firstOrFail();

                return [
                    'inventory_item_id' => $latest->inventory_item_id,
                    'sku' => $latest->inventoryItem->sku,
                    'course' => $latest->inventoryItem->pace->course->name,
                    'pace' => $latest->inventoryItem->pace->number,
                    'title' => $latest->inventoryItem->pace->title,
                    'quantity' => abs((int) $movements->sum('quantity')),
                    'last_issued_at' => $latest->recorded_at->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }
}
