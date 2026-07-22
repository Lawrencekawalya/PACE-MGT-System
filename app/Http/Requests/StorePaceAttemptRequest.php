<?php

namespace App\Http\Requests;

use App\AssessmentType;
use App\Models\PaceAttempt;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaceAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PaceAttempt::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'assessment_type' => ['required', Rule::enum(AssessmentType::class)],
            'score' => ['required', 'numeric', 'between:0,100'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
