<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInventoryItemRequest;
use App\Http\Requests\UpdateInventoryItemRequest;
use App\InventoryItemType;
use App\Models\InventoryItem;
use App\Models\Pace;
use App\Services\ActivityLogger;
use App\StockMovementType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class InventoryItemController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function store(StoreInventoryItemRequest $request): RedirectResponse
    {
        $item = InventoryItem::query()->create($request->validated());
        $this->activityLogger->record($request->user(), 'inventory-item.created', $item, newValues: $item->only(['pace_id', 'item_type', 'sku', 'reorder_level', 'is_consumable', 'is_active']));
        Inertia::flash('toast', ['type' => 'success', 'message' => "Inventory item {$item->sku} created."]);

        return redirect()->route('inventory-items.show', $item);
    }

    public function show(Request $request, InventoryItem $inventoryItem): Response
    {
        Gate::authorize('view', $inventoryItem);
        $inventoryItem->load(['pace.course.subject:id,name']);
        $movements = $inventoryItem->movements()->with(['student:id,admission_number,first_name,last_name', 'paceAssignment:id', 'recordedBy:id,name', 'correctsMovement:id,type,quantity'])->paginate(25);

        return Inertia::render('inventory/Show', [
            'item' => [...$inventoryItem->toArray(), 'on_hand' => $inventoryItem->onHand()],
            'movements' => $movements,
            'movementTypes' => collect([StockMovementType::Receipt, StockMovementType::Damage, StockMovementType::Loss, StockMovementType::Adjustment])->map(fn ($type) => ['value' => $type->value, 'label' => $type->label()]),
            'scoreKeyPaces' => $inventoryItem->item_type === InventoryItemType::ScoreKey
                ? Pace::query()
                    ->where('is_active', true)
                    ->where(fn ($query) => $query
                        ->whereKey($inventoryItem->pace_id)
                        ->orWhereDoesntHave('inventoryItems', fn ($query) => $query->where('item_type', InventoryItemType::ScoreKey)))
                    ->with(['course:id,name,subject_id', 'course.subject:id,name'])
                    ->orderBy('course_id')->orderBy('sequence_order')->orderBy('number')
                    ->get(['id', 'course_id', 'number', 'title'])
                : [],
            'canAdjust' => Gate::allows('adjust-inventory'),
        ]);
    }

    public function update(UpdateInventoryItemRequest $request, InventoryItem $inventoryItem): RedirectResponse
    {
        $old = $inventoryItem->only(['sku', 'reorder_level', 'is_active']);
        $inventoryItem->update($request->validated());
        $this->activityLogger->record($request->user(), 'inventory-item.updated', $inventoryItem, $old, $inventoryItem->only(array_keys($old)));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Inventory settings updated.']);

        return back();
    }
}
