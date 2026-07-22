<?php

namespace App\Policies;

use App\Models\PaceAttempt;
use App\Models\User;
use App\PermissionName;
use App\RoleName;

class PaceAttemptPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::EnterTestResults) || $user->hasPermission(PermissionName::ViewAcademicReports);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PaceAttempt $paceAttempt): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission(PermissionName::EnterTestResults);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PaceAttempt $paceAttempt): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PaceAttempt $paceAttempt): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PaceAttempt $paceAttempt): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PaceAttempt $paceAttempt): bool
    {
        return false;
    }

    public function correct(User $user, PaceAttempt $paceAttempt): bool
    {
        return $user->hasRole(RoleName::Administrator);
    }
}
