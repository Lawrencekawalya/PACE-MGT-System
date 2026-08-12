<?php

namespace App\Console\Commands;

use App\Models\PurchaseOrder;
use App\NotificationCategory;
use App\NotificationPriority;
use App\PermissionName;
use App\PurchaseOrderStatus;
use App\RoleName;
use App\Services\NotificationRecipientService;
use App\Services\OperationalAlertService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('notifications:monitor-orders')]
#[Description('Monitor submitted, approved, and overdue purchase orders')]
class MonitorOrderNotifications extends Command
{
    public function handle(OperationalAlertService $alerts, NotificationRecipientService $recipients): int
    {
        $reminder = (int) config('operations.notification_reminders.order_minutes');
        $submitted = PurchaseOrder::query()->where('status', PurchaseOrderStatus::Submitted)->count();
        $approved = PurchaseOrder::query()->where('status', PurchaseOrderStatus::Approved)->count();
        $overdue = PurchaseOrder::query()
            ->whereIn('status', [PurchaseOrderStatus::Sent, PurchaseOrderStatus::PartiallyReceived])
            ->whereDate('expected_on', '<', today())
            ->count();

        $alerts->observe(
            'orders:submitted',
            $submitted > 0,
            $submitted,
            NotificationCategory::Ordering,
            NotificationPriority::ActionRequired,
            'Purchase orders await approval',
            "{$submitted} submitted purchase order(s) require an approval decision.",
            route('purchase-orders.submitted'),
            $recipients->withPermission(PermissionName::ApprovePurchaseOrders),
            $reminder,
            ['submitted_count' => $submitted],
            ['submitted_count' => $submitted],
        );
        $alerts->observe(
            'orders:approved-unsent',
            $approved > 0,
            $approved,
            NotificationCategory::Ordering,
            NotificationPriority::ActionRequired,
            'Approved purchase orders await sending',
            "{$approved} approved purchase order(s) have not been marked as sent.",
            route('purchase-orders.approved'),
            $recipients->withRole(RoleName::PaceOfficer),
            $reminder,
            ['approved_count' => $approved],
            ['approved_count' => $approved],
        );
        $alerts->observe(
            'orders:overdue-deliveries',
            $overdue > 0,
            $overdue,
            NotificationCategory::Ordering,
            NotificationPriority::Warning,
            'Purchase-order deliveries are overdue',
            "{$overdue} sent purchase order(s) are past their expected delivery date.",
            route('purchase-orders.sent'),
            $recipients->withPermission(PermissionName::ReceivePurchaseOrders),
            $reminder,
            ['overdue_count' => $overdue],
            ['overdue_count' => $overdue],
        );

        $this->info("Order monitor found {$submitted} submitted, {$approved} approved-unsent, and {$overdue} overdue order(s).");

        return self::SUCCESS;
    }
}
