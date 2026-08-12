<?php

namespace App;

enum NotificationCategory: string
{
    case Academic = 'academic';
    case Inventory = 'inventory';
    case Finance = 'finance';
    case Ordering = 'ordering';
    case Administration = 'administration';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::Academic => 'Academic',
            self::Inventory => 'Inventory',
            self::Finance => 'PACE accounts',
            self::Ordering => 'Orders',
            self::Administration => 'Administration',
            self::System => 'System',
        };
    }
}
