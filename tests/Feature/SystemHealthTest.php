<?php

use App\Jobs\QueueHeartbeat;
use App\RoleName;
use App\Services\SystemHealthService;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
    Storage::fake('local');
    config(['cache.default' => 'array', 'operations.backup_disk' => 'local']);
});

test('readiness endpoint verifies core infrastructure without authentication', function () {
    $this->getJson(route('ready'))
        ->assertSuccessful()
        ->assertJsonPath('status', 'ready')
        ->assertJsonStructure(['status', 'checked_at']);
});

test('administrator can view infrastructure and release checks', function () {
    $administrator = createStaffWithRole(RoleName::Administrator);

    $this->actingAs($administrator)->get(route('admin.system-status'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/system-status/Index')
            ->has('infrastructure.checks', 6)
            ->has('releaseChecks', 5)
            ->has('metrics'));
});

test('non-administrators cannot view system status', function (RoleName $role) {
    $this->actingAs(createStaffWithRole($role))
        ->get(route('admin.system-status'))
        ->assertForbidden();
})->with([RoleName::Teacher, RoleName::Storekeeper]);

test('scheduler heartbeat is recorded and reported as current', function () {
    $this->artisan('system:heartbeat')->assertSuccessful();

    expect(Cache::get('system:scheduler:last-run'))->not->toBeNull();
    $this->artisan('system:check')->assertSuccessful();
});

test('queue heartbeat distinguishes a running worker from an available queue table', function () {
    $before = collect(app(SystemHealthService::class)->infrastructure()['checks'])->firstWhere('key', 'queue');
    expect($before['status'])->toBe('warning');

    (new QueueHeartbeat)->handle();

    $after = collect(app(SystemHealthService::class)->infrastructure()['checks'])->firstWhere('key', 'queue');
    expect($after['status'])->toBe('passed')
        ->and($after['detail'])->toContain('Worker heartbeat is current');
});

test('web responses include baseline security headers', function () {
    $this->get(route('ready'))
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
});
