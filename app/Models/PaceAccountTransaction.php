<?php

namespace App\Models;

use App\PaceAccountTransactionType;
use Database\Factories\PaceAccountTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property PaceAccountTransactionType $type
 * @property string $amount
 * @property string $balance_after
 * @property Carbon $recorded_at
 */
#[Fillable(['student_id', 'pace_assignment_id', 'academic_year_id', 'term_id', 'type', 'amount', 'balance_after', 'reference', 'notes', 'recorded_by', 'recorded_at'])]
class PaceAccountTransaction extends Model
{
    /** @use HasFactory<PaceAccountTransactionFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<PaceAssignment, $this> */
    public function paceAssignment(): BelongsTo
    {
        return $this->belongsTo(PaceAssignment::class);
    }

    /** @return BelongsTo<User, $this> */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    protected function casts(): array
    {
        return [
            'type' => PaceAccountTransactionType::class,
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'recorded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Posted PACE account transactions are immutable.'));
        static::deleting(fn () => throw new \LogicException('Posted PACE account transactions are immutable.'));
    }
}
