<?php

namespace App\Console\Commands;

use App\Models\PaceAccountTransaction;
use App\Models\Student;
use App\Models\Term;
use App\NotificationCategory;
use App\NotificationPriority;
use App\PermissionName;
use App\Services\NotificationRecipientService;
use App\Services\OperationalAlertService;
use App\StudentStatus;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('notifications:monitor-pace-accounts')]
#[Description('Monitor active students whose PACE account cannot fund another issue')]
class MonitorPaceAccountNotifications extends Command
{
    public function handle(OperationalAlertService $alerts, NotificationRecipientService $recipients): int
    {
        $term = Term::query()->where('is_active', true)->first();
        $balances = PaceAccountTransaction::query()
            ->selectRaw('student_id, SUM(amount) as balance')
            ->groupBy('student_id')
            ->pluck('balance', 'student_id');
        $insufficient = $term === null || (float) $term->pace_cost <= 0
            ? collect()
            : Student::query()->where('status', StudentStatus::Active)->pluck('id')
                ->filter(fn (int $studentId): bool => (float) ($balances->get($studentId) ?? 0) < (float) $term->pace_cost);
        $count = $insufficient->count();

        $alerts->observe(
            key: 'finance:insufficient-pace-balances',
            active: $count > 0,
            affectedCount: $count,
            category: NotificationCategory::Finance,
            priority: NotificationPriority::Warning,
            title: 'PACE balances require attention',
            message: "{$count} active student(s) cannot fund another PACE at the current term cost.",
            url: route('pace-accounts.index', ['balance' => 'insufficient']),
            recipients: $recipients->withPermission(PermissionName::ManagePaceAccounts),
            reminderMinutes: (int) config('operations.notification_reminders.finance_minutes'),
            context: ['student_count' => $count, 'term_id' => $term?->id],
            metadata: ['student_count' => $count, 'term_id' => $term?->id, 'pace_cost' => $term?->pace_cost],
        );

        $this->info("PACE-account monitor found {$count} student(s) requiring attention.");

        return self::SUCCESS;
    }
}
