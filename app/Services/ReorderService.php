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

        return $items->map(function (InventoryItem $item) use ($onOrder): array {
            $onHand = (int) $item->on_hand;
            $pending = (int) ($onOrder[$item->id] ?? 0);

            return [
                ...$item->toArray(),
                'on_hand' => $onHand,
                'on_order' => $pending,
                'suggested_quantity' => max($item->target_stock_level - ($onHand + $pending), 0),
            ];
        })->filter(fn (array $item): bool => $item['on_hand'] <= $item['reorder_level']
            && $item['suggested_quantity'] > 0)->values();
    }
}
