<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\PaceAccountTransaction;
use App\Models\PaceAssignment;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use App\PaceAccountTransactionType;
use App\RoleName;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaceAccountService
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function balance(Student|int $student): string
    {
        $studentId = $student instanceof Student ? $student->id : $student;
        $balance = PaceAccountTransaction::query()
            ->where('student_id', $studentId)
            ->sum('amount');

        return $this->fromMinorUnits($this->toMinorUnits((string) $balance));
    }

    public function recordPayment(
        Student $student,
        string $amount,
        CarbonInterface $paidAt,
        ?string $reference,
        ?string $notes,
        User $actor,
    ): PaceAccountTransaction {
        return DB::transaction(function () use ($student, $amount, $paidAt, $reference, $notes, $actor): PaceAccountTransaction {
            $student = Student::query()->lockForUpdate()->findOrFail($student->id);
            $amountMinor = $this->toMinorUnits($amount);
            if ($amountMinor <= 0) {
                throw ValidationException::withMessages(['amount' => 'The payment amount must be greater than zero.']);
            }

            $balanceAfter = $this->toMinorUnits($this->balance($student)) + $amountMinor;
            $year = AcademicYear::query()->where('is_active', true)->first();
            $term = $year === null ? null : Term::query()
                ->where('academic_year_id', $year->id)
                ->where('is_active', true)
                ->first();
            $transaction = PaceAccountTransaction::query()->create([
                'student_id' => $student->id,
                'academic_year_id' => $year?->id,
                'term_id' => $term?->id,
                'type' => PaceAccountTransactionType::Payment,
                'amount' => $this->fromMinorUnits($amountMinor),
                'balance_after' => $this->fromMinorUnits($balanceAfter),
                'reference' => filled($reference) ? trim($reference) : null,
                'notes' => filled($notes) ? trim($notes) : null,
                'recorded_by' => $actor->id,
                'recorded_at' => $paidAt,
            ]);
            $this->activityLogger->record(
                $actor,
                'pace-account.payment-recorded',
                $transaction,
                newValues: [
                    'student_id' => $student->id,
                    'amount' => $transaction->amount,
                    'balance_after' => $transaction->balance_after,
                    'reference' => $transaction->reference,
                ],
            );

            return $transaction;
        }, 3);
    }

    public function chargeIssue(PaceAssignment $assignment, User $actor): PaceAccountTransaction
    {
        return DB::transaction(function () use ($assignment, $actor): PaceAccountTransaction {
            $assignment = PaceAssignment::query()
                ->with('studentCourse.enrollment.student')
                ->lockForUpdate()
                ->findOrFail($assignment->id);
            $existing = PaceAccountTransaction::query()
                ->where('pace_assignment_id', $assignment->id)
                ->where('type', PaceAccountTransactionType::PaceIssue)
                ->first();
            if ($existing !== null) {
                return $existing;
            }

            $student = Student::query()->lockForUpdate()->findOrFail(
                $assignment->studentCourse->enrollment->student_id,
            );
            $settings = SchoolSetting::query()->lockForUpdate()->findOrFail(SchoolSetting::current()->id);
            $costMinor = $this->toMinorUnits($settings->pace_cost);
            if ($costMinor <= 0) {
                throw ValidationException::withMessages([
                    'balance' => 'The Accountant must set the PACE cost before any PACE can be issued.',
                ]);
            }

            $balanceMinor = $this->toMinorUnits($this->balance($student));
            if ($balanceMinor < $costMinor) {
                throw ValidationException::withMessages([
                    'balance' => "{$student->full_name} has an insufficient PACE balance. Available UGX {$this->wholeAmount($balanceMinor)}; required UGX {$this->wholeAmount($costMinor)}.",
                ]);
            }

            return PaceAccountTransaction::query()->create([
                'student_id' => $student->id,
                'pace_assignment_id' => $assignment->id,
                'academic_year_id' => $assignment->academic_year_id,
                'term_id' => $assignment->term_id,
                'type' => PaceAccountTransactionType::PaceIssue,
                'amount' => $this->fromMinorUnits(-$costMinor),
                'balance_after' => $this->fromMinorUnits($balanceMinor - $costMinor),
                'reference' => 'PACE-'.$assignment->id,
                'notes' => 'Automatic charge for physical PACE issue.',
                'recorded_by' => $actor->id,
                'recorded_at' => now(),
            ]);
        }, 3);
    }

    public function reverseIssueCharge(PaceAssignment $assignment, string $reason, User $actor): ?PaceAccountTransaction
    {
        return DB::transaction(function () use ($assignment, $reason, $actor): ?PaceAccountTransaction {
            $assignment = PaceAssignment::query()->lockForUpdate()->findOrFail($assignment->id);
            $charge = PaceAccountTransaction::query()
                ->where('pace_assignment_id', $assignment->id)
                ->where('type', PaceAccountTransactionType::PaceIssue)
                ->lockForUpdate()
                ->first();
            if ($charge === null) {
                return null;
            }
            $existing = PaceAccountTransaction::query()
                ->where('pace_assignment_id', $assignment->id)
                ->where('type', PaceAccountTransactionType::IssueReversal)
                ->first();
            if ($existing !== null) {
                return $existing;
            }

            $student = Student::query()->lockForUpdate()->findOrFail($charge->student_id);
            $amountMinor = abs($this->toMinorUnits($charge->amount));
            $balanceAfter = $this->toMinorUnits($this->balance($student)) + $amountMinor;

            return PaceAccountTransaction::query()->create([
                'student_id' => $student->id,
                'pace_assignment_id' => $assignment->id,
                'academic_year_id' => $assignment->academic_year_id,
                'term_id' => $assignment->term_id,
                'type' => PaceAccountTransactionType::IssueReversal,
                'amount' => $this->fromMinorUnits($amountMinor),
                'balance_after' => $this->fromMinorUnits($balanceAfter),
                'reference' => 'REV-PACE-'.$assignment->id,
                'notes' => trim($reason),
                'recorded_by' => $actor->id,
                'recorded_at' => now(),
            ]);
        }, 3);
    }

    public function updatePaceCost(string $cost, User $actor): SchoolSetting
    {
        if (! $actor->hasRole(RoleName::Accountant)) {
            throw ValidationException::withMessages(['pace_cost' => 'Only an Accountant can set the PACE cost.']);
        }

        return DB::transaction(function () use ($cost, $actor): SchoolSetting {
            $settings = SchoolSetting::query()->lockForUpdate()->findOrFail(SchoolSetting::current()->id);
            $oldCost = $settings->pace_cost;
            $settings->update(['pace_cost' => $this->fromMinorUnits($this->toMinorUnits($cost))]);
            $this->activityLogger->record(
                $actor,
                'pace-account.cost-updated',
                $settings,
                ['pace_cost' => $oldCost],
                ['pace_cost' => $settings->pace_cost],
            );

            return $settings;
        }, 3);
    }

    private function toMinorUnits(string|float|int $amount): int
    {
        $normalized = trim((string) $amount);
        $negative = str_starts_with($normalized, '-');
        $normalized = ltrim($normalized, '+-');
        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '');
        $minor = ((int) ($whole === '' ? '0' : $whole) * 100)
            + (int) str_pad(substr($fraction, 0, 2), 2, '0');

        return $negative ? -$minor : $minor;
    }

    private function fromMinorUnits(int $amount): string
    {
        $sign = $amount < 0 ? '-' : '';
        $absolute = abs($amount);

        return sprintf('%s%d.%02d', $sign, intdiv($absolute, 100), $absolute % 100);
    }

    private function wholeAmount(int $minorUnits): string
    {
        return number_format($minorUnits / 100, 0);
    }
}
