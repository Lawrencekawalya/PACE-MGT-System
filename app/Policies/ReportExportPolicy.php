<?php

namespace App\Policies;

use App\Models\ReportExport;
use App\Models\User;

class ReportExportPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->create($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ReportExport $reportExport): bool
    {
        return $reportExport->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('view-academic-reports') || $user->can('view-inventory-reports');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ReportExport $reportExport): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ReportExport $reportExport): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ReportExport $reportExport): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ReportExport $reportExport): bool
    {
        return false;
    }

    public function download(User $user, ReportExport $reportExport): bool
    {
        return $this->view($user, $reportExport);
    }
}
