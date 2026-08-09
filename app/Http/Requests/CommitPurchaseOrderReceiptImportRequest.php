<?php

namespace App\Http\Requests;

use App\Models\PurchaseOrderReceiptImport;
use Illuminate\Foundation\Http\FormRequest;

class CommitPurchaseOrderReceiptImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $import = $this->route('purchase_order_receipt_import');

        return $import instanceof PurchaseOrderReceiptImport
            && $this->user()?->can('commit', $import) === true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'delivery_reference' => ['required', 'string', 'max:255'],
            'received_at' => ['required', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
