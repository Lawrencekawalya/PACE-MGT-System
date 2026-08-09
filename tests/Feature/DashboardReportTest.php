<?php

use App\Models\User;
use App\RoleName;
use App\Services\PaceAccountService;
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
            ->where('academic.charts.target_status_by_subject.categories', ['Mathematics'])
            ->where('academic.charts.target_status_by_subject.series.0.data', [0])
            ->where('academic.charts.target_status_by_subject.series.1.data', [0])
            ->where('academic.charts.target_status_by_subject.series.2.data', [1])
            ->has('academic.queue', 1)
            ->where('inventory', null)
            ->where('paceAccounts', null));
});

test('storekeeper dashboard shows inventory metrics but no academic data', function () {
    createReportFixture();
    $storekeeper = createStaffWithRole(RoleName::PaceOfficer);

    $this->actingAs($storekeeper)->get(route('dashboard'))->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('academic', null)
            ->where('inventory.metrics.on_hand', 0)
            ->where('inventory.metrics.out_of_stock', 3)
            ->where('inventory.charts.stock_status.labels', ['Healthy', 'Low stock', 'Out of stock'])
            ->where('inventory.charts.stock_status.series', [0, 0, 3])
            ->has('inventory.charts.issuance_trend.categories', 8)
            ->where('inventory.charts.issuance_trend.series.0.data', [0, 0, 0, 0, 0, 0, 0, 0])
            ->has('inventory.queue', 3)
            ->where('paceAccounts', null));
});

test('PACE Officer dashboard charts physical issues in weekly buckets', function () {
    $this->travelTo(now()->setDate(2026, 7, 25)->startOfDay());
    createIssuingReportFixture();
    $officer = createStaffWithRole(RoleName::PaceOfficer);

    $this->actingAs($officer)->get(route('dashboard'))->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('inventory.charts.issuance_trend.series.0.data', [0, 0, 0, 0, 0, 0, 1, 0]));
});

test('Accountant dashboard shows student PACE balances and funding attention', function () {
    $fixture = createReportFixture();
    $accountant = createStaffWithRole(RoleName::Accountant);
    $fixture['term']->update(['pace_cost' => 15000]);
    app(PaceAccountService::class)->recordPayment(
        $fixture['student'],
        '10000.00',
        now(),
        'RCT-DASHBOARD',
        null,
        $accountant,
    );

    $this->actingAs($accountant)->get(route('dashboard'))->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('academic', null)
            ->where('inventory', null)
            ->where('paceAccounts.period.term', 'Term 1')
            ->where('paceAccounts.pace_cost', '15000.00')
            ->where('paceAccounts.metrics.students', 1)
            ->where('paceAccounts.metrics.total_balance', '10000.00')
            ->where('paceAccounts.metrics.funded', 0)
            ->where('paceAccounts.metrics.insufficient', 1)
            ->where('paceAccounts.metrics.zero', 0)
            ->where('paceAccounts.charts.balance_status.series', [0, 1, 0])
            ->has('paceAccounts.charts.balance_by_center.categories', 1)
            ->where('paceAccounts.charts.balance_by_center.series.0.data', [10000])
            ->has('paceAccounts.queue', 1)
            ->where('paceAccounts.queue.0.student', 'Grace Auma')
            ->where('paceAccounts.queue.0.balance', '10000.00')
            ->where('paceAccounts.queue.0.shortfall', '5000.00'));
});

test('administrator dashboard includes all operational reporting domains', function () {
    createReportFixture();
    $administrator = createStaffWithRole(RoleName::Administrator);

    $this->actingAs($administrator)->get(route('dashboard'))->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('academic.metrics')
            ->has('academic.charts')
            ->has('inventory.metrics')
            ->has('inventory.charts')
            ->has('paceAccounts.metrics')
            ->has('paceAccounts.charts')
            ->has('setup'));
});

test('dashboard charts provide stable empty data when no term is active', function () {
    $fixture = createReportFixture();
    $fixture['term']->update(['is_active' => false]);
    $accountant = createStaffWithRole(RoleName::Accountant);
    $teacher = $fixture['teacher'];

    $this->actingAs($accountant)->get(route('dashboard'))->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('paceAccounts.period', null)
            ->where('paceAccounts.charts.balance_status.series', [0, 0, 1])
            ->has('paceAccounts.charts.balance_by_center.categories', 1));

    $this->actingAs($teacher)->get(route('dashboard'))->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('academic.charts.target_status_by_subject.categories', [])
            ->where('academic.charts.target_status_by_subject.series.0.data', []));
});

test('user without reporting permissions receives no operational reporting data', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('academic', null)
            ->where('inventory', null)
            ->where('paceAccounts', null));
});
