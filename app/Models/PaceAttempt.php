<?php

namespace App\Models;

use App\AssessmentOutcome;
use App\AssessmentType;
use Database\Factories\PaceAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property AssessmentType $assessment_type
 * @property AssessmentOutcome $outcome
 */
#[Fillable(['pace_assignment_id', 'assessment_type', 'attempt_number', 'score', 'pass_mark_used', 'outcome', 'notes', 'recorded_by', 'recorded_at', 'approved_by', 'approved_at', 'approval_reason', 'finalized_at'])]
class PaceAttempt extends Model
{
    /** @use HasFactory<PaceAttemptFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /** @return BelongsTo<PaceAssignment, $this> */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(PaceAssignment::class, 'pace_assignment_id');
    }

    /** @return BelongsTo<User, $this> */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /** @return BelongsTo<User, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** @return HasMany<PaceAttemptCorrection, $this> */
    public function corrections(): HasMany
    {
        return $this->hasMany(PaceAttemptCorrection::class)->orderBy('corrected_at');
    }

    protected function casts(): array
    {
        return [
            'assessment_type' => AssessmentType::class, 'outcome' => AssessmentOutcome::class,
            'score' => 'decimal:2', 'pass_mark_used' => 'decimal:2', 'attempt_number' => 'integer',
            'recorded_at' => 'datetime', 'approved_at' => 'datetime', 'finalized_at' => 'datetime',
        ];
    }
}
