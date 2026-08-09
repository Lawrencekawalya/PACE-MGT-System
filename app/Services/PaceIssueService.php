<?php

namespace App\Services;

use App\InventoryItemType;
use App\Models\InventoryItem;
use App\Models\PaceAssignment;
use App\Models\StockMovement;
use App\Models\User;
use App\PaceAssignmentStatus;
use App\StockMovementType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaceIssueService
{
    public function __construct(
        private PaceAssignmentService $assignments,
        private StockLedgerService $stock,
        private PaceAccountService $accounts,
    ) {}

    /**
     * @param  list<int>  $assignmentIds
     * @return Collection<int, PaceAssignment>
     */
    public function issueMany(array $assignmentIds, User $actor): Collection
    {
        $assignmentIds = collect($assignmentIds)->unique()->sort()->values()->all();

        return DB::transaction(function () use ($assignmentIds, $actor): Collection {
            $assignments = PaceAssignment::query()
                ->visibleTo($actor)
                ->whereKey($assignmentIds)
                ->lockForUpdate()
                ->get();

            if ($assignments->count() !== count($assignmentIds)) {
                throw ValidationException::withMessages([
                    'assignment_ids' => 'One or more selected assignments are no longer available to you.',
                ]);
            }

            if ($assignments->contains(fn (PaceAssignment $assignment): bool => $assignment->status !== PaceAssignmentStatus::Assigned || $assignment->issued_at !== null)) {
                throw ValidationException::withMessages([
                    'assignment_ids' => 'One or more selected PACEs have already been issued or changed status. Refresh the issuing list and try again.',
                ]);
            }

            $requiredByPace = $assignments->countBy('pace_id');
            $itemsByPace = InventoryItem::query()
                ->whereIn('pace_id', $requiredByPace->keys())
                ->where('item_type', InventoryItemType::PaceBooklet)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('pace_id');

            foreach ($requiredByPace as $paceId => $required) {
                $item = $itemsByPace->get($paceId);
                if ($item === null) {
                    throw ValidationException::withMessages([
                        'stock' => 'A selected PACE does not have a booklet inventory item.',
                    ]);
                }
                if (! $item->is_active || ! $item->is_consumable) {
                    throw ValidationException::withMessages([
                        'stock' => "{$item->sku} is not an active consumable PACE booklet.",
                    ]);
                }

                $onHand = (int) StockMovement::query()
                    ->where('inventory_item_id', $item->id)
                    ->sum('quantity');
                if ($onHand < $required) {
                    throw ValidationException::withMessages([
                        'stock' => "{$item->sku} requires {$required} copies but only {$onHand} are on hand.",
                    ]);
                }
            }

            return $assignments
                ->sortBy(fn (PaceAssignment $assignment): string => sprintf(
                    '%010d-%010d',
                    $itemsByPace->get($assignment->pace_id)->id,
                    $assignment->id,
                ))
                ->map(fn (PaceAssignment $assignment): PaceAssignment => $this->issue($assignment, $actor))
                ->values();
        }, 3);
    }

    public function issue(PaceAssignment $assignment, User $actor): PaceAssignment
    {
        return DB::transaction(function () use ($assignment, $actor): PaceAssignment {
            $assignment = PaceAssignment::query()->lockForUpdate()->findOrFail($assignment->id);
            $this->accounts->chargeIssue($assignment, $actor);
            $this->stock->issueAssignment($assignment, $actor);
            $assignment = $this->assignments->transition($assignment, PaceAssignmentStatus::InProgress, $actor);
            $assignment->forceFill(['issued_by' => $actor->id, 'issued_at' => now()])->save();

            return $assignment->refresh();
        }, 3);
    }

    public function reverse(StockMovement $movement, string $reason, User $actor): StockMovement
    {
        return DB::transaction(function () use ($movement, $reason, $actor): StockMovement {
            $movement = StockMovement::query()->lockForUpdate()->findOrFail($movement->id);
            if ($movement->type !== StockMovementType::Issue || $movement->paceAssignment === null) {
                throw new \InvalidArgumentException('Only a linked student issue can use the issue reversal workflow.');
            }
            $assignment = PaceAssignment::query()->lockForUpdate()->findOrFail($movement->pace_assignment_id);
            $correction = $this->stock->correct($movement, $reason, $actor);
            $this->assignments->reversePhysicalIssue($assignment, $actor, $reason);
            $this->accounts->reverseIssueCharge($assignment, $reason, $actor);

            return $correction;
        }, 3);
    }
}
