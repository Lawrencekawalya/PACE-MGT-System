<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseOrderRequest;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\PurchaseOrderStatus;
use App\Services\ActivityLogger;
use App\Services\PurchaseOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private PurchaseOrderService $orders,
        private ActivityLogger $activityLogger,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', PurchaseOrder::class);
        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'status' => $request->string('status')->toString(),
        ];

        return Inertia::render('purchase-orders/Index', [
            'orders' => PurchaseOrder::query()
                ->with(['supplier:id,name,code', 'createdBy:id,name'])
                ->withCount('lines')
                ->when($filters['search'], fn ($query, $search) => $query->where(fn ($query) => $query
                    ->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))))
                ->when(in_array($filters['status'], array_column(PurchaseOrderStatus::cases(), 'value'), true), fn ($query) => $query->where('status', $filters['status']))
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
            'filters' => $filters,
            'statuses' => collect(PurchaseOrderStatus::cases())->map(fn ($status) => ['value' => $status->value, 'label' => $status->label()]),
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'canCreate' => Gate::allows('create', PurchaseOrder::class),
        ]);
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        $order = $this->orders->create($request->validated(), $request->user());
        $this->activityLogger->record($request->user(), 'purchase-order.created', $order, newValues: $order->only(['order_number', 'supplier_id', 'source', 'status', 'expected_on']));
        Inertia::flash('toast', ['type' => 'success', 'message' => "{$order->order_number} created as a draft."]);

        return redirect()->route('purchase-orders.show', $order);
    }

    public function show(PurchaseOrder $purchaseOrder): Response
    {
        Gate::authorize('view', $purchaseOrder);
        $purchaseOrder->load([
            'supplier',
            'createdBy:id,name',
            'submittedBy:id,name',
            'decidedBy:id,name',
            'sentBy:id,name',
            'cancelledBy:id,name',
            'lines' => fn ($query) => $query->with([
                'inventoryItem.pace.course.subject:id,name',
            ])->withSum('effectiveGoodsReceiptLines as received_quantity', 'quantity_received'),
            'goodsReceipts' => fn ($query) => $query->with(['receivedBy:id,name', 'lines.purchaseOrderLine.inventoryItem:id,sku'])->latest('received_at'),
        ]);

        return Inertia::render('purchase-orders/Show', [
            'order' => $purchaseOrder,
            'inventoryItems' => InventoryItem::query()
                ->where('is_active', true)
                ->whereNotIn('id', $purchaseOrder->lines->pluck('inventory_item_id'))
                ->with(['pace:id,course_id,number,title', 'pace.course:id,name,subject_id', 'pace.course.subject:id,name'])
                ->orderBy('sku')
                ->get(),
            'can' => [
                'update' => Gate::allows('update', $purchaseOrder),
                'submit' => Gate::allows('submit', $purchaseOrder),
                'decide' => Gate::allows('decide', $purchaseOrder),
                'send' => Gate::allows('send', $purchaseOrder),
                'receive' => Gate::allows('receive', $purchaseOrder),
                'cancel' => Gate::allows('cancel', $purchaseOrder),
            ],
        ]);
    }
}
