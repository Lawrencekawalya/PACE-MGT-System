<?php

namespace App\Http\Requests\Admin;

use App\PermissionName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCurriculumRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionName::ManagePaceCatalogue->value) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $courseId = $this->integer('course_id');

        return [
            'level_id' => ['required', 'integer', Rule::exists('levels', 'id')],
            'course_id' => ['required', 'integer', Rule::exists('courses', 'id')],
            'is_required' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:1'],
            'is_active' => ['required', 'boolean'],
            'pace_ids' => ['required', 'array', 'min:1'],
            'pace_ids.*' => ['required', 'integer', 'distinct', Rule::exists('paces', 'id')->where('course_id', $courseId)],
        ];
    }
}
