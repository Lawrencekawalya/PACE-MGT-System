<?php

namespace App\Models;

use Database\Factories\CatalogueImportRowFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string, mixed> $raw_data
 * @property array<string, mixed>|null $normalized_data
 * @property array<int, string>|null $errors
 */
#[Fillable(['catalogue_import_id', 'row_number', 'raw_data', 'normalized_data', 'status', 'errors'])]
class CatalogueImportRow extends Model
{
    /** @use HasFactory<CatalogueImportRowFactory> */
    use HasFactory;

    /** @return BelongsTo<CatalogueImport, $this> */
    public function catalogueImport(): BelongsTo
    {
        return $this->belongsTo(CatalogueImport::class);
    }

    protected function casts(): array
    {
        return ['raw_data' => 'array', 'normalized_data' => 'array', 'errors' => 'array'];
    }
}
