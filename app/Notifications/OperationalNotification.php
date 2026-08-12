<?php

namespace App\Notifications;

use App\NotificationCategory;
use App\NotificationPriority;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OperationalNotification extends Notification
{
    use Queueable;

    /** @param array<string, scalar|null> $context */
    public function __construct(
        public readonly string $title,
        public readonly string $message,
        public readonly string $url,
        public readonly NotificationCategory $category,
        public readonly NotificationPriority $priority = NotificationPriority::Information,
        public readonly ?string $eventKey = null,
        public readonly array $context = [],
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'category' => $this->category->value,
            'priority' => $this->priority->value,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'event_key' => $this->eventKey,
            'context' => $this->context,
        ];
    }
}
