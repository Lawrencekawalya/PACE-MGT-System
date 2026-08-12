<?php

namespace App\Services;

use App\InventoryItemType;
use App\Models\InventoryItem;
use App\Models\ScoreKeyRequest;
use App\Models\StockMovement;
use App\Models\User;
use App\NotificationCategory;
use App\NotificationPriority;
use App\Notifications\OperationalNotification;
use App\RoleName;
use App\ScoreKeyRequestStatus;
use App\ScoreKeyRequestType;
use App\StockMovementType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScoreKeyRequestService
{
    public function __construct(
        private StockLedgerService $stock,
        private ActivityLogger $activityLogger,
        private NotificationRecipientService $recipients,
        private NotificationDispatcher $notifications,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, User $teacher): ScoreKeyRequest
    {
        return DB::transaction(function () use ($data, $teacher): ScoreKeyRequest {
            $teacher = User::query()->lockForUpdate()->findOrFail($teacher->id);
            $itemId = (int) $data['inventory_item_id'];
            $centers = $teacher->learningCenters()
                ->where('learning_centers.is_active', true)
                ->orderBy('learning_centers.id')
                ->get();
            if ($centers->count() !== 1) {
                throw ValidationException::withMessages([
                    'learning_center_id' => $centers->isEmpty()
                        ? 'You must be assigned to an active learning center before requesting a Score Key.'
                        : 'Your account has more than one active learning center. Ask an administrator to retain the correct assignment.',
                ]);
            }
            $center = $centers->firstOrFail();
            $item = InventoryItem::query()
                ->where('item_type', InventoryItemType::ScoreKey)
                ->where('is_active', true)
                ->whereNotNull('pace_id')
                ->findOrFail($itemId);
            $hasOpenRequest = ScoreKeyRequest::query()
                ->where('teacher_id', $teacher->id)
                ->where('inventory_item_id', $item->id)
                ->whereIn('status', [ScoreKeyRequestStatus::Pending, ScoreKeyRequestStatus::PartiallyIssued])
                ->exists();
            if ($hasOpenRequest) {
                throw ValidationException::withMessages([
                    'inventory_item_id' => 'You already have an open request for this Score Key.',
                ]);
            }
            $hasPriorIssue = StockMovement::query()
                ->where('inventory_item_id', $item->id)
                ->where('issued_to_user_id', $teacher->id)
                ->where('type', StockMovementType::Issue)
                ->whereNotNull('score_key_request_id')
                ->whereDoesntHave('correction')
                ->exists();
            $requestType = ScoreKeyRequestType::from((string) $data['request_type']);
            if ($requestType === ScoreKeyRequestType::NewIssue && $hasPriorIssue) {
                throw ValidationException::withMessages([
                    'request_type' => 'You already hold this Score Key. Select Replacement or Additional copy.',
                ]);
            }
            if ($requestType !== ScoreKeyRequestType::NewIssue && ! $hasPriorIssue) {
                throw ValidationException::withMessages([
                    'request_type' => 'Use New issue because no previous copy has been issued to you.',
                ]);
            }

            $request = ScoreKeyRequest::query()->create([
                'teacher_id' => $teacher->id,
                'learning_center_id' => $center->id,
                'inventory_item_id' => $item->id,
                'request_type' => $requestType,
                'quantity_requested' => (int) $data['quantity_requested'],
                'status' => ScoreKeyRequestStatus::Pending,
                'request_reason' => filled($data['request_reason'] ?? null) ? trim($data['request_reason']) : null,
                'notes' => filled($data['notes'] ?? null) ? trim($data['notes']) : null,
                'requested_at' => now(),
            ]);
            $this->activityLogger->record($teacher, 'score-key-request.created', $request, newValues: $request->only([
                'learning_center_id', 'inventory_item_id', 'request_type', 'quantity_requested',
            ]));
            $this->notifications->send(
                $this->recipients->withRole(RoleName::PaceOfficer),
                new OperationalNotification(
                    'Score Key request received',
                    "{$teacher->name} requested {$request->quantity_requested} Score Key copy or copies.",
                    route('score-key-requests.index', ['status' => ScoreKeyRequestStatus::Pending->value]),
                    NotificationCategory::Inventory,
                    NotificationPriority::ActionRequired,
                    "score-key-request:{$request->id}:created",
                    ['score_key_request_id' => $request->id, 'learning_center_id' => $center->id],
                ),
                $teacher,
            );

            return $request;
        }, 3);
    }

    public function issue(ScoreKeyRequest $request, int $quantity, ?string $notes, User $actor): ScoreKeyRequest
    {
        return DB::transaction(function () use ($request, $quantity, $notes, $actor): ScoreKeyRequest {
            $request = ScoreKeyRequest::query()->lockForUpdate()->findOrFail($request->id);
            if (! $request->status->canIssue()) {
                throw ValidationException::withMessages(['quantity' => 'This request can no longer be issued.']);
            }
            $outstanding = $request->outstandingQuantity();
            if ($quantity > $outstanding) {
                throw ValidationException::withMessages([
                    'quantity' => "Only {$outstanding} requested copy or copies remain outstanding.",
                ]);
            }

            $movement = $this->stock->issueScoreKeyRequest($request, $quantity, $notes, $actor);
            $issued = $request->issuedQuantity();
            $request->update([
                'status' => $issued >= $request->quantity_requested
                    ? ScoreKeyRequestStatus::Issued
                    : ScoreKeyRequestStatus::PartiallyIssued,
            ]);
            $this->activityLogger->record($actor, 'score-key-request.issued', $request, newValues: [
                'quantity' => $quantity,
                'stock_movement_id' => $movement->id,
                'status' => $request->status->value,
            ]);
            $teacher = User::query()->find($request->teacher_id);
            if ($teacher !== null) {
                $this->notifications->send([$teacher], new OperationalNotification(
                    $request->status === ScoreKeyRequestStatus::Issued ? 'Score Key request completed' : 'Score Key request partly issued',
                    "{$quantity} Score Key copy or copies were issued for request #{$request->id}.",
                    route('score-key-requests.index'),
                    NotificationCategory::Inventory,
                    $request->status === ScoreKeyRequestStatus::Issued ? NotificationPriority::Information : NotificationPriority::ActionRequired,
                    "score-key-request:{$request->id}:issued:{$movement->id}",
                    ['score_key_request_id' => $request->id],
                ), $actor);
            }

            return $request->refresh();
        }, 3);
    }

    public function reject(ScoreKeyRequest $request, string $reason, User $actor): ScoreKeyRequest
    {
        return DB::transaction(function () use ($request, $reason, $actor): ScoreKeyRequest {
            $request = ScoreKeyRequest::query()->lockForUpdate()->findOrFail($request->id);
            if ($request->status !== ScoreKeyRequestStatus::Pending) {
                throw ValidationException::withMessages(['reason' => 'Only pending requests can be rejected.']);
            }
            $request->update([
                'status' => ScoreKeyRequestStatus::Rejected,
                'rejection_reason' => trim($reason),
                'rejected_by' => $actor->id,
                'rejected_at' => now(),
            ]);
            $this->activityLogger->record($actor, 'score-key-request.rejected', $request, reason: trim($reason));
            $teacher = User::query()->find($request->teacher_id);
            if ($teacher !== null) {
                $this->notifications->send([$teacher], new OperationalNotification(
                    'Score Key request rejected',
                    "Request #{$request->id} was rejected: ".trim($reason),
                    route('score-key-requests.index'),
                    NotificationCategory::Inventory,
                    NotificationPriority::Warning,
                    "score-key-request:{$request->id}:rejected",
                    ['score_key_request_id' => $request->id],
                ), $actor);
            }

            return $request;
        }, 3);
    }

    public function cancel(ScoreKeyRequest $request, ?string $reason, User $teacher): ScoreKeyRequest
    {
        return DB::transaction(function () use ($request, $reason, $teacher): ScoreKeyRequest {
            $request = ScoreKeyRequest::query()->lockForUpdate()->findOrFail($request->id);
            if ($request->teacher_id !== $teacher->id || $request->status !== ScoreKeyRequestStatus::Pending) {
                throw ValidationException::withMessages(['request' => 'Only your own pending request can be cancelled.']);
            }
            $request->update(['status' => ScoreKeyRequestStatus::Cancelled, 'cancelled_at' => now()]);
            $this->activityLogger->record($teacher, 'score-key-request.cancelled', $request, reason: $reason);

            return $request;
        }, 3);
    }
}
