<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogueImport;
use App\Models\InventoryItem;
use App\Models\Pace;
use App\Models\Student;
use App\Models\User;
use App\Services\SystemHealthService;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SystemStatusController extends Controller
{
    public function __invoke(SystemHealthService $health): Response
    {
        Gate::authorize('manage-school-settings');

        return Inertia::render('admin/system-status/Index', [
            'infrastructure' => $health->infrastructure(),
            'releaseChecks' => $health->releaseChecks(),
            'metrics' => [
                'staff' => User::query()->where('is_active', true)->count(),
                'students' => Student::query()->count(),
                'paces' => Pace::query()->where('is_active', true)->count(),
                'inventory_items' => InventoryItem::query()->where('is_active', true)->count(),
                'catalogue_imports' => CatalogueImport::query()->where('status', 'committed')->count(),
            ],
        ]);
    }
}
