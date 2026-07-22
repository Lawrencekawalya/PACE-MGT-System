<?php

namespace App\Models;

use App\AssessmentType;
use App\RetryApprovalStatus;
use Database\Factories\PaceRetryApprovalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property AssessmentType $assessment_type
 * @property RetryApprovalStatus $status
 */
#[Fillable(['pace_assignment_id', 'assessment_type', 'attempt_number', 'status', 'is_over_limit', 'requested_by', 'requested_at', 'request_reason', 'decided_by', 'decided_at', 'decision_reason'])]
class PaceRetryApproval extends Model
{
    /** @use HasFactory<PaceRetryApprovalFactory> */
    use HasFactory;

    /** @return BelongsTo<PaceAssignment, $this> */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(PaceAssignment::class, 'pace_assignment_id');
    }

    /** @return BelongsTo<User, $this> */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** @return BelongsTo<User, $this> */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    protected function casts(): array
    {
        return [
            'assessment_type' => AssessmentType::class, 'status' => RetryApprovalStatus::class,
            'attempt_number' => 'integer', 'is_over_limit' => 'boolean', 'requested_at' => 'datetime', 'decided_at' => 'datetime',
        ];
    }
}
