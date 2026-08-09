<?php

namespace App\Models;

use App\ScoreKeyRequestStatus;
use App\ScoreKeyRequestType;
use App\StockMovementType;
use Database\Factories\ScoreKeyRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property ScoreKeyRequestStatus $status
 * @property ScoreKeyRequestType $request_type
 * @property Carbon $requested_at
 */
#[Fillable(['teacher_id', 'learning_center_id', 'inventory_item_id', 'request_type', 'quantity_requested', 'status', 'request_reason', 'notes', 'rejection_reason', 'requested_at', 'rejected_by', 'rejected_at', 'cancelled_at'])]
class ScoreKeyRequest extends Model
{
    /** @use HasFactory<ScoreKeyRequestFactory> */
    use HasFactory;

    /** @return BelongsTo<User, $this> */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /** @return BelongsTo<LearningCenter, $this> */
    public function learningCenter(): BelongsTo
    {
        return $this->belongsTo(LearningCenter::class);
    }

    /** @return BelongsTo<InventoryItem, $this> */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /** @return BelongsTo<User, $this> */
    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /** @return HasMany<StockMovement, $this> */
    public function issueMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class)
            ->where('type', StockMovementType::Issue)
            ->whereDoesntHave('correction');
    }

    public function issuedQuantity(): int
    {
        return abs((int) $this->issueMovements()->sum('quantity'));
    }

    public function outstandingQuantity(): int
    {
        return max(0, $this->quantity_requested - $this->issuedQuantity());
    }

    protected function casts(): array
    {
        return [
            'request_type' => ScoreKeyRequestType::class,
            'status' => ScoreKeyRequestStatus::class,
            'quantity_requested' => 'integer',
            'requested_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }
}
