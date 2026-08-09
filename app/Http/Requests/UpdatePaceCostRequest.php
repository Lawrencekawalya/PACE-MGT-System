<?php

namespace App\Http\Requests;

use App\PermissionName;
use App\RoleName;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePaceCostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(PermissionName::ManagePaceAccounts->value)
            && $this->user()->hasRole(RoleName::Accountant);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pace_cost' => ['required', 'decimal:0,2', 'gt:0', 'max:9999999999.99'],
        ];
    }
}
