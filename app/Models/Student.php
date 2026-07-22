<?php

namespace App\Models;

use App\RoleName;
use App\StudentStatus;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $date_of_birth
 * @property StudentStatus $status
 */
#[Fillable(['teacher_id', 'registered_by', 'admission_number', 'first_name', 'last_name', 'other_names', 'date_of_birth', 'gender', 'guardian_name', 'guardian_phone', 'guardian_email', 'status', 'notes'])]
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    /** @return BelongsTo<User, $this> */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /** @return BelongsTo<User, $this> */
    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    /** @return HasMany<StudentEnrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class)->orderByDesc('enrolled_on');
    }

    public function getFullNameAttribute(): string
    {
        return collect([$this->first_name, $this->other_names, $this->last_name])->filter()->implode(' ');
    }

    /** @param Builder<Student> $query */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->hasRole(RoleName::Teacher) && ! $user->hasRole(RoleName::Administrator)) {
            $query->where('teacher_id', $user->id);
        }
    }

    public function isManagedBy(User $user): bool
    {
        if ($user->hasRole(RoleName::Teacher) && ! $user->hasRole(RoleName::Administrator)) {
            return $this->teacher_id === $user->id;
        }

        return true;
    }

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'status' => StudentStatus::class];
    }
}
