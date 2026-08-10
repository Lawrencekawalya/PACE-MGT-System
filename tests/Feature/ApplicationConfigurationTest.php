<?php

test('uses the configured application identity and timezone', function () {
    expect(config('app.name'))->toBe('PACE Management System')
        ->and(config('app.timezone'))->toBe('Africa/Kampala')
        ->and(date_default_timezone_get())->toBe('Africa/Kampala');
});

test('ships the official FICA brand asset and browser icon', function () {
    $brandAsset = public_path('branding/fica-logo.jpg');
    $backgroundAsset = public_path('branding/fica-home-background.jpg');
    $backgroundVideo = public_path('branding/fica-home-background.mp4');
    $applicationView = file_get_contents(resource_path('views/app.blade.php'));

    expect($brandAsset)->toBeFile()
        ->and(filesize($brandAsset))->toBeGreaterThan(0)
        ->and($backgroundAsset)->toBeFile()
        ->and(filesize($backgroundAsset))->toBeGreaterThan(0)
        ->and($backgroundVideo)->toBeFile()
        ->and(filesize($backgroundVideo))->toBeGreaterThan(0)
        ->and($applicationView)->toContain('/branding/fica-logo.jpg');
});
