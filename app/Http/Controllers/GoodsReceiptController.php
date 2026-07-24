<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGoodsReceiptRequest;
use App\Models\PurchaseOrder;
use App\Services\ActivityLogger;
use App\Services\PurchaseOrderService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class GoodsReceiptController extends Controller
{
    public function __construct(
        private PurchaseOrderService $orders,
        private ActivityLogger $activityLogger,
    ) {}

    public function store(StoreGoodsReceiptRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $receipt = $this->orders->receive($purchaseOrder, $request->validated(), $request->user());
        $this->activityLogger->record(
            $request->user(),
            'goods-receipt.created',
            $receipt,
            newValues: $receipt->only(['receipt_number', 'purchase_order_id', 'delivery_reference', 'received_at']),
        );
        Inertia::flash('toast', ['type' => 'success', 'message' => "{$receipt->receipt_number} posted to the stock ledger."]);

        return back();
    }
}
