<?php

namespace App;

enum TuitionClearanceStatus: string
{
    case Unconfirmed = 'unconfirmed';
    case PartiallyPaid = 'partially_paid';
    case FullyPaid = 'fully_paid';

    public function label(): string
    {
        return match ($this) {
            self::Unconfirmed => 'Unconfirmed',
            self::PartiallyPaid => 'Partially paid',
            self::FullyPaid => 'Fully paid',
        };
    }
}
