<?php

namespace App\Http\Requests;

use App\Models\PurchaseOrderLine;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseOrderLineRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $line = $this->route('purchase_order_line');

        return $line instanceof PurchaseOrderLine && ($this->user()?->can('update', $line->purchaseOrder) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'quantity_ordered' => ['required', 'integer', 'min:1', 'max:100000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
