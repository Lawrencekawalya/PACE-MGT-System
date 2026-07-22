<?php

namespace App\Models;

use App\ReportExportStatus;
use App\ReportFormat;
use App\ReportType;
use Database\Factories\ReportExportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property ReportType $report_type
 * @property ReportFormat $format
 * @property array<string, mixed> $filters
 * @property ReportExportStatus $status
 * @property string $disk
 * @property string|null $path
 * @property string|null $original_filename
 * @property Carbon|null $completed_at
 * @property Carbon|null $expires_at
 */
#[Fillable(['user_id', 'report_type', 'format', 'filters', 'status', 'disk', 'path', 'original_filename', 'row_count', 'error_message', 'completed_at', 'expires_at'])]
class ReportExport extends Model
{
    /** @use HasFactory<ReportExportFactory> */
    use HasFactory;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'report_type' => ReportType::class,
            'format' => ReportFormat::class,
            'filters' => 'array',
            'status' => ReportExportStatus::class,
            'row_count' => 'integer',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
