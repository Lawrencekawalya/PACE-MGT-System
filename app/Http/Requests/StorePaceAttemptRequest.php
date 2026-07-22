<?php

namespace App\Http\Requests;

use App\AssessmentType;
use App\Models\PaceAssignment;
use App\Models\PaceAttempt;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaceAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        $assignment = $this->route('pace_assignment');

        return $assignment instanceof PaceAssignment
            && $this->user()->can('create', PaceAttempt::class)
            && $this->user()->can('update', $assignment);
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
