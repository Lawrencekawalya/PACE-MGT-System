<?php

namespace App\Http\Requests;

use App\Models\StudentEnrollment;
use App\PermissionName;
use App\TuitionClearanceStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTuitionClearanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionName::ManageTuitionClearance->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $enrollment = $this->route('student_enrollment');
        $academicYearId = $enrollment instanceof StudentEnrollment
            ? $enrollment->academic_year_id
            : 0;

        return [
            'term_id' => [
                'required',
                'integer',
                Rule::exists('terms', 'id')->where('academic_year_id', $academicYearId),
            ],
            'status' => ['required', Rule::enum(TuitionClearanceStatus::class)],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
