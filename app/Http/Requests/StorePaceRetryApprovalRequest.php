<?php

namespace App\Http\Requests;

use App\AssessmentType;
use App\Models\PaceAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaceRetryApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $assignment = $this->route('pace_assignment');

        return $assignment instanceof PaceAssignment
            && $this->user()->can('enter-test-results')
            && $this->user()->can('update', $assignment);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'assessment_type' => ['required', Rule::enum(AssessmentType::class)],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
