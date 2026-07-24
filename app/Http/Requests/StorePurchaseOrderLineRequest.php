<?php

namespace App\Http\Requests;

use App\Models\PurchaseOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseOrderLineRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $order = $this->route('purchase_order');

        return $order instanceof PurchaseOrder && ($this->user()?->can('update', $order) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $order = $this->route('purchase_order');
        $orderId = $order instanceof PurchaseOrder ? $order->id : null;

        return [
            'inventory_item_id' => [
                'required',
                'integer',
                Rule::exists('inventory_items', 'id')->where('is_active', true),
                Rule::unique('purchase_order_lines', 'inventory_item_id')
                    ->where('purchase_order_id', $orderId),
            ],
            'quantity_ordered' => ['required', 'integer', 'min:1', 'max:100000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
