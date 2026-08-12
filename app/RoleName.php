<?php

namespace App;

enum RoleName: string
{
    case Administrator = 'administrator';
    case Teacher = 'teacher';
    case PaceOfficer = 'pace_officer';
    case Accountant = 'accountant';
    case Management = 'management';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrator',
            self::Teacher => 'Teacher',
            self::PaceOfficer => 'PACE Officer',
            self::Accountant => 'Accountant',
            self::Management => 'Management',
        };
    }
}
