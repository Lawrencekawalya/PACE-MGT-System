<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelPurchaseOrderRequest;
use App\Http\Requests\PurchaseOrderDecisionRequest;
use App\Models\PurchaseOrder;
use App\Services\ActivityLogger;
use App\Services\PurchaseOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class PurchaseOrderWorkflowController extends Controller
{
    public function __construct(
        private PurchaseOrderService $orders,
        private ActivityLogger $activityLogger,
    ) {}

    public function submit(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        Gate::authorize('submit', $purchaseOrder);
        $order = $this->orders->submit($purchaseOrder, $request->user());
        $this->record($request, $order, 'submitted');

        return $this->back("{$order->order_number} submitted for approval.");
    }

    public function decide(PurchaseOrderDecisionRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $approve = $request->validated('decision') === 'approve';
        $order = $this->orders->decide($purchaseOrder, $approve, $request->validated('reason'), $request->user());
        $this->record($request, $order, $approve ? 'approved' : 'rejected');

        return $this->back("{$order->order_number} {$order->status->label()}.");
    }

    public function send(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        Gate::authorize('send', $purchaseOrder);
        $order = $this->orders->markSent($purchaseOrder, $request->user());
        $this->record($request, $order, 'sent');

        return $this->back("{$order->order_number} marked as sent.");
    }

    public function cancel(CancelPurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $order = $this->orders->cancel($purchaseOrder, $request->validated('reason'), $request->user());
        $this->record($request, $order, 'cancelled');

        return $this->back("{$order->order_number} cancelled.");
    }

    private function record(Request $request, PurchaseOrder $order, string $action): void
    {
        $this->activityLogger->record(
            $request->user(),
            "purchase-order.{$action}",
            $order,
            newValues: $order->only(['order_number', 'status', 'decision_reason', 'cancellation_reason']),
        );
    }

    private function back(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }
}
