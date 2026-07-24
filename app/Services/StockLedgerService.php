<?php

namespace App\Services;

use App\InventoryItemType;
use App\Models\GoodsReceiptLine;
use App\Models\InventoryItem;
use App\Models\PaceAssignment;
use App\Models\PurchaseOrderLine;
use App\Models\StockMovement;
use App\Models\User;
use App\PurchaseOrderStatus;
use App\StockMovementType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockLedgerService
{
    public function receiveGoodsReceiptLine(GoodsReceiptLine $receiptLine, User $actor): StockMovement
    {
        $receiptLine->loadMissing([
            'goodsReceipt.purchaseOrder',
            'purchaseOrderLine.inventoryItem',
        ]);
        $receipt = $receiptLine->goodsReceipt;
        $order = $receipt->purchaseOrder;

        return $this->record(
            $receiptLine->purchaseOrderLine->inventoryItem,
            StockMovementType::Receipt,
            $receiptLine->quantity_received,
            $actor,
            [
                'reference' => "{$receipt->receipt_number} / {$receipt->delivery_reference}",
                'reason' => "Receipt against purchase order {$order->order_number}.",
            ],
        );
    }

    public function postManual(
        InventoryItem $item,
        StockMovementType $type,
        int $quantity,
        ?string $reference,
        ?string $reason,
        User $actor,
    ): StockMovement {
        if (in_array($type, [StockMovementType::Issue, StockMovementType::Correction], true)) {
            throw ValidationException::withMessages(['type' => 'This movement type is created only by its dedicated workflow.']);
        }
        $signedQuantity = match ($type) {
            StockMovementType::Receipt => abs($quantity),
            StockMovementType::Damage, StockMovementType::Loss => -abs($quantity),
            StockMovementType::Adjustment => $quantity,
        };
        if ($signedQuantity === 0) {
            throw ValidationException::withMessages(['quantity' => 'Movement quantity cannot be zero.']);
        }
        if ($type === StockMovementType::Receipt && blank($reference)) {
            throw ValidationException::withMessages(['reference' => 'A delivery reference is required for a receipt.']);
        }
        if ($type !== StockMovementType::Receipt && blank($reason)) {
            throw ValidationException::withMessages(['reason' => 'A reason is required for this movement.']);
        }

        return $this->record($item, $type, $signedQuantity, $actor, [
            'reference' => filled($reference) ? trim($reference) : null,
            'reason' => filled($reason) ? trim($reason) : null,
        ]);
    }

    public function issueAssignment(PaceAssignment $assignment, User $actor): StockMovement
    {
        $item = InventoryItem::query()
            ->where('pace_id', $assignment->pace_id)
            ->where('item_type', InventoryItemType::PaceBooklet)
            ->first();
        if ($item === null) {
            throw ValidationException::withMessages(['stock' => 'This PACE does not have a booklet inventory item.']);
        }
        $assignment->loadMissing('studentCourse.enrollment.student');

        return $this->record($item, StockMovementType::Issue, -1, $actor, [
            'student_id' => $assignment->studentCourse->enrollment->student_id,
            'pace_assignment_id' => $assignment->id,
            'academic_year_id' => $assignment->academic_year_id,
            'term_id' => $assignment->term_id,
            'reference' => "ISSUE-{$assignment->id}",
            'reason' => 'Physical PACE issue to student.',
        ], true);
    }

    public function correct(StockMovement $movement, string $reason, User $actor): StockMovement
    {
        if ($movement->type === StockMovementType::Correction || $movement->corrects_movement_id !== null) {
            throw ValidationException::withMessages(['movement' => 'A correction movement cannot itself be reversed.']);
        }
        if (StockMovement::query()->where('corrects_movement_id', $movement->id)->exists()) {
            throw ValidationException::withMessages(['movement' => 'This movement has already been corrected.']);
        }

        return DB::transaction(function () use ($movement, $reason, $actor): StockMovement {
            $correction = $this->record($movement->inventoryItem, StockMovementType::Correction, -$movement->quantity, $actor, [
                'student_id' => $movement->student_id,
                'pace_assignment_id' => $movement->pace_assignment_id,
                'academic_year_id' => $movement->academic_year_id,
                'term_id' => $movement->term_id,
                'reference' => "CORRECTION-{$movement->id}",
                'reason' => trim($reason),
                'corrects_movement_id' => $movement->id,
            ]);

            $receiptLine = GoodsReceiptLine::query()
                ->where('stock_movement_id', $movement->id)
                ->with('purchaseOrderLine.purchaseOrder.lines')
                ->first();
            if ($receiptLine !== null) {
                $order = $receiptLine->purchaseOrderLine->purchaseOrder;
                $lines = $order->lines;
                $hasEffectiveReceipt = $lines->contains(
                    fn (PurchaseOrderLine $line): bool => $line->receivedQuantity() > 0,
                );
                $fullyReceived = $lines->every(
                    fn (PurchaseOrderLine $line): bool => $line->outstandingQuantity() === 0,
                );
                $order->update([
                    'status' => $fullyReceived
                        ? PurchaseOrderStatus::Received
                        : ($hasEffectiveReceipt ? PurchaseOrderStatus::PartiallyReceived : PurchaseOrderStatus::Sent),
                ]);
            }

            return $correction;
        }, 3);
    }

    /** @param array<string, mixed> $attributes */
    private function record(InventoryItem $item, StockMovementType $type, int $quantity, User $actor, array $attributes, bool $requireConsumable = false): StockMovement
    {
        return DB::transaction(function () use ($item, $type, $quantity, $actor, $attributes, $requireConsumable): StockMovement {
            $item = InventoryItem::query()->lockForUpdate()->findOrFail($item->id);
            if (! $item->is_active) {
                throw ValidationException::withMessages(['inventory_item' => 'Inactive inventory items cannot receive movements.']);
            }
            if ($requireConsumable && (! $item->is_consumable || $item->item_type !== InventoryItemType::PaceBooklet)) {
                throw ValidationException::withMessages(['stock' => 'Student issue requires an active consumable PACE booklet item.']);
            }
            $balance = (int) StockMovement::query()->where('inventory_item_id', $item->id)->sum('quantity');
            $balanceAfter = $balance + $quantity;
            if ($balanceAfter < 0) {
                throw ValidationException::withMessages(['quantity' => "Insufficient stock. {$item->sku} has {$balance} on hand."]);
            }

            return StockMovement::query()->create([
                'inventory_item_id' => $item->id, 'type' => $type, 'quantity' => $quantity,
                'balance_after' => $balanceAfter, 'recorded_by' => $actor->id, 'recorded_at' => now(),
                ...$attributes,
            ]);
        }, 3);
    }
}
