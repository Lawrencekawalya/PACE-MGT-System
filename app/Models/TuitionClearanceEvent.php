<?php

namespace App\Models;

use App\TuitionClearanceStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property TuitionClearanceStatus|null $from_status
 * @property TuitionClearanceStatus $to_status
 * @property Carbon $changed_at
 */
#[Fillable([
    'tuition_clearance_id',
    'from_status',
    'to_status',
    'reference',
    'notes',
    'changed_by',
    'changed_at',
])]
class TuitionClearanceEvent extends Model
{
    /** @return BelongsTo<TuitionClearance, $this> */
    public function clearance(): BelongsTo
    {
        return $this->belongsTo(TuitionClearance::class, 'tuition_clearance_id');
    }

    /** @return BelongsTo<User, $this> */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    protected function casts(): array
    {
        return [
            'from_status' => TuitionClearanceStatus::class,
            'to_status' => TuitionClearanceStatus::class,
            'changed_at' => 'datetime',
        ];
    }
}
