<?php

namespace App\Http\Requests;

use App\InventoryItemType;
use App\Models\InventoryItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $item = $this->route('inventory_item');

        return $item instanceof InventoryItem && ($this->user()?->can('update', $item) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $item = $this->route('inventory_item');

        $rules = [
            'sku' => ['required', 'string', 'max:100', Rule::unique('inventory_items', 'sku')->ignore($item)],
            'reorder_level' => ['required', 'integer', 'min:0', 'max:100000'],
            'target_stock_level' => ['sometimes', 'integer', 'min:0', 'max:100000', 'gte:reorder_level'],
            'is_active' => ['required', 'boolean'],
        ];

        if ($item instanceof InventoryItem && $item->item_type === InventoryItemType::ScoreKey) {
            $rules['pace_id'] = [
                Rule::requiredIf($this->boolean('is_active')), 'nullable', 'integer', 'exists:paces,id',
                Rule::unique('inventory_items', 'pace_id')
                    ->where(fn ($query) => $query->where('item_type', InventoryItemType::ScoreKey))
                    ->ignore($item),
            ];
        }

        return $rules;
    }
}
