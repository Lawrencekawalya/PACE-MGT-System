<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockMovementRequest;
use App\Models\InventoryItem;
use App\Services\ActivityLogger;
use App\Services\StockLedgerService;
use App\StockMovementType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class StockMovementController extends Controller
{
    public function __construct(private StockLedgerService $stock, private ActivityLogger $activityLogger) {}

    public function store(StoreStockMovementRequest $request, InventoryItem $inventoryItem): RedirectResponse
    {
        $movement = $this->stock->postManual(
            $inventoryItem, StockMovementType::from($request->string('type')->toString()),
            $request->integer('quantity'), $request->string('reference')->trim()->toString() ?: null,
            $request->string('reason')->trim()->toString() ?: null, $request->user(),
        );
        $this->activityLogger->record($request->user(), 'stock-movement.posted', $movement, newValues: $movement->only(['inventory_item_id', 'type', 'quantity', 'balance_after', 'reference']), reason: $movement->reason);
        Inertia::flash('toast', ['type' => 'success', 'message' => "Stock movement posted. On hand: {$movement->balance_after}."]);

        return back();
    }
}
