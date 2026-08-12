<?php

namespace App\Console\Commands;

use App\Models\InventoryItem;
use App\Models\PaceAccountTransaction;
use App\Models\PurchaseOrder;
use App\Models\Student;
use App\Models\Term;
use App\NotificationCategory;
use App\NotificationPriority;
use App\Notifications\OperationalNotification;
use App\PurchaseOrderStatus;
use App\RoleName;
use App\Services\NotificationDispatcher;
use App\Services\NotificationRecipientService;
use App\Services\SystemHealthService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('notifications:send-operational-summaries')]
#[Description('Send deduplicated daily operational summaries to responsible staff')]
class SendOperationalNotificationSummaries extends Command
{
    public function handle(
        NotificationRecipientService $recipients,
        NotificationDispatcher $notifications,
        SystemHealthService $health,
    ): int {
        $date = today()->toDateString();
        $this->orderingSummary($recipients, $notifications, $date);
        $this->financeSummary($recipients, $notifications, $date);
        $this->systemSummary($recipients, $notifications, $health, $date);

        $this->info('Operational notification summaries reviewed.');

        return self::SUCCESS;
    }

    private function orderingSummary(NotificationRecipientService $recipients, NotificationDispatcher $notifications, string $date): void
    {
        $items = InventoryItem::query()
            ->where('is_active', true)
            ->where('reorder_level', '>', 0)
            ->withSum('movements as on_hand', 'quantity')
            ->get();
        $lowStock = $items->filter(fn (InventoryItem $item): bool => (int) ($item->on_hand ?? 0) <= $item->reorder_level)->count();
        $approved = PurchaseOrder::query()->where('status', PurchaseOrderStatus::Approved)->count();
        $overdue = PurchaseOrder::query()
            ->whereIn('status', [PurchaseOrderStatus::Sent, PurchaseOrderStatus::PartiallyReceived])
            ->whereDate('expected_on', '<', today())
            ->count();
        if ($lowStock + $approved + $overdue === 0) {
            return;
        }

        $notifications->send($recipients->withRole(RoleName::PaceOfficer), new OperationalNotification(
            'Daily inventory and ordering attention',
            "{$lowStock} low-stock item(s), {$approved} approved order(s) awaiting sending, and {$overdue} overdue delivery or deliveries.",
            route('reorders.index'),
            NotificationCategory::Ordering,
            NotificationPriority::ActionRequired,
            "operational-summary:ordering:{$date}",
        ));
    }

    private function financeSummary(NotificationRecipientService $recipients, NotificationDispatcher $notifications, string $date): void
    {
        $term = Term::query()->where('is_active', true)->first();
        if ($term === null || (float) $term->pace_cost <= 0) {
            return;
        }
        $balances = PaceAccountTransaction::query()
            ->selectRaw('student_id, SUM(amount) as balance')
            ->groupBy('student_id')
            ->pluck('balance', 'student_id');
        $insufficient = Student::query()->where('status', 'active')->pluck('id')
            ->filter(fn (int $studentId): bool => (float) ($balances->get($studentId) ?? 0) < (float) $term->pace_cost)
            ->count();
        if ($insufficient === 0) {
            return;
        }

        $notifications->send($recipients->withRole(RoleName::Accountant), new OperationalNotification(
            'PACE balances require attention',
            "{$insufficient} active student(s) cannot fund another PACE at the current term cost.",
            route('pace-accounts.index', ['balance' => 'insufficient']),
            NotificationCategory::Finance,
            NotificationPriority::Warning,
            "operational-summary:finance:{$date}",
        ));
    }

    private function systemSummary(NotificationRecipientService $recipients, NotificationDispatcher $notifications, SystemHealthService $health, string $date): void
    {
        $failed = collect($health->infrastructure()['checks'])->whereIn('status', ['failed', 'warning']);
        if ($failed->isEmpty()) {
            return;
        }

        $notifications->send($recipients->withRole(RoleName::Administrator), new OperationalNotification(
            'System health requires attention',
            $failed->pluck('label')->implode(', ').' reported a warning or failure.',
            route('admin.system-status'),
            NotificationCategory::System,
            $failed->contains('status', 'failed') ? NotificationPriority::Critical : NotificationPriority::Warning,
            "operational-summary:system:{$date}",
        ));
    }
}
