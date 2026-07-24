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
            ->has('guides', 3)
            ->where('guides.0.role', RoleName::Administrator->value)
            ->where('guides.1.role', RoleName::Teacher->value)
            ->where('guides.2.role', RoleName::PaceOfficer->value)
            ->has('guides.0.workflows')
            ->has('guides.1.boundaries')
            ->has('guides.2.workflows'));
});
