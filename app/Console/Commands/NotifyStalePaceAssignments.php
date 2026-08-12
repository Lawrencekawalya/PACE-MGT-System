<?php

namespace App\Console\Commands;

use App\Models\PaceAssignment;
use App\Notifications\StalePaceAssignmentNotification;
use App\PaceAssignmentStatus;
use App\PermissionName;
use App\Services\NotificationRecipientService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('pace-assignments:notify-stale')]
#[Description('Notify academic staff about stale or awaiting-test PACE assignments')]
class NotifyStalePaceAssignments extends Command
{
    public function handle(NotificationRecipientService $recipients): int
    {
        $assignments = PaceAssignment::query()
            ->where(fn ($query) => $query
                ->where(fn ($query) => $query->whereIn('status', [PaceAssignmentStatus::Assigned, PaceAssignmentStatus::InProgress])->where('assigned_at', '<=', now()->subDays(14)))
                ->orWhere(fn ($query) => $query->whereIn('status', [PaceAssignmentStatus::AwaitingSelfTest, PaceAssignmentStatus::AwaitingPaceTest])->where('updated_at', '<=', now()->subDays(2))))
            ->with('studentCourse.enrollment:id,learning_center_id')
            ->get();

        foreach ($assignments as $assignment) {
            $centerId = $assignment->studentCourse->enrollment->learning_center_id;
            if ($centerId === null) {
                continue;
            }
            foreach ($recipients->forLearningCenter($centerId, PermissionName::AssignPaces) as $recipient) {
                $alreadySentToday = $recipient->notifications()
                    ->where('data->event_key', "pace-assignment:{$assignment->id}:stale:".today()->toDateString())
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
