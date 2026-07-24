<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IssuePacesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('issue-paces') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'assignment_ids' => ['required', 'array', 'min:1', 'max:100'],
            'assignment_ids.*' => ['required', 'integer', 'distinct', 'exists:pace_assignments,id'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'assignment_ids.required' => 'Select at least one assigned PACE to issue.',
            'assignment_ids.max' => 'Issue no more than 100 assigned PACEs at a time.',
            'assignment_ids.*.distinct' => 'Each assigned PACE may only be selected once.',
        ];
    }
}
