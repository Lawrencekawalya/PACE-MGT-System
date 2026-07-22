<?php

namespace App\Http\Requests\Admin;

use App\PermissionName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionName::ManagePaceCatalogue->value) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'subject_id' => ['required', 'integer', Rule::exists('subjects', 'id')],
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:40', 'alpha_dash:ascii', Rule::unique('courses')->ignore($this->route('course'))],
            'edition' => ['nullable', 'string', 'max:60'],
            'is_pace_course' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['edition' => trim((string) $this->input('edition', ''))]);
    }
}
