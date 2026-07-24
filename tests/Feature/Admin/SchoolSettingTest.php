<?php

use App\Models\ActivityLog;
use App\Models\SchoolSetting;
use App\RoleName;
use Database\Seeders\AccessControlSeeder;
use Database\Seeders\SchoolSettingSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed([AccessControlSeeder::class, SchoolSettingSeeder::class]);
});

test('administrator can view school settings with approved defaults', function () {
    $administrator = createStaffWithRole(RoleName::Administrator);

    $this->actingAs($administrator)
        ->get(route('admin.school-settings.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/school-settings/Edit')
            ->where('settings.official_name', 'Friends International Christian Academy')
            ->where('settings.short_name', 'FICA')
            ->where('settings.timezone', 'Africa/Kampala')
            ->where('settings.self_test_pass_mark', '80.00')
            ->where('settings.pace_test_pass_mark', '80.00')
            ->where('settings.self_test_retry_limit', 2)
            ->where('settings.term_pace_target', 4));
});

test('administrator can update identity and assessment defaults with an audit record', function () {
    $administrator = createStaffWithRole(RoleName::Administrator);

    $this->actingAs($administrator)
        ->post(route('admin.school-settings.update'), [
            'official_name' => 'Friends International Christian Academy',
            'short_name' => 'FICA',
            'slogan' => '#1 ACE Mission School in Uganda',
            'country_code' => 'UG',
            'timezone' => 'Africa/Kampala',
            'date_format' => 'DD/MM/YYYY',
            'time_format' => '12-hour',
            'self_test_pass_mark' => 85,
            'pace_test_pass_mark' => 82,
            'self_test_retry_limit' => 3,
            'term_pace_target' => 5,
        ])
        ->assertRedirect();

    $settings = SchoolSetting::current();
    expect($settings->self_test_pass_mark)->toBe('85.00')
        ->and($settings->pace_test_pass_mark)->toBe('82.00')
        ->and($settings->self_test_retry_limit)->toBe(3)
        ->and($settings->term_pace_target)->toBe(5);

    expect(ActivityLog::query()->where('event', 'school-settings.updated')->exists())->toBeTrue();
});

test('school assessment settings reject invalid values', function () {
    $administrator = createStaffWithRole(RoleName::Administrator);

    $this->actingAs($administrator)
        ->from(route('admin.school-settings.edit'))
        ->post(route('admin.school-settings.update'), [
            'official_name' => 'FICA',
            'short_name' => 'FICA',
            'country_code' => 'UG',
            'timezone' => 'Africa/Kampala',
            'date_format' => 'DD/MM/YYYY',
            'time_format' => '12-hour',
            'self_test_pass_mark' => 120,
            'pace_test_pass_mark' => 80,
            'self_test_retry_limit' => 0,
            'term_pace_target' => 0,
        ])
        ->assertRedirect(route('admin.school-settings.edit'))
        ->assertSessionHasErrors(['self_test_pass_mark', 'self_test_retry_limit', 'term_pace_target']);
});
