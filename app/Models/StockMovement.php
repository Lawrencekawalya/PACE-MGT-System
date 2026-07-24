<?php

namespace App\Models;

use App\StockMovementType;
use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property StockMovementType $type
 * @property Carbon $recorded_at
 */
#[Fillable(['inventory_item_id', 'type', 'quantity', 'balance_after', 'student_id', 'pace_assignment_id', 'academic_year_id', 'term_id', 'reference', 'reason', 'recorded_by', 'recorded_at', 'corrects_movement_id'])]
class StockMovement extends Model
{
    /** @use HasFactory<StockMovementFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /** @return BelongsTo<InventoryItem, $this> */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

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
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /** @return BelongsTo<StockMovement, $this> */
    public function correctsMovement(): BelongsTo
    {
        return $this->belongsTo(self::class, 'corrects_movement_id');
    }

    /** @return HasOne<StockMovement, $this> */
    public function correction(): HasOne
    {
        return $this->hasOne(self::class, 'corrects_movement_id');
    }

    protected function casts(): array
    {
        return ['type' => StockMovementType::class, 'quantity' => 'integer', 'balance_after' => 'integer', 'recorded_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Posted stock movements are immutable.'));
        static::deleting(fn () => throw new \LogicException('Posted stock movements are immutable.'));
    }
}
