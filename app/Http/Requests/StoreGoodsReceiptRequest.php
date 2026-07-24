<?php

namespace App\Http\Requests;

use App\Models\PurchaseOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreGoodsReceiptRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $order = $this->route('purchase_order');

        return $order instanceof PurchaseOrder && ($this->user()?->can('receive', $order) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'delivery_reference' => ['required', 'string', 'max:255'],
            'received_at' => ['required', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.purchase_order_line_id' => ['required', 'integer', 'distinct', 'exists:purchase_order_lines,id'],
            'lines.*.quantity_received' => ['required', 'integer', 'min:0', 'max:100000'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $lines = collect($this->input('lines', []));
                if (! $lines->contains(fn (array $line): bool => (int) ($line['quantity_received'] ?? 0) > 0)) {
                    $validator->errors()->add('lines', 'Enter a received quantity for at least one order line.');
                }
            },
        ];
    }
}
