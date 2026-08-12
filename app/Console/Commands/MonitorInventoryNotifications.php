<?php

namespace App\Console\Commands;

use App\Models\InventoryItem;
use App\NotificationCategory;
use App\NotificationPriority;
use App\PermissionName;
use App\Services\NotificationRecipientService;
use App\Services\OperationalAlertService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('notifications:monitor-inventory')]
#[Description('Monitor configured inventory items for low-stock and out-of-stock conditions')]
class MonitorInventoryNotifications extends Command
{
    public function handle(OperationalAlertService $alerts, NotificationRecipientService $recipients): int
    {
        $items = InventoryItem::query()
            ->where('is_active', true)
            ->where('reorder_level', '>', 0)
            ->withSum('movements as on_hand', 'quantity')
            ->get();
        $lowStock = $items->filter(fn (InventoryItem $item): bool => (int) ($item->on_hand ?? 0) <= $item->reorder_level);
        $outOfStock = $lowStock->filter(fn (InventoryItem $item): bool => (int) ($item->on_hand ?? 0) <= 0);
        $count = $lowStock->count();
        $outCount = $outOfStock->count();

        $alerts->observe(
            key: 'inventory:low-stock',
            active: $count > 0,
            affectedCount: $count,
            category: NotificationCategory::Inventory,
            priority: $outCount > 0 ? NotificationPriority::Critical : NotificationPriority::ActionRequired,
            title: 'Inventory stock requires attention',
            message: "{$count} configured item(s) are at or below reorder level; {$outCount} are out of stock.",
            url: route('reorders.index'),
            recipients: $recipients->withPermission(PermissionName::AdjustInventory),
            reminderMinutes: (int) config('operations.notification_reminders.inventory_minutes'),
            context: ['low_stock_count' => $count, 'out_of_stock_count' => $outCount],
            metadata: ['low_stock_count' => $count, 'out_of_stock_count' => $outCount],
        );

        $this->info("Inventory monitor reviewed {$items->count()} configured item(s); {$count} require attention.");

        return self::SUCCESS;
    }
}
