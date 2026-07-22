<?php

namespace App;

enum InventoryItemType: string
{
    case PaceBooklet = 'pace_booklet';
    case ScoreKey = 'score_key';

    public function label(): string
    {
        return match ($this) {
            self::PaceBooklet => 'PACE booklet',
            self::ScoreKey => 'Score Key',
        };
    }
}
