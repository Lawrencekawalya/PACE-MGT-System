<?php

namespace App;

enum RoleName: string
{
    case Administrator = 'administrator';
    case Teacher = 'teacher';
    case PaceOfficer = 'pace_officer';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrator',
            self::Teacher => 'Teacher',
            self::PaceOfficer => 'PACE Officer',
        };
    }
}
