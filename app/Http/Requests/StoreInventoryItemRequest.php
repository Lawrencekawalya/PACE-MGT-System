<?php

namespace App\Http\Requests;

use App\InventoryItemType;
use App\Models\InventoryItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', InventoryItem::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'pace_id' => [
                'required', 'integer', 'exists:paces,id',
                Rule::unique('inventory_items', 'pace_id')->where(fn ($query) => $query->where('item_type', $this->input('item_type'))),
            ],
            'item_type' => ['required', Rule::enum(InventoryItemType::class)],
            'sku' => ['required', 'string', 'max:100', 'unique:inventory_items,sku'],
            'reorder_level' => ['required', 'integer', 'min:0', 'max:100000'],
            'target_stock_level' => ['sometimes', 'integer', 'min:0', 'max:100000', 'gte:reorder_level'],
            'is_consumable' => ['required', 'boolean'], 'is_active' => ['required', 'boolean'],
        ];
    }
}
