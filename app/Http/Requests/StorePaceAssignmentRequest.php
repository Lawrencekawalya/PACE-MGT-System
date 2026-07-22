<?php

namespace App\Http\Requests;

use App\Models\PaceAssignment;
use App\Models\StudentCourse;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePaceAssignmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (! ($this->user()?->can('create', PaceAssignment::class) ?? false)) {
            return false;
        }

        $studentCourse = StudentCourse::query()->find($this->integer('student_course_id'));

        return $studentCourse !== null && $studentCourse->isManagedBy($this->user());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'student_course_id' => ['required', 'integer', 'exists:student_courses,id'],
            'pace_id' => ['required', 'integer', 'exists:paces,id'],
            'override_reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
