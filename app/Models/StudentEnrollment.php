<?php

namespace App\Models;

use App\EnrollmentStatus;
use App\RoleName;
use Database\Factories\StudentEnrollmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $enrolled_on
 * @property Carbon|null $decision_at
 * @property EnrollmentStatus $status
 */
#[Fillable(['student_id', 'previous_enrollment_id', 'learning_center_id', 'academic_year_id', 'term_id', 'level_id', 'status', 'enrolled_on', 'decision_by', 'decision_at', 'decision_reason'])]
class StudentEnrollment extends Model
{
    /** @use HasFactory<StudentEnrollmentFactory> */
    use HasFactory;

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<StudentEnrollment, $this> */
    public function previousEnrollment(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_enrollment_id');
    }

    /** @return HasOne<StudentEnrollment, $this> */
    public function nextEnrollment(): HasOne
    {
        return $this->hasOne(self::class, 'previous_enrollment_id');
    }

    /** @return BelongsTo<LearningCenter, $this> */
    public function learningCenter(): BelongsTo
    {
        return $this->belongsTo(LearningCenter::class);
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

    /** @return BelongsTo<Level, $this> */
    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    /** @return BelongsTo<User, $this> */
    public function decisionMaker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decision_by');
    }

    /** @return HasMany<StudentCourse, $this> */
    public function studentCourses(): HasMany
    {
        return $this->hasMany(StudentCourse::class);
    }

    public function isManagedBy(User $user): bool
    {
        if ($user->hasRole(RoleName::Teacher) && ! $user->hasRole(RoleName::Administrator)) {
            return $this->learningCenter()
                ->whereHas('teachers', fn (Builder $query) => $query->whereKey($user->id))
                ->exists();
        }

        return true;
    }

    protected function casts(): array
    {
        return ['status' => EnrollmentStatus::class, 'enrolled_on' => 'date', 'decision_at' => 'datetime'];
    }
}
