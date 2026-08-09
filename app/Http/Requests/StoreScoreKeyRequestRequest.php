<?php

namespace App\Http\Requests;

use App\InventoryItemType;
use App\Models\InventoryItem;
use App\PermissionName;
use App\RoleName;
use App\ScoreKeyRequestType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScoreKeyRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasRole(RoleName::Teacher)
            && $this->user()->can(PermissionName::RequestScoreKeys->value);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'learning_center_id' => ['required', 'integer', 'exists:learning_centers,id'],
            'inventory_item_id' => [
                'required',
                'integer',
                Rule::exists(InventoryItem::class, 'id')->where(fn ($query) => $query
                    ->where('item_type', InventoryItemType::ScoreKey->value)
                    ->where('is_active', true)
                    ->whereNotNull('pace_id')),
            ],
            'request_type' => ['required', Rule::enum(ScoreKeyRequestType::class)],
            'quantity_requested' => ['required', 'integer', 'min:1', 'max:100'],
            'request_reason' => ['nullable', 'required_unless:request_type,'.ScoreKeyRequestType::NewIssue->value, 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
