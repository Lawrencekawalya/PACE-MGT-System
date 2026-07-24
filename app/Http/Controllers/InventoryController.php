<?php

namespace App\Http\Controllers;

use App\InventoryItemType;
use App\Models\Course;
use App\Models\InventoryItem;
use App\Models\Pace;
use App\Models\StockMovement;
use App\StockMovementType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', InventoryItem::class);
        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'item_type' => $request->string('item_type')->toString(),
            'stock' => $request->string('stock')->toString(),
            'active' => $request->string('active')->toString(),
        ];
        $balanceSql = '(SELECT COALESCE(SUM(quantity), 0) FROM stock_movements WHERE stock_movements.inventory_item_id = inventory_items.id)';
        $items = InventoryItem::query()
            ->with(['pace:id,course_id,number,title', 'pace.course:id,name,subject_id', 'pace.course.subject:id,name'])
            ->withSum('movements as on_hand', 'quantity')
            ->withSum(['movements as issued_quantity' => fn ($query) => $query->where('type', StockMovementType::Issue)], 'quantity')
            ->when($filters['search'], fn ($query, $search) => $query->where(fn ($query) => $query
                ->where('sku', 'like', "%{$search}%")
                ->orWhereHas('pace', fn ($query) => $query->where('number', 'like', "%{$search}%")->orWhere('title', 'like', "%{$search}%"))
                ->orWhereHas('pace.course', fn ($query) => $query->where('name', 'like', "%{$search}%"))))
            ->when(in_array($filters['item_type'], array_column(InventoryItemType::cases(), 'value'), true), fn ($query) => $query->where('item_type', $filters['item_type']))
            ->when($filters['active'] === 'active', fn ($query) => $query->where('is_active', true))
            ->when($filters['active'] === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($filters['stock'] === 'out', fn ($query) => $query->whereRaw("{$balanceSql} = 0"))
            ->when($filters['stock'] === 'low', fn ($query) => $query->whereRaw("{$balanceSql} <= inventory_items.reorder_level"))
            ->when($filters['stock'] === 'available', fn ($query) => $query->whereRaw("{$balanceSql} > 0"))
            ->orderBy('sku')->paginate(25)->withQueryString();

        return Inertia::render('inventory/Index', [
            'items' => $items, 'filters' => $filters,
            'itemTypes' => collect(InventoryItemType::cases())->map(fn ($type) => ['value' => $type->value, 'label' => $type->label()]),
            'courses' => Course::query()
                ->where('is_active', true)
                ->whereHas('paces.inventoryItems')
                ->with('subject:id,name')
                ->orderBy('name')
                ->get(['id', 'subject_id', 'name', 'code']),
            'scoreKeyPaces' => Pace::query()
                ->where('is_active', true)
                ->whereDoesntHave('inventoryItems', fn ($query) => $query->where('item_type', InventoryItemType::ScoreKey))
                ->with(['course:id,name,subject_id', 'course.subject:id,name'])
                ->orderBy('course_id')->orderBy('sequence_order')->orderBy('number')
                ->get(['id', 'course_id', 'number', 'title']),
            'summary' => [
                'items' => InventoryItem::query()->where('is_active', true)->count(),
                'on_hand' => (int) StockMovement::query()->sum('quantity'),
                'out_of_stock' => InventoryItem::query()->where('is_active', true)->whereRaw("{$balanceSql} = 0")->count(),
                'low_stock' => InventoryItem::query()->where('is_active', true)->whereRaw("{$balanceSql} <= inventory_items.reorder_level")->count(),
            ],
            'canAdjust' => Gate::allows('adjust-inventory'),
        ]);
    }

    public function ledger(Request $request): Response
    {
        Gate::authorize('viewAny', StockMovement::class);
        $filters = [
            'search' => $request->string('search')->trim()->toString(), 'type' => $request->string('type')->toString(),
            'inventory_item_id' => $request->integer('inventory_item_id') ?: null,
            'date_from' => $request->date('date_from')?->toDateString(), 'date_to' => $request->date('date_to')?->toDateString(),
        ];
        $movements = StockMovement::query()->with([
            'inventoryItem.pace:id,number,title', 'student:id,admission_number,first_name,last_name',
            'paceAssignment:id', 'recordedBy:id,name', 'correctsMovement:id,type,quantity',
        ])->when($filters['search'], fn ($query, $search) => $query->where(fn ($query) => $query
            ->where('reference', 'like', "%{$search}%")
            ->orWhereHas('inventoryItem', fn ($query) => $query->where('sku', 'like', "%{$search}%"))
            ->orWhereHas('student', fn ($query) => $query->where('admission_number', 'like', "%{$search}%")->orWhere('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"))))
            ->when(in_array($filters['type'], array_column(StockMovementType::cases(), 'value'), true), fn ($query) => $query->where('type', $filters['type']))
            ->when($filters['inventory_item_id'], fn ($query, $id) => $query->where('inventory_item_id', $id))
            ->when($filters['date_from'], fn ($query, $date) => $query->whereDate('recorded_at', '>=', $date))
            ->when($filters['date_to'], fn ($query, $date) => $query->whereDate('recorded_at', '<=', $date))
            ->latest('recorded_at')->latest('id')->paginate(30)->withQueryString();

        return Inertia::render('inventory/Ledger', [
            'movements' => $movements, 'filters' => $filters,
            'movementTypes' => collect(StockMovementType::cases())->map(fn ($type) => ['value' => $type->value, 'label' => $type->label()]),
            'canCorrect' => Gate::allows('adjust-inventory'),
        ]);
    }
}
