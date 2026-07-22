<?php

namespace App\Models;

use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['subject_id', 'name', 'code', 'edition', 'is_pace_course', 'is_active'])]
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** @return HasMany<Pace, $this> */
    public function paces(): HasMany
    {
        return $this->hasMany(Pace::class)->orderBy('sequence_order');
    }

    /** @return HasMany<CurriculumRequirement, $this> */
    public function curriculumRequirements(): HasMany
    {
        return $this->hasMany(CurriculumRequirement::class);
    }

    /** @return HasMany<StudentCourse, $this> */
    public function studentCourses(): HasMany
    {
        return $this->hasMany(StudentCourse::class);
    }

    protected function casts(): array
    {
        return ['is_pace_course' => 'boolean', 'is_active' => 'boolean'];
    }
}
