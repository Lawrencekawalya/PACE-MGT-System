<?php

namespace App\Models;

use Database\Factories\CurriculumRequirementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['level_id', 'course_id', 'is_required', 'sort_order', 'is_active'])]
class CurriculumRequirement extends Model
{
    /** @use HasFactory<CurriculumRequirementFactory> */
    use HasFactory;

    /** @return BelongsTo<Level, $this> */
    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return BelongsToMany<Pace, $this> */
    public function paces(): BelongsToMany
    {
        return $this->belongsToMany(Pace::class)
            ->withPivot('sequence_order')
            ->withTimestamps()
            ->orderByPivot('sequence_order');
    }

    protected function casts(): array
    {
        return ['is_required' => 'boolean', 'sort_order' => 'integer', 'is_active' => 'boolean'];
    }
}
