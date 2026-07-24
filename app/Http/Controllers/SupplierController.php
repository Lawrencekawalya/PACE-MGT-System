<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function index(): Response
    {
        Gate::authorize('viewAny', Supplier::class);

        return Inertia::render('suppliers/Index', [
            'suppliers' => Supplier::query()->withCount('purchaseOrders')->orderBy('name')->get(),
            'canManage' => Gate::allows('create', Supplier::class),
        ]);
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $supplier = Supplier::query()->create($request->validated());
        $this->activityLogger->record($request->user(), 'supplier.created', $supplier, newValues: $supplier->toArray());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Supplier created.']);

        return back();
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $old = $supplier->toArray();
        $supplier->update($request->validated());
        $this->activityLogger->record($request->user(), 'supplier.updated', $supplier, $old, $supplier->fresh()->toArray());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Supplier updated.']);

        return back();
    }
}
