<?php

use App\Models\User;
use App\NotificationCategory;
use App\NotificationPriority;
use App\Notifications\OperationalNotification;
use Database\Seeders\AccessControlSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
});

test('authenticated staff see their notification centre and shared unread count', function () {
    $user = User::factory()->create();
    $user->notify(new OperationalNotification(
        'Action required',
        'A workflow item needs attention.',
        route('dashboard'),
        NotificationCategory::Administration,
        NotificationPriority::ActionRequired,
        'test:notification-centre',
    ));

    $this->actingAs($user)->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('notifications/Index')
            ->has('notifications.data', 1)
            ->where('notifications.data.0.title', 'Action required')
            ->where('notifications.data.0.read_at', null)
            ->where('notificationFeed.unread_count', 1));
});

test('staff can mark their notifications read but cannot access another users notification', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $notification = new OperationalNotification(
        'Private alert',
        'This alert belongs to one user.',
        route('dashboard'),
        NotificationCategory::System,
    );
    $user->notify($notification);
    $other->notify($notification);

    $otherNotification = $other->notifications()->sole();
    $this->actingAs($user)->patch(route('notifications.read', $otherNotification))->assertNotFound();

    $ownNotification = $user->notifications()->sole();
    $this->actingAs($user)->patch(route('notifications.read', $ownNotification))->assertRedirect();
    expect($ownNotification->fresh()->read_at)->not->toBeNull()
        ->and($otherNotification->fresh()->read_at)->toBeNull();
});

test('mark all read only affects the authenticated user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $notification = new OperationalNotification(
        'Daily attention',
        'Review the queue.',
        route('dashboard'),
        NotificationCategory::Administration,
    );
    $user->notify($notification);
    $user->notify(new OperationalNotification('Second alert', 'Review another queue.', route('dashboard'), NotificationCategory::Academic));
    $other->notify($notification);

    $this->actingAs($user)->patch(route('notifications.read-all'))->assertRedirect();

    expect($user->fresh()->unreadNotifications()->count())->toBe(0)
        ->and($other->fresh()->unreadNotifications()->count())->toBe(1);
});
