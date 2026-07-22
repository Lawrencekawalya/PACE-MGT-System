<?php

namespace App\Http\Controllers;

use App\Http\Requests\CorrectStockMovementRequest;
use App\Models\StockMovement;
use App\Services\ActivityLogger;
use App\Services\PaceIssueService;
use App\Services\StockLedgerService;
use App\StockMovementType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class StockMovementCorrectionController extends Controller
{
    public function __construct(
        private StockLedgerService $stock,
        private PaceIssueService $issues,
        private ActivityLogger $activityLogger,
    ) {}

    public function store(CorrectStockMovementRequest $request, StockMovement $stockMovement): RedirectResponse
    {
        $reason = $request->string('reason')->trim()->toString();
        $correction = $stockMovement->type === StockMovementType::Issue
            ? $this->issues->reverse($stockMovement, $reason, $request->user())
            : $this->stock->correct($stockMovement, $reason, $request->user());
        $this->activityLogger->record($request->user(), 'stock-movement.corrected', $correction, newValues: $correction->only(['inventory_item_id', 'quantity', 'balance_after', 'corrects_movement_id']), reason: $reason);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Correction movement posted. The original ledger entry was retained.']);

        return back();
    }
}
