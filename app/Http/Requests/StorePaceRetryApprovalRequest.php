<?php

namespace App\Http\Requests;

use App\AssessmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaceRetryApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('enter-test-results') ?? false;
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
