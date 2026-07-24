<?php

namespace App\Models;

use App\EnrollmentStatus;
use App\RoleName;
use App\StudentStatus;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $date_of_birth
 * @property StudentStatus $status
 */
#[Fillable(['registered_by', 'admission_number', 'first_name', 'last_name', 'other_names', 'date_of_birth', 'gender', 'guardian_name', 'guardian_phone', 'guardian_email', 'status', 'notes'])]
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

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

    /** @return HasOne<StudentEnrollment, $this> */
    public function activeEnrollment(): HasOne
    {
        return $this->hasOne(StudentEnrollment::class)
            ->where('status', EnrollmentStatus::Active)
            ->whereHas('academicYear', fn (Builder $query) => $query->where('is_active', true));
    }

    public function getFullNameAttribute(): string
    {
        return collect([$this->first_name, $this->other_names, $this->last_name])->filter()->implode(' ');
    }

    /** @param Builder<Student> $query */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->hasRole(RoleName::Teacher) && ! $user->hasRole(RoleName::Administrator)) {
            $query->whereHas('activeEnrollment.learningCenter.teachers', fn (Builder $query) => $query->whereKey($user->id));
        }
    }

    public function isManagedBy(User $user): bool
    {
        if ($user->hasRole(RoleName::Teacher) && ! $user->hasRole(RoleName::Administrator)) {
            return $this->activeEnrollment()
                ->whereHas('learningCenter.teachers', fn (Builder $query) => $query->whereKey($user->id))
                ->exists();
        }

        return true;
    }

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'status' => StudentStatus::class];
    }
}
