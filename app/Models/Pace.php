<?php

namespace App\Models;

use App\Observers\PaceObserver;
use Database\Factories\PaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(PaceObserver::class)]
#[Fillable(['course_id', 'number', 'title', 'edition', 'sequence_order', 'is_active'])]
class Pace extends Model
{
    /** @use HasFactory<PaceFactory> */
    use HasFactory;

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return BelongsToMany<CurriculumRequirement, $this> */
    public function curriculumRequirements(): BelongsToMany
    {
        return $this->belongsToMany(CurriculumRequirement::class)
            ->withPivot('sequence_order')
            ->withTimestamps();
    }

    /** @return HasMany<PaceAssignment, $this> */
    public function assignments(): HasMany
    {
        return $this->hasMany(PaceAssignment::class);
    }

    /** @return HasMany<InventoryItem, $this> */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    protected function casts(): array
    {
        return ['sequence_order' => 'integer', 'is_active' => 'boolean'];
    }
}
