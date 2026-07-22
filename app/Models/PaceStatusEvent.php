<?php

namespace App\Models;

use App\PaceAssignmentStatus;
use Database\Factories\PaceStatusEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['pace_assignment_id', 'from_status', 'to_status', 'changed_by', 'changed_at', 'reason'])]
class PaceStatusEvent extends Model
{
    /** @use HasFactory<PaceStatusEventFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /** @return BelongsTo<PaceAssignment, $this> */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(PaceAssignment::class, 'pace_assignment_id');
    }

    /** @return BelongsTo<User, $this> */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    protected function casts(): array
    {
        return ['from_status' => PaceAssignmentStatus::class, 'to_status' => PaceAssignmentStatus::class, 'changed_at' => 'datetime'];
    }
}
