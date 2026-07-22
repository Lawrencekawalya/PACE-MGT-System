<?php

namespace App\Http\Requests;

use App\Models\PaceRetryApproval;
use App\RetryApprovalStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecidePaceRetryApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $approval = $this->route('pace_retry_approval');

        return $approval instanceof PaceRetryApproval
            && ($this->user()?->can('approve-retests') ?? false)
            && $approval->assignment->isManagedBy($this->user());
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
