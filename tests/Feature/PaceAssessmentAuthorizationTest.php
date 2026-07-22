<?php

use App\AssessmentType;
use App\Models\PaceAttempt;
use App\RoleName;
use App\Services\PaceAssessmentService;
use Database\Seeders\AccessControlSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
});

test('teacher records results while storekeeper cannot access assessments', function () {
    $fixture = assessmentFixture();
    $storekeeper = createStaffWithRole(RoleName::Storekeeper);
    $data = ['assessment_type' => 'self_test', 'score' => 80];

    $this->actingAs($storekeeper)->get(route('assessments.index'))->assertForbidden();
    $this->actingAs($storekeeper)->post(route('pace-assignments.attempts.store', $fixture['assignment']), $data)->assertForbidden();
    $this->actingAs($fixture['teacher'])->post(route('pace-assignments.attempts.store', $fixture['assignment']), $data)->assertRedirect();
});

test('assessment endpoint rejects scores outside zero to one hundred', function () {
    $fixture = assessmentFixture();

    $this->actingAs($fixture['teacher'])->post(route('pace-assignments.attempts.store', $fixture['assignment']), [
        'assessment_type' => 'self_test', 'score' => 100.01,
    ])->assertSessionHasErrors('score');

    expect(PaceAttempt::query()->count())->toBe(0);
});

test('only administrator can append a result correction', function () {
    $fixture = assessmentFixture();
    $attempt = app(PaceAssessmentService::class)->finalize($fixture['assignment'], AssessmentType::SelfTest, 70, null, $fixture['teacher']);
    $data = ['score' => 90, 'reason' => 'Verified against the signed score sheet.'];

    $this->actingAs($fixture['teacher'])->post(route('pace-attempts.corrections.store', $attempt), $data)->assertForbidden();
    $administrator = createStaffWithRole(RoleName::Administrator);
    $this->actingAs($administrator)->post(route('pace-attempts.corrections.store', $attempt), $data)->assertRedirect();

    expect(PaceAttempt::query()->find($attempt->id)->corrections()->count())->toBe(1);
});
