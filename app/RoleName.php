<?php

namespace App;

enum RoleName: string
{
    case Administrator = 'administrator';
    case Teacher = 'teacher';
    case Storekeeper = 'storekeeper';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrator',
            self::Teacher => 'Teacher',
            self::Storekeeper => 'Storekeeper',
        };
    }
}
