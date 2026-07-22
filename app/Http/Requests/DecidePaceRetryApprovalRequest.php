<?php

namespace App\Http\Requests;

use App\RetryApprovalStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecidePaceRetryApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('approve-retests') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in([RetryApprovalStatus::Approved->value, RetryApprovalStatus::Rejected->value])],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
