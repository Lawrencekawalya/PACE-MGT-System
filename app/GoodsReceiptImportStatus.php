<?php

namespace App;

enum GoodsReceiptImportStatus: string
{
    case Validating = 'validating';
    case Ready = 'ready';
    case Failed = 'failed';
    case Committed = 'committed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Validating => 'Validating',
            self::Ready => 'Ready to post',
            self::Failed => 'Validation failed',
            self::Committed => 'Posted',
            self::Cancelled => 'Cancelled',
        };
    }
}
