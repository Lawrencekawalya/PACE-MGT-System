<?php

namespace App\Models;

use Database\Factories\PurchaseOrderLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $inventory_item_id
 * @property int $quantity_ordered
 * @property-read int|string|null $received_quantity
 */
#[Fillable(['purchase_order_id', 'inventory_item_id', 'quantity_ordered', 'notes'])]
class PurchaseOrderLine extends Model
{
    /** @use HasFactory<PurchaseOrderLineFactory> */
    use HasFactory;

    /** @return BelongsTo<PurchaseOrder, $this> */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /** @return BelongsTo<InventoryItem, $this> */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /** @return HasMany<GoodsReceiptLine, $this> */
    public function goodsReceiptLines(): HasMany
    {
        return $this->hasMany(GoodsReceiptLine::class);
    }

    /** @return HasMany<GoodsReceiptLine, $this> */
    public function effectiveGoodsReceiptLines(): HasMany
    {
        return $this->hasMany(GoodsReceiptLine::class)
            ->whereHas('stockMovement')
            ->whereDoesntHave('stockMovement.correction');
    }

    public function receivedQuantity(): int
    {
        return (int) $this->effectiveGoodsReceiptLines()->sum('quantity_received');
    }

    public function outstandingQuantity(): int
    {
        return max($this->quantity_ordered - $this->receivedQuantity(), 0);
    }

    protected function casts(): array
    {
        return ['quantity_ordered' => 'integer'];
    }
}
