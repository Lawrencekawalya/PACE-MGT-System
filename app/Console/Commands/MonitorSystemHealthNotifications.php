<?php

namespace App\Console\Commands;

use App\NotificationCategory;
use App\NotificationPriority;
use App\RoleName;
use App\Services\NotificationRecipientService;
use App\Services\OperationalAlertService;
use App\Services\SystemHealthService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('notifications:monitor-system-health')]
#[Description('Monitor infrastructure health and notify administrators about failures and recovery')]
class MonitorSystemHealthNotifications extends Command
{
    public function handle(
        SystemHealthService $health,
        OperationalAlertService $alerts,
        NotificationRecipientService $recipients,
    ): int {
        $checks = collect($health->infrastructure()['checks']);
        $problems = $checks->whereIn('status', ['failed', 'warning']);
        $failed = $problems->where('status', 'failed');
        $labels = $problems->pluck('label')->implode(', ');

        $alerts->observe(
            key: 'system:infrastructure-health',
            active: $problems->isNotEmpty(),
            affectedCount: $problems->count(),
            category: NotificationCategory::System,
            priority: $failed->isNotEmpty() ? NotificationPriority::Critical : NotificationPriority::Warning,
            title: 'System health requires attention',
            message: $problems->isEmpty() ? 'All infrastructure checks passed.' : "{$labels} reported a warning or failure.",
            url: route('admin.system-status'),
            recipients: $recipients->withRole(RoleName::Administrator),
            reminderMinutes: (int) config('operations.notification_reminders.system_minutes'),
            context: ['problem_count' => $problems->count(), 'failed_count' => $failed->count()],
            metadata: [
                'checks' => $problems
                    ->map(fn (array $check): array => ['key' => $check['key'], 'status' => $check['status']])
                    ->values()
                    ->all(),
            ],
            notifyResolution: true,
        );

        $this->info("System-health monitor found {$problems->count()} warning or failed check(s).");

        return self::SUCCESS;
    }
}
