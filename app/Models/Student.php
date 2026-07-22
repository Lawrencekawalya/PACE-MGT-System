<?php

namespace App\Models;

use App\StudentStatus;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $date_of_birth
 * @property StudentStatus $status
 */
#[Fillable(['admission_number', 'first_name', 'last_name', 'other_names', 'date_of_birth', 'gender', 'guardian_name', 'guardian_phone', 'guardian_email', 'status', 'notes'])]
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    /** @return HasMany<StudentEnrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class)->orderByDesc('enrolled_on');
    }

    public function getFullNameAttribute(): string
    {
        return collect([$this->first_name, $this->other_names, $this->last_name])->filter()->implode(' ');
    }

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'status' => StudentStatus::class];
    }
}
