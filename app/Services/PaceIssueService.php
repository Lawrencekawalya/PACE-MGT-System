<?php

namespace App\Services;

use App\Models\PaceAssignment;
use App\Models\StockMovement;
use App\Models\User;
use App\PaceAssignmentStatus;
use App\StockMovementType;
use Illuminate\Support\Facades\DB;

class PaceIssueService
{
    public function __construct(private PaceAssignmentService $assignments, private StockLedgerService $stock) {}

    public function issue(PaceAssignment $assignment, User $actor): PaceAssignment
    {
        return DB::transaction(function () use ($assignment, $actor): PaceAssignment {
            $assignment = PaceAssignment::query()->lockForUpdate()->findOrFail($assignment->id);
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

            return $correction;
        }, 3);
    }
}
