<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\User;
use App\StudentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'admission_number' => fake()->unique()->bothify('FICA-2026-######'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'other_names' => null,
            'date_of_birth' => fake()->dateTimeBetween('-18 years', '-5 years'),
            'gender' => fake()->randomElement(['male', 'female']),
            'guardian_name' => fake()->name(),
            'guardian_phone' => fake()->phoneNumber(),
            'guardian_email' => fake()->optional()->safeEmail(),
            'status' => StudentStatus::Active,
            'notes' => null,
        ];
    }

    public function supervisedBy(User $teacher): static
    {
        return $this->state(fn (): array => [
            'teacher_id' => $teacher->id,
            'registered_by' => $teacher->id,
        ]);
    }
}
