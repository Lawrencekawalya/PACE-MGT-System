<?php

namespace App\Http\Requests;

use App\Models\PaceAssignment;
use App\PaceAssignmentStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionPaceAssignmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $assignment = $this->route('pace_assignment');

        return $assignment instanceof PaceAssignment && ($this->user()?->can('update', $assignment) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(PaceAssignmentStatus::class)],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
