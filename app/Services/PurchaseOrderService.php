<?php

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\User;
use App\PurchaseOrderSource;
use App\PurchaseOrderStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseOrderService
{
    public function __construct(private StockLedgerService $stockLedger) {}

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes, User $actor): PurchaseOrder
    {
        return DB::transaction(function () use ($attributes, $actor): PurchaseOrder {
            $order = PurchaseOrder::query()->create([
                'supplier_id' => $attributes['supplier_id'],
                'source' => $attributes['source'] ?? PurchaseOrderSource::Manual,
                'status' => PurchaseOrderStatus::Draft,
                'expected_on' => $attributes['expected_on'] ?? null,
                'notes' => $attributes['notes'] ?? null,
                'created_by' => $actor->id,
            ]);
            $order->update(['order_number' => sprintf('PO-%s-%05d', now()->format('Y'), $order->id)]);

            foreach ($attributes['lines'] ?? [] as $line) {
                $order->lines()->create($line);
            }

            return $order->fresh(['supplier', 'lines.inventoryItem']);
        }, 3);
    }

    /** @param array<string, mixed> $attributes */
    public function addLine(PurchaseOrder $order, array $attributes): PurchaseOrderLine
    {
        $this->ensureStatus($order, PurchaseOrderStatus::Draft);

        return $order->lines()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function updateLine(PurchaseOrderLine $line, array $attributes): PurchaseOrderLine
    {
        $this->ensureStatus($line->purchaseOrder, PurchaseOrderStatus::Draft);
        $line->update($attributes);

        return $line->fresh();
    }

    public function removeLine(PurchaseOrderLine $line): void
    {
        $this->ensureStatus($line->purchaseOrder, PurchaseOrderStatus::Draft);
        $line->delete();
    }

    public function submit(PurchaseOrder $order, User $actor): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $actor): PurchaseOrder {
            $order = $this->locked($order);
            $this->ensureStatus($order, PurchaseOrderStatus::Draft);
            if (! $order->lines()->exists()) {
                throw ValidationException::withMessages(['order' => 'Add at least one item before submitting the order.']);
            }
            if (! $order->supplier()->where('is_active', true)->exists()) {
                throw ValidationException::withMessages(['supplier' => 'The selected supplier is inactive.']);
            }
            $order->update([
                'status' => PurchaseOrderStatus::Submitted,
                'submitted_by' => $actor->id,
                'submitted_at' => now(),
            ]);

            return $order->fresh();
        }, 3);
    }

    public function decide(PurchaseOrder $order, bool $approve, ?string $reason, User $actor): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $approve, $reason, $actor): PurchaseOrder {
            $order = $this->locked($order);
            $this->ensureStatus($order, PurchaseOrderStatus::Submitted);
            $order->update([
                'status' => $approve ? PurchaseOrderStatus::Approved : PurchaseOrderStatus::Rejected,
                'decided_by' => $actor->id,
                'decided_at' => now(),
                'decision_reason' => filled($reason) ? trim($reason) : null,
            ]);

            return $order->fresh();
        }, 3);
    }

    public function markSent(PurchaseOrder $order, User $actor): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $actor): PurchaseOrder {
            $order = $this->locked($order);
            $this->ensureStatus($order, PurchaseOrderStatus::Approved);
            $order->update([
                'status' => PurchaseOrderStatus::Sent,
                'sent_by' => $actor->id,
                'sent_at' => now(),
            ]);

            return $order->fresh();
        }, 3);
    }

    public function cancel(PurchaseOrder $order, string $reason, User $actor): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $reason, $actor): PurchaseOrder {
            $order = $this->locked($order);
            if (in_array($order->status, [
                PurchaseOrderStatus::Received,
                PurchaseOrderStatus::Rejected,
                PurchaseOrderStatus::Cancelled,
            ], true)) {
                throw ValidationException::withMessages(['order' => 'This order can no longer be cancelled.']);
            }
            $order->update([
                'status' => PurchaseOrderStatus::Cancelled,
                'cancelled_by' => $actor->id,
                'cancelled_at' => now(),
                'cancellation_reason' => trim($reason),
            ]);

            return $order->fresh();
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function receive(PurchaseOrder $order, array $attributes, User $actor): GoodsReceipt
    {
        return DB::transaction(function () use ($order, $attributes, $actor): GoodsReceipt {
            $order = $this->locked($order);
            if (! in_array($order->status, [PurchaseOrderStatus::Sent, PurchaseOrderStatus::PartiallyReceived], true)) {
                throw ValidationException::withMessages(['order' => 'Only sent orders can be received.']);
            }

            $rawLines = $attributes['lines'] ?? null;
            if (! is_array($rawLines)) {
                throw ValidationException::withMessages(['lines' => 'Receipt lines are required.']);
            }

            $submittedLineValues = [];
            foreach ($rawLines as $line) {
                if (! is_array($line)
                    || ! isset($line['purchase_order_line_id'], $line['quantity_received'])
                    || ! is_numeric($line['purchase_order_line_id'])
                    || ! is_numeric($line['quantity_received'])) {
                    throw ValidationException::withMessages(['lines' => 'One or more receipt lines are invalid.']);
                }

                $lineId = (int) $line['purchase_order_line_id'];
                $quantity = (int) $line['quantity_received'];
                if ($quantity > 0) {
                    $submittedLineValues[$lineId] = [
                        'purchase_order_line_id' => $lineId,
                        'quantity_received' => $quantity,
                    ];
                }
            }

            $submittedLines = collect($submittedLineValues);
            $orderLines = PurchaseOrderLine::query()
                ->where('purchase_order_id', $order->id)
                ->whereIn('id', $submittedLines->keys())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            if ($orderLines->count() !== $submittedLines->count()) {
                throw ValidationException::withMessages(['lines' => 'One or more receipt lines do not belong to this order.']);
            }

            $receipt = GoodsReceipt::query()->create([
                'purchase_order_id' => $order->id,
                'delivery_reference' => trim($attributes['delivery_reference']),
                'received_by' => $actor->id,
                'received_at' => $attributes['received_at'],
                'notes' => $attributes['notes'] ?? null,
            ]);
            $receipt->update(['receipt_number' => sprintf('GRN-%s-%05d', now()->format('Y'), $receipt->id)]);

            foreach ($submittedLines as $lineId => $submittedLine) {
                $receiptLine = GoodsReceiptLine::query()->create([
                    'goods_receipt_id' => $receipt->id,
                    'purchase_order_line_id' => $lineId,
                    'quantity_received' => $submittedLine['quantity_received'],
                ]);
                $movement = $this->stockLedger->receiveGoodsReceiptLine($receiptLine, $actor);
                $receiptLine->update(['stock_movement_id' => $movement->id]);
            }

            $fullyReceived = $order->lines()->get()->every(
                fn (PurchaseOrderLine $line): bool => $line->outstandingQuantity() === 0,
            );
            $order->update([
                'status' => $fullyReceived ? PurchaseOrderStatus::Received : PurchaseOrderStatus::PartiallyReceived,
            ]);

            return $receipt->fresh(['lines.stockMovement', 'lines.purchaseOrderLine.inventoryItem']);
        }, 3);
    }

    private function locked(PurchaseOrder $order): PurchaseOrder
    {
        return PurchaseOrder::query()->lockForUpdate()->findOrFail($order->id);
    }

    private function ensureStatus(PurchaseOrder $order, PurchaseOrderStatus $status): void
    {
        if ($order->status !== $status) {
            throw ValidationException::withMessages(['order' => "This action requires an order in {$status->label()} status."]);
        }
    }
}
