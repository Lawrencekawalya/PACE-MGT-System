<?php

namespace App\Services;

use App\Models\User;
use App\PermissionName;
use App\RoleName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class NotificationRecipientService
{
    /** @return Collection<int, User> */
    public function withPermission(PermissionName $permission): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($permission): void {
                $query->whereHas('directPermissions', fn (Builder $query) => $query->where('name', $permission->value))
                    ->orWhereHas('roles.permissions', fn (Builder $query) => $query->where('name', $permission->value))
                    ->orWhereHas('roles', fn (Builder $query) => $query->where('name', RoleName::Administrator->value));
            })
            ->get();
    }

    /** @return Collection<int, User> */
    public function withRole(RoleName $role): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn (Builder $query) => $query->where('name', $role->value))
            ->get();
    }

    /** @return Collection<int, User> */
    public function forLearningCenter(int $learningCenterId, PermissionName $permission): Collection
    {
        return $this->withPermission($permission)
            ->filter(fn (User $user): bool => $user->learningCenters()->whereKey($learningCenterId)->exists())
            ->values();
    }
}
