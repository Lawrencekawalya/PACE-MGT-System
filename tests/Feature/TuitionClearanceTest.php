<?php

use App\Models\ActivityLog;
use App\Models\InventoryItem;
use App\Models\PaceAccountTransaction;
use App\Models\Term;
use App\PaceAccountTransactionType;
use App\PaceAssignmentStatus;
use App\RoleName;
use App\Services\PaceAccountService;
use App\Services\PaceIssueService;
use App\Services\StockLedgerService;
use App\StockMovementType;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
});

test('Accountant sees student PACE accounts and can filter by school structure', function () {
    $fixture = createReportFixture();
    $accountant = createStaffWithRole(RoleName::Accountant);

    $this->actingAs($accountant)
        ->get(route('pace-accounts.index', [
            'learning_center_id' => $fixture['enrollment']->learning_center_id,
            'level_id' => $fixture['level']->id,
            'balance_status' => 'zero',
            'search' => 'FICA-0001',
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('pace-accounts/Index')
            ->where('summary.students', 1)
            ->where('summary.zero', 1)
            ->where('paceCost', '0.00')
            ->where('priceTerm.name', 'Term 1')
            ->where('priceTerm.academic_year', '2026')
            ->where('canSetPaceCost', true)
            ->has('enrollments.data', 1)
            ->where('enrollments.data.0.student.admission_number', 'FICA-0001')
            ->where('enrollments.data.0.balance', '0.00')
            ->where('enrollments.data.0.can_issue', false));
});

test('only an Accountant can set the uniform PACE cost', function () {
    $fixture = createReportFixture();
    $accountant = createStaffWithRole(RoleName::Accountant);
    $administrator = createStaffWithRole(RoleName::Administrator);

    $this->actingAs($accountant)
        ->put(route('pace-accounts.cost.update'), ['pace_cost' => 15000])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($fixture['term']->fresh()->pace_cost)->toBe('15000.00')
        ->and(ActivityLog::query()->where('event', 'pace-account.cost-updated')->exists())->toBeTrue();

    $this->actingAs($administrator)
        ->put(route('pace-accounts.cost.update'), ['pace_cost' => 20000])
        ->assertForbidden();
});

test('Accountant records an auditable payment that increases the carried student balance', function () {
    $fixture = createReportFixture();
    $accountant = createStaffWithRole(RoleName::Accountant);

    $this->actingAs($accountant)
        ->post(route('pace-accounts.payments.store', $fixture['student']), [
            'amount' => 60000,
            'paid_on' => now()->toDateString(),
            'reference' => 'RCT-2026-104',
            'notes' => 'PACE credit received.',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $transaction = PaceAccountTransaction::query()->sole();
    expect($transaction->type)->toBe(PaceAccountTransactionType::Payment)
        ->and($transaction->amount)->toBe('60000.00')
        ->and($transaction->balance_after)->toBe('60000.00')
        ->and($transaction->reference)->toBe('RCT-2026-104')
        ->and($transaction->recorded_by)->toBe($accountant->id)
        ->and(app(PaceAccountService::class)->balance($fixture['student']))->toBe('60000.00')
        ->and(ActivityLog::query()->where('event', 'pace-account.payment-recorded')->exists())->toBeTrue();

    expect(fn () => $transaction->update(['amount' => 1]))->toThrow(LogicException::class)
        ->and(fn () => $transaction->delete())->toThrow(LogicException::class);
});

test('Teacher and PACE Officer cannot manage PACE accounts', function (RoleName $role) {
    $fixture = createReportFixture();
    $staff = createStaffWithRole($role);

    $this->actingAs($staff)->get(route('pace-accounts.index'))->assertForbidden();
    $this->actingAs($staff)
        ->post(route('pace-accounts.payments.store', $fixture['student']), [
            'amount' => 10000,
            'paid_on' => now()->toDateString(),
        ])
        ->assertForbidden();
})->with([
    'teacher' => RoleName::Teacher,
    'PACE Officer' => RoleName::PaceOfficer,
]);

test('physical issue requires the full PACE cost and deducts it atomically', function () {
    $fixture = createReportFixture();
    $accountant = createStaffWithRole(RoleName::Accountant);
    $officer = createStaffWithRole(RoleName::PaceOfficer);
    $fixture['active']->update([
        'status' => PaceAssignmentStatus::Assigned,
        'issued_at' => null,
        'started_at' => null,
    ]);
    $fixture['term']->update(['pace_cost' => 15000]);
    $item = InventoryItem::query()->where('pace_id', $fixture['active']->pace_id)->sole();
    app(StockLedgerService::class)->postManual($item, StockMovementType::Receipt, 1, 'DEL-ACCOUNT', null, $officer);
    app(PaceAccountService::class)->recordPayment(
        $fixture['student'],
        '14999.00',
        now(),
        'RCT-SHORT',
        null,
        $accountant,
    );

    expect(fn () => app(PaceIssueService::class)->issue($fixture['active'], $officer))
        ->toThrow(ValidationException::class, 'insufficient PACE balance')
        ->and($item->onHand())->toBe(1)
        ->and($fixture['active']->fresh()->status)->toBe(PaceAssignmentStatus::Assigned)
        ->and(PaceAccountTransaction::query()->where('type', PaceAccountTransactionType::PaceIssue)->exists())->toBeFalse();

    app(PaceAccountService::class)->recordPayment(
        $fixture['student'],
        '1.00',
        now(),
        'RCT-TOPUP',
        null,
        $accountant,
    );
    $issued = app(PaceIssueService::class)->issue($fixture['active'], $officer);
    $charge = PaceAccountTransaction::query()->where('type', PaceAccountTransactionType::PaceIssue)->sole();

    expect($issued->status)->toBe(PaceAssignmentStatus::InProgress)
        ->and($item->onHand())->toBe(0)
        ->and($charge->amount)->toBe('-15000.00')
        ->and($charge->balance_after)->toBe('0.00')
        ->and(app(PaceAccountService::class)->balance($fixture['student']))->toBe('0.00');
});

test('valid issue reversal restores the exact price originally charged', function () {
    $fixture = createReportFixture();
    $accountant = createStaffWithRole(RoleName::Accountant);
    $officer = createStaffWithRole(RoleName::PaceOfficer);
    $fixture['active']->update([
        'status' => PaceAssignmentStatus::Assigned,
        'issued_at' => null,
        'started_at' => null,
    ]);
    $fixture['term']->update(['pace_cost' => 12000]);
    $item = InventoryItem::query()->where('pace_id', $fixture['active']->pace_id)->sole();
    app(StockLedgerService::class)->postManual($item, StockMovementType::Receipt, 1, 'DEL-REV', null, $officer);
    app(PaceAccountService::class)->recordPayment($fixture['student'], '20000.00', now(), 'RCT-REV', null, $accountant);
    app(PaceIssueService::class)->issue($fixture['active'], $officer);
    $issue = $item->movements()->where('type', StockMovementType::Issue)->sole();

    $nextTerm = Term::factory()->create([
        'academic_year_id' => $fixture['year']->id,
        'name' => 'Term 2',
        'sort_order' => 2,
        'pace_cost' => 15000,
    ]);
    app(PaceIssueService::class)->reverse($issue, 'Wrong student selected.', $officer);

    $reversal = PaceAccountTransaction::query()->where('type', PaceAccountTransactionType::IssueReversal)->sole();
    expect($reversal->amount)->toBe('12000.00')
        ->and($reversal->balance_after)->toBe('20000.00')
        ->and(app(PaceAccountService::class)->balance($fixture['student']))->toBe('20000.00')
        ->and($fixture['term']->fresh()->pace_cost)->toBe('12000.00')
        ->and($nextTerm->pace_cost)->toBe('15000.00');
});

test('a delayed issue uses the cost retained on its assignment term', function () {
    $fixture = createReportFixture();
    $accountant = createStaffWithRole(RoleName::Accountant);
    $officer = createStaffWithRole(RoleName::PaceOfficer);
    $fixture['active']->update([
        'status' => PaceAssignmentStatus::Assigned,
        'issued_at' => null,
        'started_at' => null,
    ]);
    $fixture['term']->update(['pace_cost' => 12000, 'is_active' => false]);
    Term::factory()->create([
        'academic_year_id' => $fixture['year']->id,
        'name' => 'Term 2',
        'sort_order' => 2,
        'pace_cost' => 15000,
        'is_active' => true,
    ]);
    $item = InventoryItem::query()->where('pace_id', $fixture['active']->pace_id)->sole();
    app(StockLedgerService::class)->postManual($item, StockMovementType::Receipt, 1, 'DEL-DELAYED', null, $officer);
    app(PaceAccountService::class)->recordPayment($fixture['student'], '12000.00', now(), 'RCT-DELAYED', null, $accountant);

    app(PaceIssueService::class)->issue($fixture['active'], $officer);

    $charge = PaceAccountTransaction::query()->where('type', PaceAccountTransactionType::PaceIssue)->sole();
    expect($charge->term_id)->toBe($fixture['term']->id)
        ->and($charge->amount)->toBe('-12000.00')
        ->and($charge->balance_after)->toBe('0.00');
});

test('PACE costs remain attached to their academic terms', function () {
    $fixture = createReportFixture();
    $accountant = createStaffWithRole(RoleName::Accountant);

    app(PaceAccountService::class)->updatePaceCost('12000.00', $accountant);
    $fixture['term']->update(['is_active' => false]);
    $nextTerm = Term::factory()->create([
        'academic_year_id' => $fixture['year']->id,
        'name' => 'Term 2',
        'sort_order' => 2,
        'is_active' => true,
        'pace_cost' => 0,
    ]);

    app(PaceAccountService::class)->updatePaceCost('15000.00', $accountant);

    expect($fixture['term']->fresh()->pace_cost)->toBe('12000.00')
        ->and($nextTerm->fresh()->pace_cost)->toBe('15000.00');
});
