<?php

namespace App\Http\Requests;

use App\PaceAssignmentStatus;
use App\ReportFormat;
use App\ReportType;
use App\StudentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $type = ReportType::tryFrom($this->string('report_type')->toString());

        return $type !== null && ($this->user()?->can($type->isInventory() ? 'view-inventory-reports' : 'view-academic-reports') ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'report_type' => ['required', Rule::enum(ReportType::class)],
            'format' => ['required', Rule::enum(ReportFormat::class)],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'term_id' => ['nullable', 'integer', 'exists:terms,id'],
            'level_id' => ['nullable', 'integer', 'exists:levels,id'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'student_status' => ['nullable', Rule::enum(StudentStatus::class)],
            'assignment_status' => ['nullable', Rule::enum(PaceAssignmentStatus::class)],
            'stock' => ['nullable', Rule::in(['available', 'low', 'out'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }
}
