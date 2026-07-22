<?php

namespace App\Models;

use App\AssessmentOutcome;
use Database\Factories\PaceAttemptCorrectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property AssessmentOutcome $outcome
 */
#[Fillable(['pace_attempt_id', 'score', 'outcome', 'reason', 'corrected_by', 'corrected_at'])]
class PaceAttemptCorrection extends Model
{
    /** @use HasFactory<PaceAttemptCorrectionFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /** @return BelongsTo<PaceAttempt, $this> */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(PaceAttempt::class, 'pace_attempt_id');
    }

    /** @return BelongsTo<User, $this> */
    public function correctedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }

    protected function casts(): array
    {
        return ['score' => 'decimal:2', 'outcome' => AssessmentOutcome::class, 'corrected_at' => 'datetime'];
    }
}
