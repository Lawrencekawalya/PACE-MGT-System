<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\PermissionName;
use App\RoleName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveLearningCenterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionName::ManageAcademicSetup->value) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $learningCenter = $this->route('learning_center');

        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('learning_centers', 'name')->ignore($learningCenter)],
            'code' => ['required', 'string', 'max:40', Rule::unique('learning_centers', 'code')->ignore($learningCenter)],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
            'level_ids' => ['sometimes', 'array'],
            'level_ids.*' => ['integer', 'distinct', Rule::exists('levels', 'id')],
            'teacher_ids' => ['sometimes', 'array'],
            'teacher_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')->where('is_active', true)],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('teacher_ids')) {
                return;
            }

            $submittedTeacherIds = $this->input('teacher_ids', []);
            if (! is_array($submittedTeacherIds)) {
                return;
            }

            $teacherIds = collect($submittedTeacherIds)->map(fn ($id): int => (int) $id)->unique();
            $validTeacherCount = User::query()
                ->whereKey($teacherIds)
                ->where('is_active', true)
                ->whereHas('roles', fn ($query) => $query->where('name', RoleName::Teacher->value))
                ->count();

            if ($validTeacherCount !== $teacherIds->count()) {
                $validator->errors()->add('teacher_ids', 'Select only active staff members with the Teacher role.');
            }
        }];
    }
}
