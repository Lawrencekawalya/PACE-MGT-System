<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\ReorderService;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ReorderController extends Controller
{
    public function __invoke(ReorderService $reorders): Response
    {
        Gate::authorize('create', PurchaseOrder::class);

        return Inertia::render('reorders/Index', [
            'items' => $reorders->suggestions(),
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }
}
