<?php

namespace App;

enum PurchaseOrderSource: string
{
    case Manual = 'manual';
    case Reorder = 'reorder';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Reorder => 'Reorder queue',
        };
    }
}
