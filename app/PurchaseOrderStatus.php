<?php

namespace App;

enum PurchaseOrderStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Sent = 'sent';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::Approved => 'Approved',
            self::Sent => 'Sent',
            self::PartiallyReceived => 'Partially received',
            self::Received => 'Received',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }
}
