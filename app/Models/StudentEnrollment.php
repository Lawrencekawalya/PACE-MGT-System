<?php

namespace App\Models;

use App\EnrollmentStatus;
use Database\Factories\StudentEnrollmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $enrolled_on
 * @property EnrollmentStatus $status
 */
#[Fillable(['student_id', 'academic_year_id', 'term_id', 'level_id', 'status', 'enrolled_on', 'decision_by', 'decision_at', 'decision_reason'])]
class StudentEnrollment extends Model
{
    /** @use HasFactory<StudentEnrollmentFactory> */
    use HasFactory;

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
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

    protected function casts(): array
    {
        return ['status' => EnrollmentStatus::class, 'enrolled_on' => 'date', 'decision_at' => 'datetime'];
    }
}
