<?php

namespace App\Http\Requests\Admin;

use App\PermissionName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionName::ManageAcademicSetup->value) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('subjects')->ignore($this->route('subject'))],
            'code' => ['required', 'string', 'max:30', 'alpha_dash:ascii', Rule::unique('subjects')->ignore($this->route('subject'))],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
