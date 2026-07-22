<?php

namespace App\Http\Requests;

use App\Models\Student;
use App\StudentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = $this->route('student');

        return $student instanceof Student && ($this->user()?->can('update', $student) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(StudentStatus::class)],
            'reason' => ['nullable', 'string', 'max:1000', Rule::requiredIf($this->input('status') !== StudentStatus::Active->value)],
        ];
    }
}
