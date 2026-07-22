<?php

namespace App\Models;

use Database\Factories\TermFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $starts_on
 * @property Carbon $ends_on
 */
#[Fillable(['academic_year_id', 'name', 'sort_order', 'starts_on', 'ends_on', 'is_active', 'is_closed'])]
class Term extends Model
{
    /** @use HasFactory<TermFactory> */
    use HasFactory;

    /** @return BelongsTo<AcademicYear, $this> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'starts_on' => 'date', 'ends_on' => 'date', 'is_active' => 'boolean', 'is_closed' => 'boolean'];
    }
}
