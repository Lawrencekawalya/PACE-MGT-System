<?php

test('uses the configured application identity and timezone', function () {
    expect(config('app.name'))->toBe('PACE Management System')
        ->and(config('app.timezone'))->toBe('Africa/Kampala')
        ->and(date_default_timezone_get())->toBe('Africa/Kampala');
});
