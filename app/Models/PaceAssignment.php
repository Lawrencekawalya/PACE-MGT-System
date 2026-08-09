<?php

namespace App\Models;

use App\PaceAccountTransactionType;
use App\PaceAssignmentStatus;
use App\RoleName;
use Database\Factories\PaceAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property PaceAssignmentStatus $status
 * @property Carbon $assigned_at
 * @property Carbon|null $issued_at
 * @property Carbon|null $started_at
 * @property Carbon|null $submitted_at
 * @property Carbon|null $completed_at
 */
#[Fillable(['student_course_id', 'pace_id', 'academic_year_id', 'term_id', 'status', 'attempt_cycle', 'assigned_by', 'assigned_at', 'issued_by', 'issued_at', 'started_at', 'submitted_at', 'completed_at', 'cancelled_at', 'reassigned_at', 'override_reason'])]
class PaceAssignment extends Model
{
    /** @use HasFactory<PaceAssignmentFactory> */
    use HasFactory;

    /** @return BelongsTo<StudentCourse, $this> */
    public function studentCourse(): BelongsTo
    {
        return $this->belongsTo(StudentCourse::class);
    }

    /** @return BelongsTo<Pace, $this> */
    public function pace(): BelongsTo
    {
        return $this->belongsTo(Pace::class);
    }

    /** @return BelongsTo<AcademicYear, $this> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /** @return BelongsTo<Term, $this> */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /** @return BelongsTo<User, $this> */
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /** @return HasMany<PaceStatusEvent, $this> */
    public function statusEvents(): HasMany
    {
        return $this->hasMany(PaceStatusEvent::class)->orderBy('changed_at');
    }

    /** @return HasMany<PaceAttempt, $this> */
    public function attempts(): HasMany
    {
        return $this->hasMany(PaceAttempt::class)->orderBy('assessment_type')->orderBy('attempt_number');
    }

    /** @return HasMany<PaceRetryApproval, $this> */
    public function retryApprovals(): HasMany
    {
        return $this->hasMany(PaceRetryApproval::class)->orderByDesc('requested_at');
    }

    /** @return HasMany<StockMovement, $this> */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /** @return HasOne<PaceAccountTransaction, $this> */
    public function paceAccountCharge(): HasOne
    {
        return $this->hasOne(PaceAccountTransaction::class)
            ->where('type', PaceAccountTransactionType::PaceIssue);
    }

    /** @param Builder<PaceAssignment> $query */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->hasRole(RoleName::Teacher) && ! $user->hasRole(RoleName::Administrator)) {
            $query->whereHas('studentCourse.enrollment.learningCenter.teachers', fn (Builder $query) => $query->whereKey($user->id));
        }
    }

    public function isManagedBy(User $user): bool
    {
        return $this->studentCourse->isManagedBy($user);
    }

    protected function casts(): array
    {
        return [
            'status' => PaceAssignmentStatus::class, 'attempt_cycle' => 'integer',
            'assigned_at' => 'datetime', 'issued_at' => 'datetime', 'started_at' => 'datetime',
            'submitted_at' => 'datetime', 'completed_at' => 'datetime', 'cancelled_at' => 'datetime', 'reassigned_at' => 'datetime',
        ];
    }
}
