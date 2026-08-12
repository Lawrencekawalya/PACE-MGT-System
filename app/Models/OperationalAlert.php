<?php

namespace App\Models;

use App\NotificationCategory;
use App\NotificationPriority;
use App\OperationalAlertStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property NotificationCategory $category
 * @property NotificationPriority $priority
 * @property OperationalAlertStatus $status
 * @property int $affected_count
 * @property string|null $fingerprint
 * @property int $notification_sequence
 * @property array<string, mixed>|null $metadata
 * @property Carbon $first_detected_at
 * @property Carbon $last_detected_at
 * @property Carbon|null $last_notified_at
 * @property Carbon|null $resolved_at
 */
#[Fillable([
    'key', 'category', 'priority', 'status', 'affected_count', 'fingerprint', 'metadata',
    'notification_sequence', 'first_detected_at', 'last_detected_at', 'last_notified_at', 'resolved_at',
])]
class OperationalAlert extends Model
{
    protected function casts(): array
    {
        return [
            'category' => NotificationCategory::class,
            'priority' => NotificationPriority::class,
            'status' => OperationalAlertStatus::class,
            'affected_count' => 'integer',
            'notification_sequence' => 'integer',
            'metadata' => 'array',
            'first_detected_at' => 'datetime',
            'last_detected_at' => 'datetime',
            'last_notified_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }
}
