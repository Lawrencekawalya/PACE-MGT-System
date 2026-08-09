<?php

namespace App\Models;

use App\GoodsReceiptImportStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $purchase_order_id
 * @property string $original_name
 * @property string $file_path
 * @property string $checksum
 * @property GoodsReceiptImportStatus $status
 * @property int $valid_rows
 * @property int $skipped_rows
 * @property int $invalid_rows
 * @property string|null $failure_reason
 * @property int|null $uploaded_by
 * @property int|null $committed_by
 * @property Carbon|null $committed_at
 * @property int|null $goods_receipt_id
 * @property-read PurchaseOrder $purchaseOrder
 * @property-read User|null $uploader
 * @property-read User|null $committer
 * @property-read GoodsReceipt|null $goodsReceipt
 */
#[Fillable([
    'purchase_order_id', 'original_name', 'file_path', 'checksum', 'status',
    'valid_rows', 'skipped_rows', 'invalid_rows', 'failure_reason', 'uploaded_by',
    'committed_by', 'committed_at', 'goods_receipt_id',
])]
class PurchaseOrderReceiptImport extends Model
{
    /** @return BelongsTo<PurchaseOrder, $this> */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /** @return HasMany<PurchaseOrderReceiptImportRow, $this> */
    public function rows(): HasMany
    {
        return $this->hasMany(PurchaseOrderReceiptImportRow::class)->orderBy('row_number');
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** @return BelongsTo<User, $this> */
    public function committer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'committed_by');
    }

    /** @return BelongsTo<GoodsReceipt, $this> */
    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    protected function casts(): array
    {
        return [
            'status' => GoodsReceiptImportStatus::class,
            'valid_rows' => 'integer',
            'skipped_rows' => 'integer',
            'invalid_rows' => 'integer',
            'committed_at' => 'datetime',
        ];
    }
}
