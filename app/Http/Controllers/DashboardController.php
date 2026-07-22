<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\SchoolSetting;
use App\Models\User;
use App\PermissionName;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $canManageSetup = $request->user()?->can(PermissionName::ManageSchoolSettings->value) ?? false;

        return Inertia::render('Dashboard', [
            'setup' => $canManageSetup ? [
                'school_settings' => SchoolSetting::query()->exists(),
                'roles' => Role::query()->count() === 3,
                'administrator' => User::query()->where('is_active', true)
                    ->whereHas('roles', fn ($query) => $query->where('name', 'administrator'))
                    ->exists(),
            ] : null,
        ]);
    }
}
