<?php

namespace App;

enum ScoreKeyRequestStatus: string
{
    case Pending = 'pending';
    case PartiallyIssued = 'partially_issued';
    case Issued = 'issued';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::PartiallyIssued => 'Partially issued',
            self::Issued => 'Issued',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }

    public function canIssue(): bool
    {
        return in_array($this, [self::Pending, self::PartiallyIssued], true);
    }
}
