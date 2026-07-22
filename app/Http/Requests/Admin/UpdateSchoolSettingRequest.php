<?php

namespace App\Http\Requests\Admin;

use App\PermissionName;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSchoolSettingRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'country_code' => strtoupper($this->string('country_code')->toString()),
        ]);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionName::ManageSchoolSettings->value) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'official_name' => ['required', 'string', 'max:255'],
            'short_name' => ['required', 'string', 'max:30'],
            'slogan' => ['nullable', 'string', 'max:255'],
            'country_code' => ['required', 'string', 'size:2'],
            'timezone' => ['required', 'timezone:all'],
            'date_format' => ['required', Rule::in(['DD/MM/YYYY'])],
            'time_format' => ['required', Rule::in(['12-hour', '24-hour'])],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'remove_logo' => ['sometimes', 'boolean'],
            'self_test_pass_mark' => ['required', 'numeric', 'between:0,100'],
            'self_test_retry_limit' => ['required', 'integer', 'between:1,10'],
        ];
    }
}
