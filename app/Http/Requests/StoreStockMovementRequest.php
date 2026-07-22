<?php

namespace App\Http\Requests;

use App\Models\StockMovement;
use App\StockMovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', StockMovement::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in([StockMovementType::Receipt->value, StockMovementType::Damage->value, StockMovementType::Loss->value, StockMovementType::Adjustment->value])],
            'quantity' => ['required', 'integer', 'not_in:0', 'between:-100000,100000'],
            'reference' => ['nullable', 'string', 'max:255'], 'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
