<?php

namespace App\Policies;

use App\Models\PaceAssignment;
use App\Models\User;
use App\PermissionName;

class PaceAssignmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::AssignPaces) || $user->hasPermission(PermissionName::IssuePaces);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PaceAssignment $paceAssignment): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission(PermissionName::AssignPaces);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PaceAssignment $paceAssignment): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PaceAssignment $paceAssignment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PaceAssignment $paceAssignment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PaceAssignment $paceAssignment): bool
    {
        return false;
    }
}
