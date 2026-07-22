<?php

use App\Models\User;
use App\RoleName;
use Database\Seeders\AccessControlSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
});

test('teacher dashboard shows academic metrics and overdue queue only', function () {
    $fixture = createReportFixture();

    $this->actingAs($fixture['teacher'])->get(route('dashboard'))->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('academic.metrics.active_students', 1)
            ->where('academic.metrics.active_assignments', 1)
            ->where('academic.metrics.overdue', 1)
            ->has('academic.queue', 1)
            ->where('inventory', null));
});

test('storekeeper dashboard shows inventory metrics but no academic data', function () {
    createReportFixture();
    $storekeeper = createStaffWithRole(RoleName::Storekeeper);

    $this->actingAs($storekeeper)->get(route('dashboard'))->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('academic', null)
            ->where('inventory.metrics.on_hand', 0)
            ->where('inventory.metrics.out_of_stock', 3)
            ->has('inventory.queue', 3));
});

test('administrator dashboard includes both reporting domains', function () {
    createReportFixture();
    $administrator = createStaffWithRole(RoleName::Administrator);

    $this->actingAs($administrator)->get(route('dashboard'))->assertOk()
        ->assertInertia(fn ($page) => $page->has('academic.metrics')->has('inventory.metrics')->has('setup'));
});

test('user without reporting permissions receives no operational reporting data', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk()
        ->assertInertia(fn ($page) => $page->where('academic', null)->where('inventory', null));
});
