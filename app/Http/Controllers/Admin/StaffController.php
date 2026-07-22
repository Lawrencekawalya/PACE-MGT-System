<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStaffRequest;
use App\Http\Requests\Admin\UpdateStaffRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\PermissionName;
use App\RoleName;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $search = $request->string('search')->trim()->toString();
        $staff = User::query()
            ->with(['roles:id,name,display_name', 'directPermissions:id,name,display_name'])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/staff/Index', [
            'staff' => $staff,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', User::class);

        return Inertia::render('admin/staff/Create', $this->accessOptions());
    }

    public function store(StoreStaffRequest $request): RedirectResponse
    {
        Gate::authorize('create', User::class);
        $data = $request->validated();

        $user = DB::transaction(function () use ($data, $request): User {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'email_verified_at' => now(),
                'is_active' => true,
                'password_changed_at' => now(),
            ]);
            $user->roles()->sync(Role::query()->whereIn('name', $data['roles'])->pluck('id'));
            $user->directPermissions()->sync(
                Permission::query()->whereIn('name', $data['direct_permissions'] ?? [])->pluck('id'),
            );

            $this->activityLogger->record($request->user(), 'staff.created', $user, newValues: [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $data['roles'],
                'direct_permissions' => $data['direct_permissions'] ?? [],
            ]);

            return $user;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Staff account created.']);

        return redirect()->route('admin.staff.edit', $user);
    }

    public function edit(User $user): Response
    {
        Gate::authorize('update', $user);
        $user->load(['roles:id,name,display_name', 'directPermissions:id,name,display_name']);

        return Inertia::render('admin/staff/Edit', [
            ...$this->accessOptions(),
            'staffMember' => [
                ...$user->only(['id', 'name', 'email', 'is_active', 'last_login_at', 'created_at']),
                'roles' => $user->roles->pluck('name')->all(),
                'direct_permissions' => $user->directPermissions->pluck('name')->all(),
            ],
        ]);
    }

    public function update(UpdateStaffRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);
        $data = $request->validated();
        $this->guardAdministratorContinuity($request, $user, $data['roles'], $data['is_active']);

        DB::transaction(function () use ($data, $request, $user): void {
            $user->load(['roles', 'directPermissions']);
            $oldValues = [
                ...$user->only(['name', 'email', 'is_active']),
                'roles' => $user->roles->pluck('name')->all(),
                'direct_permissions' => $user->directPermissions->pluck('name')->all(),
            ];

            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'is_active' => $data['is_active'],
            ]);
            $user->roles()->sync(Role::query()->whereIn('name', $data['roles'])->pluck('id'));
            $user->directPermissions()->sync(
                Permission::query()->whereIn('name', $data['direct_permissions'] ?? [])->pluck('id'),
            );

            $this->activityLogger->record($request->user(), 'staff.updated', $user, $oldValues, [
                ...$user->only(['name', 'email', 'is_active']),
                'roles' => $data['roles'],
                'direct_permissions' => $data['direct_permissions'] ?? [],
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Staff account updated.']);

        return back();
    }

    /** @return array<string, mixed> */
    private function accessOptions(): array
    {
        return [
            'roles' => Role::query()->orderBy('display_name')->get(['name', 'display_name', 'description']),
            'permissions' => Permission::query()
                ->whereIn('name', [PermissionName::IssuePaces->value, PermissionName::ViewInventoryReports->value])
                ->orderBy('display_name')
                ->get(['name', 'display_name', 'description']),
        ];
    }

    /** @param array<int, string> $roles */
    private function guardAdministratorContinuity(Request $request, User $user, array $roles, bool $isActive): void
    {
        if ((int) $request->user()?->getKey() === (int) $user->getKey() && ! $isActive) {
            throw ValidationException::withMessages(['is_active' => 'You cannot deactivate your own account.']);
        }

        $removesAdministrator = $user->hasRole(RoleName::Administrator)
            && (! in_array(RoleName::Administrator->value, $roles, true) || ! $isActive);

        if ($removesAdministrator && User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('name', RoleName::Administrator->value))
            ->count() <= 1) {
            throw ValidationException::withMessages([
                'roles' => 'At least one active Administrator account must remain.',
            ]);
        }
    }
}
