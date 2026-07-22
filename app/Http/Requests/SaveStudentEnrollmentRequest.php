<?php

namespace App\Http\Requests;

use App\Models\Student;
use App\Models\StudentEnrollment;
use App\PermissionName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveStudentEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = $this->route('student');

        return $student instanceof Student
            && $this->user()->can(PermissionName::RegisterStudents->value)
            && $this->user()->can(PermissionName::AssignPaces->value)
            && $this->user()->can('update', $student);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $student = $this->route('student');
        $enrollment = $this->route('enrollment');
        $studentId = $student instanceof Student ? $student->id : null;

        return [
            'academic_year_id' => ['required', 'integer', Rule::exists('academic_years', 'id'), Rule::unique('student_enrollments')->where('student_id', $studentId)->ignore($enrollment instanceof StudentEnrollment ? $enrollment : null)],
            'term_id' => ['required', 'integer', Rule::exists('terms', 'id')],
            'level_id' => ['required', 'integer', Rule::exists('levels', 'id')->where('is_active', true)],
            'enrolled_on' => ['required', 'date'],
            'curriculum_override_reason' => ['nullable', 'string', 'max:2000'],
            'courses' => ['required', 'array', 'min:1'],
            'courses.*.course_id' => ['required', 'integer', 'distinct', Rule::exists('courses', 'id')->where('is_active', true)],
            'courses.*.starting_pace_id' => ['nullable', 'integer', Rule::exists('paces', 'id')->where('is_active', true)],
            'courses.*.placement_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
