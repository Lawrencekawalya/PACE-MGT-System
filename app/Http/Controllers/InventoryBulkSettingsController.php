<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateInventoryBulkSettingsRequest;
use App\Services\InventoryBulkSettingsService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class InventoryBulkSettingsController extends Controller
{
    public function __invoke(
        UpdateInventoryBulkSettingsRequest $request,
        InventoryBulkSettingsService $settings,
    ): RedirectResponse {
        $count = $settings->update($request->validated(), $request->user());
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Stock settings updated for {$count} inventory items.",
        ]);

        return back();
    }
}
