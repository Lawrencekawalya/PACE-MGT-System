<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseOrderLineRequest;
use App\Http\Requests\UpdatePurchaseOrderLineRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Services\PurchaseOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class PurchaseOrderLineController extends Controller
{
    public function __construct(private PurchaseOrderService $orders) {}

    public function store(StorePurchaseOrderLineRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->orders->addLine($purchaseOrder, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Order item added.']);

        return back();
    }

    public function update(UpdatePurchaseOrderLineRequest $request, PurchaseOrderLine $purchaseOrderLine): RedirectResponse
    {
        $this->orders->updateLine($purchaseOrderLine, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Order item updated.']);

        return back();
    }

    public function destroy(Request $request, PurchaseOrderLine $purchaseOrderLine): RedirectResponse
    {
        Gate::authorize('update', $purchaseOrderLine->purchaseOrder);
        $this->orders->removeLine($purchaseOrderLine);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Order item removed.']);

        return back();
    }
}
