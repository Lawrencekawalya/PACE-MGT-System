<?php

namespace App;

enum StockMovementType: string
{
    case Receipt = 'receipt';
    case Issue = 'issue';
    case Damage = 'damage';
    case Loss = 'loss';
    case Adjustment = 'adjustment';
    case Correction = 'correction';

    public function label(): string
    {
        return match ($this) {
            self::Receipt => 'Receipt', self::Issue => 'Issue',
            self::Damage => 'Damage', self::Loss => 'Loss',
            self::Adjustment => 'Adjustment', self::Correction => 'Correction',
        };
    }
}
