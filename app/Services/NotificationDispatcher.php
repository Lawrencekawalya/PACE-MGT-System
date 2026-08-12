<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\OperationalNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class NotificationDispatcher
{
    /** @param iterable<int, User> $recipients */
    public function send(iterable $recipients, OperationalNotification $notification, ?User $exclude = null): void
    {
        $users = collect($recipients)
            ->filter(fn (User $user): bool => $user->is_active && $user->id !== $exclude?->id)
            ->unique('id')
            ->values();

        if ($users->isEmpty()) {
            return;
        }

        $deliver = function () use ($users, $notification): void {
            $recipients = $notification->eventKey === null
                ? $users
                : $this->withoutDuplicate($users, $notification->eventKey);

            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, $notification);
            }
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($deliver);

            return;
        }

        $deliver();
    }

    /** @param Collection<int, User> $users
     * @return Collection<int, User>
     */
    private function withoutDuplicate(Collection $users, string $eventKey): Collection
    {
        return $users->filter(fn (User $user): bool => ! $user->notifications()
            ->where('data->event_key', $eventKey)
            ->exists())->values();
    }
}
