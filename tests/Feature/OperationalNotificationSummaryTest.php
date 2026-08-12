<?php

use App\Models\InventoryItem;
use App\Models\OperationalAlert;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Student;
use App\Models\Term;
use App\NotificationCategory;
use App\NotificationPriority;
use App\OperationalAlertStatus;
use App\PurchaseOrderStatus;
use App\RoleName;
use App\Services\NotificationRecipientService;
use App\Services\OperationalAlertService;
use App\StockMovementType;
use Database\Seeders\AccessControlSeeder;

beforeEach(function () {
    $this->seed(AccessControlSeeder::class);
});

test('inventory monitoring alerts responsible staff without duplicating an unchanged condition', function () {
    $administrator = createStaffWithRole(RoleName::Administrator);
    $officer = createStaffWithRole(RoleName::PaceOfficer);
    $teacher = createStaffWithRole(RoleName::Teacher);
    InventoryItem::factory()->create([
        'reorder_level' => 25,
        'target_stock_level' => 100,
    ]);

    $this->artisan('notifications:monitor-inventory')->assertSuccessful();

    $alert = OperationalAlert::query()->where('key', 'inventory:low-stock')->sole();
    expect($alert->status)->toBe(OperationalAlertStatus::Active)
        ->and($alert->priority)->toBe(NotificationPriority::Critical)
        ->and($alert->affected_count)->toBe(1)
        ->and($alert->notification_sequence)->toBe(1);

    $eventKey = "operational-alert:{$alert->id}:1";
    foreach ([$administrator, $officer] as $recipient) {
        $notification = $recipient->notifications()->where('data->event_key', $eventKey)->sole();
        expect($notification->data['category'])->toBe(NotificationCategory::Inventory->value)
            ->and($notification->data['event_key'])->toBe($eventKey);
    }
    expect($teacher->notifications()->count())->toBe(0);

    $this->artisan('notifications:monitor-inventory')->assertSuccessful();
    expect($administrator->notifications()->where('data->event_key', $eventKey)->count())->toBe(1)
        ->and($officer->notifications()->where('data->event_key', $eventKey)->count())->toBe(1)
        ->and($alert->fresh()->notification_sequence)->toBe(1);
});

test('inventory monitoring sends an escalation and resolves the alert after stock recovery', function () {
    config(['operations.notification_reminders.inventory_minutes' => 240]);
    $administrator = createStaffWithRole(RoleName::Administrator);
    $item = InventoryItem::factory()->create([
        'reorder_level' => 25,
        'target_stock_level' => 100,
    ]);
    StockMovement::factory()->create([
        'inventory_item_id' => $item->id,
        'type' => StockMovementType::Receipt,
        'quantity' => 10,
        'balance_after' => 10,
    ]);

    $this->artisan('notifications:monitor-inventory')->assertSuccessful();
    $alert = OperationalAlert::query()->where('key', 'inventory:low-stock')->sole();
    expect($alert->priority)->toBe(NotificationPriority::ActionRequired)
        ->and($alert->notification_sequence)->toBe(1);

    StockMovement::factory()->create([
        'inventory_item_id' => $item->id,
        'type' => StockMovementType::Issue,
        'quantity' => -10,
        'balance_after' => 0,
    ]);
    $this->artisan('notifications:monitor-inventory')->assertSuccessful();
    expect($alert->fresh()->priority)->toBe(NotificationPriority::Critical)
        ->and($alert->fresh()->notification_sequence)->toBe(2)
        ->and($administrator->notifications()->count())->toBe(2);

    StockMovement::factory()->create([
        'inventory_item_id' => $item->id,
        'type' => StockMovementType::Receipt,
        'quantity' => 100,
        'balance_after' => 100,
    ]);
    $this->artisan('notifications:monitor-inventory')->assertSuccessful();
    expect($alert->fresh()->status)->toBe(OperationalAlertStatus::Resolved)
        ->and($alert->fresh()->affected_count)->toBe(0)
        ->and($administrator->notifications()->count())->toBe(2);
});

test('active operational alerts send reminders only after their configured interval', function () {
    config(['operations.notification_reminders.inventory_minutes' => 60]);
    $administrator = createStaffWithRole(RoleName::Administrator);
    InventoryItem::factory()->create(['reorder_level' => 5, 'target_stock_level' => 20]);

    $this->artisan('notifications:monitor-inventory')->assertSuccessful();
    $this->travel(59)->minutes();
    $this->artisan('notifications:monitor-inventory')->assertSuccessful();
    expect($administrator->notifications()->count())->toBe(1);

    $this->travel(2)->minutes();
    $this->artisan('notifications:monitor-inventory')->assertSuccessful();
    expect($administrator->notifications()->count())->toBe(2)
        ->and(OperationalAlert::query()->where('key', 'inventory:low-stock')->sole()->notification_sequence)->toBe(2);
});

test('system alerts notify administrators when a monitored condition recovers', function () {
    $administrator = createStaffWithRole(RoleName::Administrator);
    $service = app(OperationalAlertService::class);
    $recipients = app(NotificationRecipientService::class)->withRole(RoleName::Administrator);

    $service->observe(
        key: 'system:test-health',
        active: true,
        affectedCount: 1,
        category: NotificationCategory::System,
        priority: NotificationPriority::Critical,
        title: 'System health requires attention',
        message: 'Queue reported a failure.',
        url: route('admin.system-status'),
        recipients: $recipients,
        reminderMinutes: 30,
        metadata: ['checks' => [['key' => 'queue', 'status' => 'failed']]],
        notifyResolution: true,
    );
    $service->observe(
        key: 'system:test-health',
        active: true,
        affectedCount: 1,
        category: NotificationCategory::System,
        priority: NotificationPriority::Critical,
        title: 'System health requires attention',
        message: 'Scheduler reported a failure.',
        url: route('admin.system-status'),
        recipients: $recipients,
        reminderMinutes: 30,
        metadata: ['checks' => [['key' => 'scheduler', 'status' => 'failed']]],
        notifyResolution: true,
    );
    $service->observe(
        key: 'system:test-health',
        active: false,
        affectedCount: 0,
        category: NotificationCategory::System,
        priority: NotificationPriority::Information,
        title: 'System health requires attention',
        message: 'All checks passed.',
        url: route('admin.system-status'),
        recipients: $recipients,
        reminderMinutes: 30,
        notifyResolution: true,
    );

    $alert = OperationalAlert::query()->where('key', 'system:test-health')->sole();
    expect($alert->status)->toBe(OperationalAlertStatus::Resolved)
        ->and($alert->notification_sequence)->toBe(3)
        ->and($administrator->notifications()->count())->toBe(3)
        ->and($administrator->notifications()->get()->pluck('data.title')->all())->toContain('System health requires attention resolved');
});

test('order monitoring routes approval and sending work to the responsible roles', function () {
    $administrator = createStaffWithRole(RoleName::Administrator);
    $officer = createStaffWithRole(RoleName::PaceOfficer);
    $teacher = createStaffWithRole(RoleName::Teacher);
    PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::Submitted]);
    PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::Approved]);

    $this->artisan('notifications:monitor-orders')->assertSuccessful();

    expect($administrator->notifications()->get()->pluck('data.title')->all())
        ->toContain('Purchase orders await approval')
        ->not->toContain('Approved purchase orders await sending')
        ->and($officer->notifications()->get()->pluck('data.title')->all())
        ->toContain('Approved purchase orders await sending')
        ->and($teacher->notifications()->count())->toBe(0);
});

test('PACE account monitoring alerts accountants and administrators with oversight access', function () {
    $administrator = createStaffWithRole(RoleName::Administrator);
    $accountant = createStaffWithRole(RoleName::Accountant);
    $officer = createStaffWithRole(RoleName::PaceOfficer);
    Term::factory()->create(['is_active' => true, 'pace_cost' => 15_000]);
    Student::factory()->create();

    $this->artisan('notifications:monitor-pace-accounts')->assertSuccessful();

    foreach ([$administrator, $accountant] as $recipient) {
        expect($recipient->notifications()->get()->pluck('data.title')->all())
            ->toContain('PACE balances require attention');
    }
    expect($officer->notifications()->count())->toBe(0);
});
