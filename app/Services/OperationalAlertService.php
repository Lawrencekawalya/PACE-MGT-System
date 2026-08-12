<?php

namespace App\Services;

use App\Models\OperationalAlert;
use App\Models\User;
use App\NotificationCategory;
use App\NotificationPriority;
use App\Notifications\OperationalNotification;
use App\OperationalAlertStatus;
use Illuminate\Support\Facades\DB;

class OperationalAlertService
{
    public function __construct(private NotificationDispatcher $notifications) {}

    /**
     * @param  iterable<int, User>  $recipients
     * @param  array<string, scalar|null>  $context
     * @param  array<string, mixed>  $metadata
     */
    public function observe(
        string $key,
        bool $active,
        int $affectedCount,
        NotificationCategory $category,
        NotificationPriority $priority,
        string $title,
        string $message,
        string $url,
        iterable $recipients,
        int $reminderMinutes,
        array $context = [],
        array $metadata = [],
        bool $notifyResolution = false,
    ): ?OperationalAlert {
        return DB::transaction(function () use (
            $key, $active, $affectedCount, $category, $priority, $title, $message, $url,
            $recipients, $reminderMinutes, $context, $metadata, $notifyResolution,
        ): ?OperationalAlert {
            $alert = OperationalAlert::query()->where('key', $key)->lockForUpdate()->first();

            if (! $active) {
                if ($alert === null || $alert->status === OperationalAlertStatus::Resolved) {
                    return $alert;
                }

                $alert->update([
                    'status' => OperationalAlertStatus::Resolved,
                    'affected_count' => 0,
                    'last_detected_at' => now(),
                    'resolved_at' => now(),
                    'metadata' => $metadata,
                ]);
                if ($notifyResolution) {
                    $this->notify(
                        $alert,
                        $recipients,
                        "{$title} resolved",
                        'The previously reported condition has returned to normal.',
                        $url,
                        $category,
                        NotificationPriority::Information,
                        [...$context, 'resolved' => true],
                    );
                }

                return $alert->refresh();
            }

            $isNew = $alert === null;
            $wasResolved = false;
            $previousCount = 0;
            $previousPriority = null;
            $previousFingerprint = null;
            if ($alert !== null) {
                $wasResolved = $alert->status === OperationalAlertStatus::Resolved;
                $previousCount = $alert->affected_count;
                $previousPriority = $alert->priority;
                $previousFingerprint = $alert->fingerprint;
            }
            $fingerprint = hash('sha256', json_encode($metadata, JSON_THROW_ON_ERROR));

            if ($alert === null) {
                $alert = OperationalAlert::query()->create([
                    'key' => $key,
                    'category' => $category,
                    'priority' => $priority,
                    'status' => OperationalAlertStatus::Active,
                    'affected_count' => $affectedCount,
                    'fingerprint' => $fingerprint,
                    'metadata' => $metadata,
                    'first_detected_at' => now(),
                    'last_detected_at' => now(),
                ]);
            } else {
                $alert->update([
                    'category' => $category,
                    'priority' => $priority,
                    'status' => OperationalAlertStatus::Active,
                    'affected_count' => $affectedCount,
                    'fingerprint' => $fingerprint,
                    'metadata' => $metadata,
                    'first_detected_at' => $wasResolved ? now() : $alert->first_detected_at,
                    'last_detected_at' => now(),
                    'resolved_at' => null,
                ]);
            }

            $shouldNotify = $isNew
                || $wasResolved
                || $this->priorityRank($priority) > $this->priorityRank($previousPriority)
                || $this->materiallyChanged($previousCount, $affectedCount)
                || $previousFingerprint !== $fingerprint
                || $alert->last_notified_at === null
                || $alert->last_notified_at->lte(now()->subMinutes($reminderMinutes));

            if ($shouldNotify) {
                $this->notify($alert, $recipients, $title, $message, $url, $category, $priority, $context);
            }

            return $alert->refresh();
        }, 3);
    }

    /** @param iterable<int, User> $recipients
     * @param  array<string, scalar|null>  $context
     */
    private function notify(
        OperationalAlert $alert,
        iterable $recipients,
        string $title,
        string $message,
        string $url,
        NotificationCategory $category,
        NotificationPriority $priority,
        array $context,
    ): void {
        $alert->increment('notification_sequence');
        $alert->update(['last_notified_at' => now()]);
        $this->notifications->send($recipients, new OperationalNotification(
            $title,
            $message,
            $url,
            $category,
            $priority,
            "operational-alert:{$alert->id}:{$alert->notification_sequence}",
            [...$context, 'operational_alert_id' => $alert->id],
        ));
    }

    private function materiallyChanged(int $previous, int $current): bool
    {
        if ($previous === $current) {
            return false;
        }

        return abs($current - $previous) >= max(1, (int) ceil(max($previous, 1) * 0.1));
    }

    private function priorityRank(?NotificationPriority $priority): int
    {
        return match ($priority) {
            NotificationPriority::Information => 1,
            NotificationPriority::Warning => 2,
            NotificationPriority::ActionRequired => 3,
            NotificationPriority::Critical => 4,
            null => 0,
        };
    }
}
