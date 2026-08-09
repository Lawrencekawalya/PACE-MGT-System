<?php

use App\InventoryItemType;
use App\Models\ActivityLog;
use App\Models\InventoryItem;
use App\Models\LearningCenter;
use App\Models\ScoreKeyRequest;
use App\Models\StockMovement;
use App\RoleName;
use App\ScoreKeyRequestStatus;
use App\ScoreKeyRequestType;
use App\Services\ScoreKeyRequestService;
use App\Services\StockLedgerService;
use App\StockMovementType;
use Database\Seeders\AccessControlSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
});

/** @return array<string, mixed> */
function scoreKeyFixture(int $stock = 3): array
{
    $fixture = createReportFixture();
    $teacher = $fixture['teacher'];
    $center = $fixture['enrollment']->learningCenter;
    $item = InventoryItem::query()
        ->where('pace_id', $fixture['paces'][1]->id)
        ->where('item_type', InventoryItemType::ScoreKey)
        ->sole();
    $officer = createStaffWithRole(RoleName::PaceOfficer);
    if ($stock > 0) {
        app(StockLedgerService::class)->postManual(
            $item,
            StockMovementType::Receipt,
            $stock,
            'DEL-SCORE-KEYS',
            null,
            $officer,
        );
    }

    return compact('fixture', 'teacher', 'center', 'item', 'officer');
}

test('Teacher requests a matching Score Key for an assigned learning center', function () {
    $data = scoreKeyFixture();
    $unrelatedCenter = LearningCenter::factory()->create();

    $this->actingAs($data['teacher'])
        ->get(route('score-key-requests.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('score-key-requests/Index')
            ->where('canRequest', true)
            ->where('canIssue', false)
            ->has('learningCenters', 1)
            ->where('assignedLearningCenter.id', $data['center']->id)
            ->where('learningCenterAssignmentIssue', null)
            ->has('scoreKeys', 3));

    $this->actingAs($data['teacher'])
        ->post(route('score-key-requests.store'), [
            'learning_center_id' => $unrelatedCenter->id,
            'inventory_item_id' => $data['item']->id,
            'request_type' => ScoreKeyRequestType::NewIssue->value,
            'quantity_requested' => 1,
            'notes' => 'Needed for Mathematics scoring.',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $request = ScoreKeyRequest::query()->sole();
    expect($request->teacher_id)->toBe($data['teacher']->id)
        ->and($request->learning_center_id)->toBe($data['center']->id)
        ->and($request->status)->toBe(ScoreKeyRequestStatus::Pending)
        ->and(ActivityLog::query()->where('event', 'score-key-request.created')->exists())->toBeTrue();
});

test('Teacher cannot submit duplicates or request with an ambiguous center assignment', function () {
    $data = scoreKeyFixture();
    $otherCenter = LearningCenter::factory()->create();
    $payload = [
        'inventory_item_id' => $data['item']->id,
        'request_type' => ScoreKeyRequestType::NewIssue->value,
        'quantity_requested' => 1,
    ];

    $this->actingAs($data['teacher'])
        ->post(route('score-key-requests.store'), $payload)
        ->assertSessionHasNoErrors();
    $this->actingAs($data['teacher'])
        ->post(route('score-key-requests.store'), $payload)
        ->assertSessionHasErrors('inventory_item_id');

    $data['teacher']->learningCenters()->attach($otherCenter);
    $this->actingAs($data['teacher'])
        ->post(route('score-key-requests.store'), $payload)
        ->assertSessionHasErrors('learning_center_id');
});

test('Teacher without an active learning center cannot request a Score Key', function () {
    $data = scoreKeyFixture();
    $data['teacher']->learningCenters()->detach();

    $this->actingAs($data['teacher'])
        ->post(route('score-key-requests.store'), [
            'inventory_item_id' => $data['item']->id,
            'request_type' => ScoreKeyRequestType::NewIssue->value,
            'quantity_requested' => 1,
        ])
        ->assertSessionHasErrors('learning_center_id');
});

test('PACE Officer partially and then fully issues permanent Score Key stock', function () {
    $data = scoreKeyFixture();
    $request = ScoreKeyRequest::factory()->create([
        'teacher_id' => $data['teacher']->id,
        'learning_center_id' => $data['center']->id,
        'inventory_item_id' => $data['item']->id,
        'quantity_requested' => 2,
    ]);

    $this->actingAs($data['officer'])
        ->post(route('score-key-requests.issues.store', $request), ['quantity' => 1])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($request->fresh()->status)->toBe(ScoreKeyRequestStatus::PartiallyIssued)
        ->and($request->issuedQuantity())->toBe(1)
        ->and($data['item']->onHand())->toBe(2);

    $this->actingAs($data['officer'])
        ->post(route('score-key-requests.issues.store', $request), ['quantity' => 1, 'notes' => 'Final copy handed over.'])
        ->assertSessionHasNoErrors();

    $movements = StockMovement::query()->where('score_key_request_id', $request->id)->where('type', StockMovementType::Issue)->get();
    expect($request->fresh()->status)->toBe(ScoreKeyRequestStatus::Issued)
        ->and($request->issuedQuantity())->toBe(2)
        ->and($data['item']->onHand())->toBe(1)
        ->and($movements)->toHaveCount(2)
        ->and($movements->every(fn (StockMovement $movement): bool => $movement->issued_to_user_id === $data['teacher']->id))->toBeTrue();

    $this->actingAs($data['teacher'])->get(route('score-key-requests.index'))
        ->assertInertia(fn ($page) => $page
            ->has('myScoreKeys', 1)
            ->where('myScoreKeys.0.quantity', 2)
            ->where('requests.data.0.quantity_issued', 2));
});

test('Score Key issue is atomic when stock is insufficient', function () {
    $data = scoreKeyFixture(0);
    $request = ScoreKeyRequest::factory()->create([
        'teacher_id' => $data['teacher']->id,
        'learning_center_id' => $data['center']->id,
        'inventory_item_id' => $data['item']->id,
    ]);

    $this->actingAs($data['officer'])
        ->post(route('score-key-requests.issues.store', $request), ['quantity' => 1])
        ->assertSessionHasErrors('quantity');

    expect($request->fresh()->status)->toBe(ScoreKeyRequestStatus::Pending)
        ->and($request->issueMovements()->count())->toBe(0)
        ->and($data['item']->onHand())->toBe(0);
});

test('Teacher cancellation and PACE Officer rejection preserve decision history', function () {
    $data = scoreKeyFixture();
    $cancelled = ScoreKeyRequest::factory()->create([
        'teacher_id' => $data['teacher']->id,
        'learning_center_id' => $data['center']->id,
        'inventory_item_id' => $data['item']->id,
    ]);

    $this->actingAs($data['teacher'])
        ->post(route('score-key-requests.cancel', $cancelled))
        ->assertSessionHasNoErrors();
    expect($cancelled->fresh()->status)->toBe(ScoreKeyRequestStatus::Cancelled)
        ->and($cancelled->fresh()->cancelled_at)->not->toBeNull();

    $rejected = ScoreKeyRequest::factory()->create([
        'teacher_id' => $data['teacher']->id,
        'learning_center_id' => $data['center']->id,
        'inventory_item_id' => $data['item']->id,
    ]);
    $this->actingAs($data['officer'])
        ->post(route('score-key-requests.reject', $rejected), ['reason' => 'Matching key is not currently stocked.'])
        ->assertSessionHasNoErrors();

    expect($rejected->fresh()->status)->toBe(ScoreKeyRequestStatus::Rejected)
        ->and($rejected->fresh()->rejection_reason)->toBe('Matching key is not currently stocked.')
        ->and($rejected->fresh()->rejected_by)->toBe($data['officer']->id);
});

test('correcting a mistaken Score Key issue restores stock and request balance', function () {
    $data = scoreKeyFixture(1);
    $request = ScoreKeyRequest::factory()->create([
        'teacher_id' => $data['teacher']->id,
        'learning_center_id' => $data['center']->id,
        'inventory_item_id' => $data['item']->id,
    ]);
    app(ScoreKeyRequestService::class)->issue($request, 1, null, $data['officer']);
    $movement = $request->issueMovements()->sole();

    app(StockLedgerService::class)->correct($movement, 'Handover entered against the wrong Teacher.', $data['officer']);

    expect($data['item']->onHand())->toBe(1)
        ->and($request->fresh()->status)->toBe(ScoreKeyRequestStatus::Pending)
        ->and($request->issuedQuantity())->toBe(0);

    $this->actingAs($data['teacher'])
        ->get(route('score-key-requests.index'))
        ->assertInertia(fn ($page) => $page->has('myScoreKeys', 0));
});

test('Teacher requests a reasoned replacement after an effective prior issue', function () {
    $data = scoreKeyFixture(2);
    $original = ScoreKeyRequest::factory()->create([
        'teacher_id' => $data['teacher']->id,
        'learning_center_id' => $data['center']->id,
        'inventory_item_id' => $data['item']->id,
    ]);
    app(ScoreKeyRequestService::class)->issue($original, 1, null, $data['officer']);

    $this->actingAs($data['teacher'])
        ->post(route('score-key-requests.store'), [
            'inventory_item_id' => $data['item']->id,
            'request_type' => ScoreKeyRequestType::Replacement->value,
            'quantity_requested' => 1,
            'request_reason' => 'The original key was damaged by water.',
        ])
        ->assertSessionHasNoErrors();

    expect(ScoreKeyRequest::query()->count())->toBe(2)
        ->and(ScoreKeyRequest::query()->latest('id')->firstOrFail()->request_type)->toBe(ScoreKeyRequestType::Replacement);
});

test('Accountant cannot access Score Key requests', function () {
    $accountant = createStaffWithRole(RoleName::Accountant);

    $this->actingAs($accountant)->get(route('score-key-requests.index'))->assertForbidden();
});

test('Administrator oversees requests without seeing the Teacher request form', function () {
    $administrator = createStaffWithRole(RoleName::Administrator);

    $this->actingAs($administrator)
        ->get(route('score-key-requests.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('canRequest', false)
            ->where('canIssue', true));
});
