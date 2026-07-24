<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\PurchaseOrderLine;
use App\PurchaseOrderStatus;
use Illuminate\Support\Collection;

class ReorderService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function suggestions(): Collection
    {
        $items = InventoryItem::query()
            ->where('is_active', true)
            ->where('target_stock_level', '>', 0)
            ->with(['pace:id,course_id,number,title', 'pace.course:id,name,subject_id', 'pace.course.subject:id,name'])
            ->withSum('movements as on_hand', 'quantity')
            ->orderBy('sku')
            ->get();
        $onOrder = PurchaseOrderLine::query()
            ->whereHas('purchaseOrder', fn ($query) => $query->whereIn('status', [
                PurchaseOrderStatus::Approved,
                PurchaseOrderStatus::Sent,
                PurchaseOrderStatus::PartiallyReceived,
            ]))
            ->withSum('effectiveGoodsReceiptLines as received_quantity', 'quantity_received')
            ->get()
            ->groupBy('inventory_item_id')
            ->map(fn (Collection $lines): int => $lines->sum(
                fn (PurchaseOrderLine $line): int => max($line->quantity_ordered - (int) $line->received_quantity, 0),
            ));

        return $items->toBase()
            ->filter(function (InventoryItem $item) use ($onOrder): bool {
                $onHand = (int) $item->on_hand;
                $pending = (int) ($onOrder[$item->id] ?? 0);

                return $onHand <= $item->reorder_level
                    && $item->target_stock_level - ($onHand + $pending) > 0;
            })
            ->map(
                fn (InventoryItem $item): array => $this->suggestionFor($item, (int) ($onOrder[$item->id] ?? 0)),
            )
            ->values();
    }

    /** @return array<string, mixed> */
    private function suggestionFor(InventoryItem $item, int $pending): array
    {
        $onHand = (int) $item->on_hand;

        return [
            ...$item->toArray(),
            'on_hand' => $onHand,
            'on_order' => $pending,
            'suggested_quantity' => max($item->target_stock_level - ($onHand + $pending), 0),
        ];
    }
}
