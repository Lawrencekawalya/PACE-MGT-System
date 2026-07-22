<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use App\RoleName;
use App\StudentStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudentRegistrationService
{
    /** @param array<string, mixed> $data */
    public function create(array $data, User $actor): Student
    {
        return DB::transaction(function () use ($data, $actor): Student {
            $teacherId = $actor->hasRole(RoleName::Administrator)
                ? $data['teacher_id']
                : $actor->id;
            unset($data['teacher_id']);

            $student = Student::query()->create([
                ...$data,
                'teacher_id' => $teacherId,
                'registered_by' => $actor->id,
                'admission_number' => 'pending-'.Str::uuid(),
                'status' => StudentStatus::Active,
            ]);
            $student->update([
                'admission_number' => sprintf('FICA-%s-%06d', now()->format('Y'), $student->id),
            ]);

            return $student;
        });
    }
}
