<?php

use App\Models\Role;
use App\RoleName;
use Database\Seeders\AccessControlSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
});

test('guests cannot access the system guide', function () {
    $this->get(route('documentation'))->assertRedirect(route('login'));
});

test('teacher sees only the teacher guide', function () {
    $teacher = createStaffWithRole(RoleName::Teacher);

    $this->actingAs($teacher)
        ->get(route('documentation'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('documentation/Index')
            ->has('guides', 1)
            ->where('guides.0.role', RoleName::Teacher->value));
});

test('PACE Officer sees only the PACE Officer guide', function () {
    $officer = createStaffWithRole(RoleName::PaceOfficer);

    $this->actingAs($officer)
        ->get(route('documentation'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('documentation/Index')
            ->has('guides', 1)
            ->where('guides.0.role', RoleName::PaceOfficer->value));
});

test('Accountant sees only the Accountant guide', function () {
    $accountant = createStaffWithRole(RoleName::Accountant);

    $this->actingAs($accountant)
        ->get(route('documentation'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('documentation/Index')
            ->has('guides', 1)
            ->where('guides.0.role', RoleName::Accountant->value));
});

test('Management sees only the Management guide', function () {
    $management = createStaffWithRole(RoleName::Management);

    $this->actingAs($management)
        ->get(route('documentation'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('documentation/Index')
            ->has('guides', 1)
            ->where('guides.0.role', RoleName::Management->value));
});

test('staff member with two operational roles sees both guides', function () {
    $staff = createStaffWithRole(RoleName::Teacher);
    $staff->roles()->attach(
        Role::query()->where('name', RoleName::PaceOfficer->value)->value('id'),
    );

    $this->actingAs($staff)
        ->get(route('documentation'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('guides', 2)
            ->where('guides.0.role', RoleName::Teacher->value)
            ->where('guides.1.role', RoleName::PaceOfficer->value));
});

test('administrator sees every role guide for staff support', function () {
    $administrator = createStaffWithRole(RoleName::Administrator);

    $this->actingAs($administrator)
        ->get(route('documentation'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('guides', 5)
            ->where('guides.0.role', RoleName::Administrator->value)
            ->where('guides.1.role', RoleName::Teacher->value)
            ->where('guides.2.role', RoleName::PaceOfficer->value)
            ->where('guides.3.role', RoleName::Accountant->value)
            ->where('guides.4.role', RoleName::Management->value)
            ->has('guides.0.workflows')
            ->has('guides.1.boundaries')
            ->has('guides.2.workflows')
            ->has('guides.3.boundaries'));
});
