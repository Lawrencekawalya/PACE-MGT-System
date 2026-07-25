<?php

use App\Models\Pace;
use App\Models\PaceAssignment;
use App\Models\User;
use App\PaceAssignmentStatus;
use App\RoleName;
use App\Services\TuitionClearanceService;
use App\TuitionClearanceStatus;
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
            ->where('clearance', null));
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
            ->where('clearance', null));
});

test('PACE Officer dashboard charts physical issues in weekly buckets', function () {
    $this->travelTo(now()->setDate(2026, 7, 25)->startOfDay());
    createIssuingReportFixture();
    $officer = createStaffWithRole(RoleName::PaceOfficer);

    $this->actingAs($officer)->get(route('dashboard'))->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('inventory.charts.issuance_trend.series.0.data', [0, 0, 0, 0, 0, 0, 1, 0]));
});

test('Accountant dashboard shows active-term clearance workload and restrictions', function () {
    $fixture = createReportFixture();
    $accountant = createStaffWithRole(RoleName::Accountant);
    $fixture['active']->update([
        'status' => PaceAssignmentStatus::Passed,
        'completed_at' => now(),
    ]);
    PaceAssignment::factory()->create([
        'student_course_id' => $fixture['studentCourse']->id,
        'pace_id' => $fixture['paces'][2]->id,
        'academic_year_id' => $fixture['year']->id,
        'term_id' => $fixture['term']->id,
        'status' => PaceAssignmentStatus::Passed,
        'completed_at' => now(),
    ]);
    $fourthPace = Pace::factory()->create([
        'course_id' => $fixture['course']->id,
        'number' => '1004',
        'sequence_order' => 4,
    ]);
    PaceAssignment::factory()->create([
        'student_course_id' => $fixture['studentCourse']->id,
        'pace_id' => $fourthPace->id,
        'academic_year_id' => $fixture['year']->id,
        'term_id' => $fixture['term']->id,
        'status' => PaceAssignmentStatus::Passed,
        'completed_at' => now(),
    ]);
    app(TuitionClearanceService::class)->record(
        $fixture['enrollment'],
        $fixture['term'],
        TuitionClearanceStatus::PartiallyPaid,
        'RCT-DASHBOARD',
        null,
        $accountant,
    );

    $this->actingAs($accountant)->get(route('dashboard'))->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('academic', null)
            ->where('inventory', null)
            ->where('clearance.period.term', 'Term 1')
            ->where('clearance.target', 4)
            ->where('clearance.metrics.students', 1)
            ->where('clearance.metrics.fully_paid', 0)
            ->where('clearance.metrics.partially_paid', 1)
            ->where('clearance.metrics.unconfirmed', 0)
            ->where('clearance.metrics.restricted', 1)
            ->where('clearance.metrics.approaching_or_at_target', 1)
            ->where('clearance.charts.status_distribution.series', [0, 1, 0])
            ->has('clearance.charts.target_pressure.categories', 1)
            ->where('clearance.charts.target_pressure.series.0.data', [0])
            ->where('clearance.charts.target_pressure.series.1.data', [1])
            ->where('clearance.charts.target_pressure.series.2.data', [0])
            ->has('clearance.queue', 1)
            ->where('clearance.queue.0.student', 'Grace Auma')
            ->where('clearance.queue.0.completed', 4)
            ->where('clearance.queue.0.restricted', true));
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
            ->has('clearance.metrics')
            ->has('clearance.charts')
            ->has('setup'));
});

test('dashboard charts provide stable empty data when no term is active', function () {
    $fixture = createReportFixture();
    $fixture['term']->update(['is_active' => false]);
    $accountant = createStaffWithRole(RoleName::Accountant);
    $teacher = $fixture['teacher'];

    $this->actingAs($accountant)->get(route('dashboard'))->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('clearance.period', null)
            ->where('clearance.charts.status_distribution.series', [0, 0, 0])
            ->where('clearance.charts.target_pressure.categories', []));

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
            ->where('clearance', null));
});
