<?php

use App\Models\InventoryItem;
use App\NotificationCategory;
use App\RoleName;
use Database\Seeders\AccessControlSeeder;

beforeEach(function () {
    $this->seed(AccessControlSeeder::class);
});

test('daily low stock summary alerts administrators and PACE Officers once per day', function () {
    $administrator = createStaffWithRole(RoleName::Administrator);
    $officer = createStaffWithRole(RoleName::PaceOfficer);
    $teacher = createStaffWithRole(RoleName::Teacher);
    InventoryItem::factory()->create([
        'reorder_level' => 25,
        'target_stock_level' => 100,
    ]);

    $this->artisan('notifications:send-operational-summaries')->assertSuccessful();

    $eventKey = 'operational-summary:ordering:'.today()->toDateString();
    foreach ([$administrator, $officer] as $recipient) {
        $notification = $recipient->notifications()->where('data->event_key', $eventKey)->sole();
        expect($notification->data['category'])->toBe(NotificationCategory::Ordering->value)
            ->and($notification->data['event_key'])->toBe($eventKey);
    }
    expect($teacher->notifications()->count())->toBe(0);

    $this->artisan('notifications:send-operational-summaries')->assertSuccessful();
    expect($administrator->notifications()->where('data->event_key', $eventKey)->count())->toBe(1)
        ->and($officer->notifications()->where('data->event_key', $eventKey)->count())->toBe(1);
});
