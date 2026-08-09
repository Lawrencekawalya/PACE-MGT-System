<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\User;
use App\PermissionName;
use App\PurchaseOrderStatus;
use App\RoleName;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderQueueController extends Controller
{
    public function submitted(Request $request): Response
    {
        Gate::authorize('viewSubmitted', PurchaseOrder::class);

        return $this->render($request, PurchaseOrderStatus::Submitted, 'submitted');
    }

    public function approved(Request $request): Response
    {
        Gate::authorize('viewApproved', PurchaseOrder::class);

        return $this->render($request, PurchaseOrderStatus::Approved, 'approved');
    }

    private function render(Request $request, PurchaseOrderStatus $status, string $queue): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $search = $request->string('search')->trim()->toString();

        $orders = PurchaseOrder::query()
            ->where('status', $status)
            ->with([
                'supplier:id,name,code',
                'submittedBy:id,name',
                'decidedBy:id,name',
            ])
            ->withCount('lines')
            ->withSum('lines as units_count', 'quantity_ordered')
            ->when($search, fn ($query, $search) => $query->where(fn ($query) => $query
                ->where('order_number', 'like', "%{$search}%")
                ->orWhereHas('supplier', fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%"))))
            ->latest($status === PurchaseOrderStatus::Submitted ? 'submitted_at' : 'decided_at')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (PurchaseOrder $order): array => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'source' => $order->source->value,
                'status' => $order->status->value,
                'expected_on' => $order->getRawOriginal('expected_on'),
                'notes' => $order->notes,
                'lines_count' => (int) $order->getAttribute('lines_count'),
                'units_count' => (int) $order->getAttribute('units_count'),
                'submitted_at' => $order->submitted_at?->toISOString(),
                'decided_at' => $order->decided_at?->toISOString(),
                'supplier' => $order->supplier,
                'submitted_by' => $order->submittedBy,
                'decided_by' => $order->decidedBy,
                'can_decide' => $status === PurchaseOrderStatus::Submitted
                    && $user->hasPermission(PermissionName::ApprovePurchaseOrders),
                'can_send' => $status === PurchaseOrderStatus::Approved
                    && $user->hasRole(RoleName::PaceOfficer)
                    && $user->hasPermission(PermissionName::ManagePurchaseOrders),
            ]);

        return Inertia::render('purchase-orders/Queue', [
            'queue' => $queue,
            'orders' => $orders,
            'filters' => ['search' => $search],
        ]);
    }
}
