<?php

namespace App\Models;

use App\StudentCourseStatus;
use Database\Factories\StudentCourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property StudentCourseStatus $status */
#[Fillable(['student_enrollment_id', 'course_id', 'starting_pace_id', 'current_pace_id', 'status', 'is_curriculum_required', 'placement_reason', 'assigned_by'])]
class StudentCourse extends Model
{
    /** @use HasFactory<StudentCourseFactory> */
    use HasFactory;

    /** @return BelongsTo<StudentEnrollment, $this> */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return BelongsTo<Pace, $this> */
    public function startingPace(): BelongsTo
    {
        return $this->belongsTo(Pace::class, 'starting_pace_id');
    }

    /** @return BelongsTo<Pace, $this> */
    public function currentPace(): BelongsTo
    {
        return $this->belongsTo(Pace::class, 'current_pace_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    protected function casts(): array
    {
        return ['status' => StudentCourseStatus::class, 'is_curriculum_required' => 'boolean'];
    }
}
