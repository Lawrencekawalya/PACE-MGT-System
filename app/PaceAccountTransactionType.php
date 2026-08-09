<?php

namespace App;

enum PaceAccountTransactionType: string
{
    case Payment = 'payment';
    case PaceIssue = 'pace_issue';
    case IssueReversal = 'issue_reversal';

    public function label(): string
    {
        return match ($this) {
            self::Payment => 'Payment received',
            self::PaceIssue => 'PACE issued',
            self::IssueReversal => 'PACE issue reversed',
        };
    }
}
