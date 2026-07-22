<?php

namespace App\Http\Requests;

use App\Models\PaceAttempt;
use Illuminate\Foundation\Http\FormRequest;

class StorePaceAttemptCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $attempt = $this->route('pace_attempt');

        return $attempt instanceof PaceAttempt && ($this->user()?->can('correct', $attempt) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['score' => ['required', 'numeric', 'between:0,100'], 'reason' => ['required', 'string', 'max:2000']];
    }
}
