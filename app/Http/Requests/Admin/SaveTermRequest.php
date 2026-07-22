<?php

namespace App\Http\Requests\Admin;

use App\Models\AcademicYear;
use App\PermissionName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTermRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionName::ManageAcademicSetup->value) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $year = $this->route('academic_year');
        $yearId = $year instanceof AcademicYear ? $year->getKey() : null;

        return [
            'name' => ['required', 'string', 'max:40', Rule::unique('terms', 'name')->where('academic_year_id', $yearId)->ignore($this->route('term'))],
            'sort_order' => ['required', 'integer', 'min:1', Rule::unique('terms', 'sort_order')->where('academic_year_id', $yearId)->ignore($this->route('term'))],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
            'is_active' => ['required', 'boolean'],
            'is_closed' => ['required', 'boolean'],
        ];
    }
}
