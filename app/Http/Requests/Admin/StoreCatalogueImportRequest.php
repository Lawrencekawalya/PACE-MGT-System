<?php

namespace App\Http\Requests\Admin;

use App\PermissionName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreCatalogueImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionName::ImportPaceCatalogue->value) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['workbook' => ['required', File::types(['xlsx'])->max('10mb')]];
    }
}
