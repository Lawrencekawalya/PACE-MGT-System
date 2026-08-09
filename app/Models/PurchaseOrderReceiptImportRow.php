<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string, mixed> $raw_data
 * @property array<string, mixed>|null $normalized_data
 * @property list<string>|null $errors
 */
#[Fillable([
    'purchase_order_receipt_import_id', 'row_number', 'purchase_order_line_id',
    'raw_data', 'normalized_data', 'status', 'errors',
])]
class PurchaseOrderReceiptImportRow extends Model
{
    /** @return BelongsTo<PurchaseOrderReceiptImport, $this> */
    public function purchaseOrderReceiptImport(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderReceiptImport::class);
    }

    /** @return BelongsTo<PurchaseOrderLine, $this> */
    public function purchaseOrderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class);
    }

    protected function casts(): array
    {
        return [
            'raw_data' => 'array',
            'normalized_data' => 'array',
            'errors' => 'array',
        ];
    }
}
