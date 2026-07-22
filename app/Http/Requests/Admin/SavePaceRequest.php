<?php

namespace App\Http\Requests\Admin;

use App\PermissionName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionName::ManagePaceCatalogue->value) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $courseId = $this->integer('course_id');
        $edition = trim($this->string('edition')->toString());

        return [
            'course_id' => ['required', 'integer', Rule::exists('courses', 'id')],
            'number' => ['required', 'string', 'max:30', Rule::unique('paces')->where(fn ($query) => $query->where('course_id', $courseId)->where('edition', $edition))->ignore($this->route('pace'))],
            'title' => ['nullable', 'string', 'max:160'],
            'edition' => ['nullable', 'string', 'max:60'],
            'sequence_order' => ['required', 'integer', 'min:1', Rule::unique('paces')->where(fn ($query) => $query->where('course_id', $courseId)->where('edition', $edition))->ignore($this->route('pace'))],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['edition' => trim((string) $this->input('edition', ''))]);
    }
}
