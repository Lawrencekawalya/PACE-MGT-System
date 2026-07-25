<?php

namespace Database\Factories;

use App\Models\StudentEnrollment;
use App\Models\Term;
use App\Models\TuitionClearance;
use App\Models\User;
use App\TuitionClearanceStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TuitionClearance>
 */
class TuitionClearanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_enrollment_id' => StudentEnrollment::factory(),
            'term_id' => Term::factory(),
            'status' => TuitionClearanceStatus::Unconfirmed,
            'reference' => null,
            'notes' => null,
            'recorded_by' => User::factory(),
            'recorded_at' => now(),
        ];
    }
}
