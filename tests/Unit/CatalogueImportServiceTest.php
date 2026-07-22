<?php

use App\Services\CatalogueImportService;

test('range expansion supports ordinary alphanumeric and even-only sequences', function () {
    $service = app(CatalogueImportService::class);

    expect($service->expandRange('1001 - 1004'))->toBe(['1001', '1002', '1003', '1004'])
        ->and($service->expandRange('RR01 - RR03'))->toBe(['RR01', 'RR02', 'RR03'])
        ->and($service->expandRange('86 - 96 (ONLY EVEN NUMBERS)'))->toBe(['86', '88', '90', '92', '94', '96']);
});

test('ambiguous and reversed ranges are rejected', function (string $range) {
    expect(app(CatalogueImportService::class)->expandRange($range))->toBeNull();
})->with(['missing end' => '1001', 'reversed' => '1012 - 1001', 'different prefixes' => 'RR01 - GG12']);
