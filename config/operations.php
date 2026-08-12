<?php

return [
    'backup_disk' => env('BACKUP_DISK', 'local'),
    'backup_path' => env('BACKUP_PATH', 'backups'),
    'backup_retention_days' => (int) env('BACKUP_RETENTION_DAYS', 30),
    'catalogue_file_retention_days' => (int) env('CATALOGUE_FILE_RETENTION_DAYS', 90),
    'activity_log_retention_days' => (int) env('ACTIVITY_LOG_RETENTION_DAYS', 2555),
    'notification_retention_days' => (int) env('NOTIFICATION_RETENTION_DAYS', 90),
    'scheduler_grace_minutes' => (int) env('SCHEDULER_GRACE_MINUTES', 5),
    'notification_reminders' => [
        'inventory_minutes' => (int) env('NOTIFICATION_INVENTORY_REMINDER_MINUTES', 240),
        'order_minutes' => (int) env('NOTIFICATION_ORDER_REMINDER_MINUTES', 240),
        'finance_minutes' => (int) env('NOTIFICATION_FINANCE_REMINDER_MINUTES', 240),
        'system_minutes' => (int) env('NOTIFICATION_SYSTEM_REMINDER_MINUTES', 30),
    ],
];
