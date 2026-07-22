<?php

namespace App\Http\Requests;

use App\Models\Student;
use App\Models\User;
use App\RoleName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = $this->route('student');

        return $student instanceof Student && ($this->user()?->can('update', $student) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $administrator = $this->user()?->hasRole(RoleName::Administrator) ?? false;

        return [
            'teacher_id' => [Rule::requiredIf($administrator), Rule::prohibitedIf(! $administrator), 'nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'other_names' => ['nullable', 'string', 'max:120'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'guardian_name' => ['required', 'string', 'max:160'],
            'guardian_phone' => ['required', 'string', 'max:40'],
            'guardian_email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('teacher_id') || ! $this->filled('teacher_id')) {
                return;
            }

            $teacher = User::query()->find($this->integer('teacher_id'));
            if ($teacher === null || ! $teacher->hasRole(RoleName::Teacher)) {
                $validator->errors()->add('teacher_id', 'Select an active staff member with the Teacher role.');
            }
        }];
    }
}
