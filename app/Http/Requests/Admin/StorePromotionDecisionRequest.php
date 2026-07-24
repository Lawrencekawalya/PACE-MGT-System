<?php

namespace App\Http\Requests\Admin;

use App\EnrollmentStatus;
use App\Models\StudentEnrollment;
use App\RoleName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePromotionDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->hasRole(RoleName::Administrator) ?? false)
            && $this->route('enrollment') instanceof StudentEnrollment;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $requiresNextEnrollment = in_array(
            $this->string('decision')->toString(),
            [EnrollmentStatus::Promoted->value, EnrollmentStatus::Retained->value],
            true,
        );

        return [
            'decision' => [
                'required',
                Rule::in([
                    EnrollmentStatus::Promoted->value,
                    EnrollmentStatus::Retained->value,
                    EnrollmentStatus::Transferred->value,
                    EnrollmentStatus::Completed->value,
                ]),
            ],
            'target_academic_year_id' => [
                Rule::requiredIf($requiresNextEnrollment),
                'nullable',
                'integer',
                Rule::exists('academic_years', 'id'),
            ],
            'target_term_id' => [
                Rule::requiredIf($requiresNextEnrollment),
                'nullable',
                'integer',
                Rule::exists('terms', 'id'),
            ],
            'target_level_id' => [
                Rule::requiredIf($requiresNextEnrollment),
                'nullable',
                'integer',
                Rule::exists('levels', 'id')->where('is_active', true),
            ],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array{
     *     decision: string,
     *     target_academic_year_id: int|null,
     *     target_term_id: int|null,
     *     target_level_id: int|null,
     *     reason: string|null
     * }
     */
    public function promotionData(): array
    {
        $validated = $this->validated();

        return [
            'decision' => (string) $validated['decision'],
            'target_academic_year_id' => isset($validated['target_academic_year_id'])
                ? (int) $validated['target_academic_year_id']
                : null,
            'target_term_id' => isset($validated['target_term_id'])
                ? (int) $validated['target_term_id']
                : null,
            'target_level_id' => isset($validated['target_level_id'])
                ? (int) $validated['target_level_id']
                : null,
            'reason' => isset($validated['reason']) ? (string) $validated['reason'] : null,
        ];
    }
}
