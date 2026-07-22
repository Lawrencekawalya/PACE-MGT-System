<?php

use App\Models\ActivityLog;
use App\Models\User;
use App\PermissionName;
use App\RoleName;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
    $this->administrator = createStaffWithRole(RoleName::Administrator);
});

test('administrator can list staff accounts', function () {
    createStaffWithRole(RoleName::Teacher, ['name' => 'Academic Teacher']);

    $this->actingAs($this->administrator)
        ->get(route('admin.staff.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/staff/Index')
            ->has('staff.data', 2));
});

test('administrator can create staff with roles and approved optional permission', function () {
    $this->actingAs($this->administrator)
        ->post(route('admin.staff.store'), [
            'name' => 'New Teacher',
            'email' => 'teacher@fica.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'roles' => [RoleName::Teacher->value],
            'direct_permissions' => [PermissionName::IssuePaces->value],
        ])
        ->assertRedirect();

    $teacher = User::query()->where('email', 'teacher@fica.test')->sole();
    expect($teacher->is_active)->toBeTrue()
        ->and($teacher->hasRole(RoleName::Teacher))->toBeTrue()
        ->and($teacher->hasPermission(PermissionName::IssuePaces))->toBeTrue();

    expect(ActivityLog::query()->where('event', 'staff.created')->where('subject_id', $teacher->id)->exists())
        ->toBeTrue();
});

test('administrator can deactivate another staff account', function () {
    $teacher = createStaffWithRole(RoleName::Teacher);

    $this->actingAs($this->administrator)
        ->put(route('admin.staff.update', $teacher), [
            'name' => $teacher->name,
            'email' => $teacher->email,
            'is_active' => false,
            'roles' => [RoleName::Teacher->value],
            'direct_permissions' => [],
        ])
        ->assertRedirect();

    expect($teacher->refresh()->is_active)->toBeFalse();
});

test('administrator cannot deactivate their own account', function () {
    $this->actingAs($this->administrator)
        ->from(route('admin.staff.edit', $this->administrator))
        ->put(route('admin.staff.update', $this->administrator), [
            'name' => $this->administrator->name,
            'email' => $this->administrator->email,
            'is_active' => false,
            'roles' => [RoleName::Administrator->value],
            'direct_permissions' => [],
        ])
        ->assertSessionHasErrors('is_active');

    expect($this->administrator->refresh()->is_active)->toBeTrue();
});

test('the final active administrator role cannot be removed', function () {
    $this->actingAs($this->administrator)
        ->put(route('admin.staff.update', $this->administrator), [
            'name' => $this->administrator->name,
            'email' => $this->administrator->email,
            'is_active' => true,
            'roles' => [RoleName::Teacher->value],
            'direct_permissions' => [],
        ])
        ->assertSessionHasErrors('roles');

    expect($this->administrator->hasRole(RoleName::Administrator))->toBeTrue();
});

test('administrator can reset a staff password with an audited reason', function () {
    $teacher = createStaffWithRole(RoleName::Teacher);

    $this->actingAs($this->administrator)
        ->put(route('admin.staff.password.update', $teacher), [
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
            'reason' => 'Staff member requested an account recovery.',
        ])
        ->assertRedirect();

    expect(Hash::check('new-secure-password', $teacher->refresh()->password))->toBeTrue();
    $log = ActivityLog::query()->where('event', 'staff.password-reset')->where('subject_id', $teacher->id)->sole();
    expect($log->reason)->toBe('Staff member requested an account recovery.');
});
