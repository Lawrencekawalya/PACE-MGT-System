<?php

namespace App\Http\Middleware;

use App\Models\SchoolSetting;
use App\PermissionName;
use App\RoleName;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $roleNames = [];
        $permissionNames = [];

        if ($user !== null && Schema::hasTable('roles')) {
            $user->loadMissing(['roles.permissions', 'directPermissions']);
            $roleNames = $user->roles->pluck('name')->values()->all();
            $permissionNames = in_array(RoleName::Administrator->value, $roleNames, true)
                ? collect(PermissionName::cases())->map->value->all()
                : $user->roles->flatMap->permissions
                    ->merge($user->directPermissions)
                    ->pluck('name')
                    ->unique()
                    ->values()
                    ->all();
        }

        $settings = Schema::hasTable('school_settings') ? SchoolSetting::query()->first() : null;

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'school' => $settings === null ? [
                ...SchoolSetting::defaults(),
                'logo_url' => null,
            ] : [
                ...$settings->only(['official_name', 'short_name', 'slogan', 'timezone', 'date_format', 'time_format']),
                'logo_url' => $settings->logo_path === null ? null : Storage::disk('public')->url($settings->logo_path),
            ],
            'auth' => [
                'user' => $user?->withoutRelations(),
                'roles' => $roleNames,
                'permissions' => $permissionNames,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
