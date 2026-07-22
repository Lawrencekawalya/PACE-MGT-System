<?php

namespace App\Http\Requests;

use App\Models\StockMovement;
use Illuminate\Foundation\Http\FormRequest;

class CorrectStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $movement = $this->route('stock_movement');

        return $movement instanceof StockMovement && ($this->user()?->can('correct', $movement) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'max:2000']];
    }
}
