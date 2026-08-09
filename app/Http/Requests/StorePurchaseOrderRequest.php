<?php

namespace App\Http\Requests;

use App\Models\PurchaseOrder;
use App\PurchaseOrderSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', PurchaseOrder::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')->where('is_active', true)],
            'source' => ['required', Rule::enum(PurchaseOrderSource::class)],
            'expected_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'lines' => ['sometimes', 'array', 'max:5000'],
            'lines.*.inventory_item_id' => ['required', 'integer', Rule::exists('inventory_items', 'id')->where('is_active', true), 'distinct'],
            'lines.*.quantity_ordered' => ['required', 'integer', 'min:1', 'max:100000'],
        ];
    }
}
