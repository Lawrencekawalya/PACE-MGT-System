<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetStaffPasswordRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class StaffPasswordController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}

    /**
     * Handle the incoming request.
     */
    public function __invoke(ResetStaffPasswordRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);
        $user->update([
            'password' => $request->validated('password'),
            'password_changed_at' => now(),
        ]);
        $this->activityLogger->record(
            $request->user(),
            'staff.password-reset',
            $user,
            reason: $request->validated('reason'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Staff password reset.']);

        return back();
    }
}
