<?php

namespace App\Http\Requests;

use App\Models\PurchaseOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StorePurchaseOrderReceiptImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('purchase_order');

        return $order instanceof PurchaseOrder
            && $this->user()?->can('receive', $order) === true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'workbook' => ['required', File::types(['xlsx', 'csv'])->max('10mb')],
        ];
    }
}
