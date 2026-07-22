<?php

use App\Models\Role;
use App\Models\User;
use App\RoleName;
use Database\Seeders\AccessControlSeeder;

beforeEach(fn () => $this->seed(AccessControlSeeder::class));

test('an administrator can be provisioned from the command line', function () {
    $this->artisan('admin:create', [
        'email' => 'owner@fica.test',
        '--name' => 'FICA Owner',
        '--password' => 'Secure-Pass9!',
    ])->expectsOutput('Administrator created: owner@fica.test')->assertSuccessful();

    $administrator = User::query()->where('email', 'owner@fica.test')->firstOrFail();

    expect($administrator->is_active)->toBeTrue()
        ->and($administrator->email_verified_at)->not->toBeNull()
        ->and($administrator->roles()->where('name', RoleName::Administrator->value)->exists())->toBeTrue();
});

test('administrator provisioning rejects weak passwords and duplicate emails', function () {
    User::factory()->create(['email' => 'existing@fica.test']);

    $this->artisan('admin:create', [
        'email' => 'existing@fica.test',
        '--name' => 'Existing User',
        '--password' => 'weak',
    ])->assertFailed();

    expect(Role::query()->where('name', RoleName::Administrator->value)->exists())->toBeTrue();
});
