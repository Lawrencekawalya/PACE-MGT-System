<?php

namespace App\Models;

use App\InventoryItemType;
use Database\Factories\InventoryItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property InventoryItemType $item_type
 * @property Pace|null $pace
 * @property-read int|null $on_hand
 */
#[Fillable(['pace_id', 'item_type', 'sku', 'reorder_level', 'target_stock_level', 'is_consumable', 'is_active'])]
class InventoryItem extends Model
{
    /** @use HasFactory<InventoryItemFactory> */
    use HasFactory;

    /** @return BelongsTo<Pace, $this> */
    public function pace(): BelongsTo
    {
        return $this->belongsTo(Pace::class);
    }

    /** @return HasMany<StockMovement, $this> */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->orderByDesc('recorded_at')->orderByDesc('id');
    }

    public function onHand(): int
    {
        return (int) $this->movements()->sum('quantity');
    }

    protected function casts(): array
    {
        return ['item_type' => InventoryItemType::class, 'reorder_level' => 'integer', 'target_stock_level' => 'integer', 'is_consumable' => 'boolean', 'is_active' => 'boolean'];
    }
}
