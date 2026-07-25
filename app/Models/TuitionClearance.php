<?php

namespace App\Models;

use App\TuitionClearanceStatus;
use Database\Factories\TuitionClearanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property TuitionClearanceStatus $status
 * @property Carbon|null $recorded_at
 */
#[Fillable([
    'student_enrollment_id',
    'term_id',
    'status',
    'reference',
    'notes',
    'recorded_by',
    'recorded_at',
])]
class TuitionClearance extends Model
{
    /** @use HasFactory<TuitionClearanceFactory> */
    use HasFactory;

    /** @return BelongsTo<StudentEnrollment, $this> */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }

    /** @return BelongsTo<Term, $this> */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    /** @return BelongsTo<User, $this> */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /** @return HasMany<TuitionClearanceEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(TuitionClearanceEvent::class)->orderByDesc('changed_at');
    }

    protected function casts(): array
    {
        return [
            'status' => TuitionClearanceStatus::class,
            'recorded_at' => 'datetime',
        ];
    }
}
