<?php

namespace App\Models;

use Database\Factories\CatalogueImportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['original_name', 'file_path', 'checksum', 'status', 'valid_rows', 'warning_rows', 'invalid_rows', 'created_records', 'updated_records', 'skipped_records', 'uploaded_by', 'committed_by', 'committed_at', 'failure_reason'])]
class CatalogueImport extends Model
{
    /** @use HasFactory<CatalogueImportFactory> */
    use HasFactory;

    /** @return HasMany<CatalogueImportRow, $this> */
    public function rows(): HasMany
    {
        return $this->hasMany(CatalogueImportRow::class)->orderBy('row_number');
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** @return BelongsTo<User, $this> */
    public function committer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'committed_by');
    }

    protected function casts(): array
    {
        return ['committed_at' => 'datetime', 'valid_rows' => 'integer', 'warning_rows' => 'integer', 'invalid_rows' => 'integer', 'created_records' => 'integer', 'updated_records' => 'integer', 'skipped_records' => 'integer'];
    }
}
