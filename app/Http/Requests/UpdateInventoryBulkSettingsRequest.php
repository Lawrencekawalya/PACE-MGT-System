<?php

namespace App\Http\Requests;

use App\InventoryItemType;
use App\Models\InventoryItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryBulkSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', InventoryItem::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'scope' => ['required', Rule::in(['selected', 'item_type', 'course', 'all'])],
            'inventory_item_ids' => ['exclude_unless:scope,selected', 'required', 'array', 'min:1', 'max:2000'],
            'inventory_item_ids.*' => ['integer', 'distinct', 'exists:inventory_items,id'],
            'item_type' => ['exclude_unless:scope,item_type', 'required', Rule::enum(InventoryItemType::class)],
            'course_id' => ['exclude_unless:scope,course', 'required', 'integer', 'exists:courses,id'],
            'reorder_level' => ['required', 'integer', 'min:0', 'max:100000'],
            'target_stock_level' => ['required', 'integer', 'min:0', 'max:100000', 'gte:reorder_level'],
        ];
    }
}
