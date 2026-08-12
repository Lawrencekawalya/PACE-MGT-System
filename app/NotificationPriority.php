<?php

namespace App;

enum NotificationPriority: string
{
    case Information = 'information';
    case Warning = 'warning';
    case ActionRequired = 'action_required';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Information => 'Information',
            self::Warning => 'Warning',
            self::ActionRequired => 'Action required',
            self::Critical => 'Critical',
        };
    }
}
