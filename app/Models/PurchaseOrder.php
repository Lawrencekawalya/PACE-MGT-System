<?php

namespace App\Models;

use App\PurchaseOrderSource;
use App\PurchaseOrderStatus;
use Carbon\CarbonInterface;
use Database\Factories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property PurchaseOrderSource $source
 * @property PurchaseOrderStatus $status
 * @property CarbonInterface|null $expected_on
 * @property CarbonInterface|null $submitted_at
 * @property int|null $decided_by
 * @property CarbonInterface|null $decided_at
 * @property CarbonInterface|null $sent_at
 * @property CarbonInterface|null $cancelled_at
 */
#[Fillable([
    'order_number', 'supplier_id', 'source', 'status', 'expected_on', 'notes',
    'created_by', 'submitted_by', 'submitted_at', 'decided_by', 'decided_at',
    'decision_reason', 'sent_by', 'sent_at', 'cancelled_by', 'cancelled_at',
    'cancellation_reason',
])]
class PurchaseOrder extends Model
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use HasFactory;

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** @return HasMany<PurchaseOrderLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    /** @return HasMany<GoodsReceipt, $this> */
    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /** @return BelongsTo<User, $this> */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /** @return BelongsTo<User, $this> */
    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /** @return BelongsTo<User, $this> */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    protected function casts(): array
    {
        return [
            'source' => PurchaseOrderSource::class,
            'status' => PurchaseOrderStatus::class,
            'expected_on' => 'date:Y-m-d',
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
            'sent_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }
}
