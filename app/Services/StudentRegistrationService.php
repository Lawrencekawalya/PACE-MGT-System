<?php

namespace App\Services;

use App\Models\Student;
use App\StudentStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudentRegistrationService
{
    /** @param array<string, mixed> $data */
    public function create(array $data): Student
    {
        return DB::transaction(function () use ($data): Student {
            $student = Student::query()->create([
                ...$data,
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
