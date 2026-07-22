<?php

namespace App\Http\Requests\Admin;

use App\PermissionName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionName::ManageAcademicSetup->value) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80', Rule::unique('levels')->ignore($this->route('level'))],
            'code' => ['required', 'string', 'max:20', 'alpha_dash:ascii', Rule::unique('levels')->ignore($this->route('level'))],
            'sort_order' => ['required', 'integer', 'min:1', Rule::unique('levels')->ignore($this->route('level'))],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
