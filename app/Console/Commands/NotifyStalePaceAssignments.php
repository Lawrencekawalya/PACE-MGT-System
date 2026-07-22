<?php

namespace App\Console\Commands;

use App\Models\PaceAssignment;
use App\Models\User;
use App\Notifications\StalePaceAssignmentNotification;
use App\PaceAssignmentStatus;
use App\PermissionName;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('pace-assignments:notify-stale')]
#[Description('Notify academic staff about stale or awaiting-test PACE assignments')]
class NotifyStalePaceAssignments extends Command
{
    public function handle(): int
    {
        $recipients = User::query()->where('is_active', true)->get()
            ->filter(fn (User $user): bool => $user->hasPermission(PermissionName::AssignPaces));
        $assignments = PaceAssignment::query()
            ->where(fn ($query) => $query
                ->where(fn ($query) => $query->whereIn('status', [PaceAssignmentStatus::Assigned, PaceAssignmentStatus::InProgress])->where('assigned_at', '<=', now()->subDays(14)))
                ->orWhere(fn ($query) => $query->whereIn('status', [PaceAssignmentStatus::AwaitingSelfTest, PaceAssignmentStatus::AwaitingPaceTest])->where('updated_at', '<=', now()->subDays(2))))
            ->get();

        foreach ($assignments as $assignment) {
            foreach ($recipients as $recipient) {
                $alreadySentToday = $recipient->notifications()
                    ->whereDate('created_at', today())
                    ->where('data', 'like', '%"pace_assignment_id":'.$assignment->id.'%')
                    ->exists();
                if (! $alreadySentToday) {
                    $recipient->notify(new StalePaceAssignmentNotification($assignment));
                }
            }
        }

        $this->info("Reviewed {$assignments->count()} assignment(s). Notifications are limited to one per assignment and recipient each day.");

        return self::SUCCESS;
    }
}
