<?php

namespace App;

enum PaceAssignmentStatus: string
{
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case AwaitingSelfTest = 'awaiting_self_test';
    case AwaitingPaceTest = 'awaiting_pace_test';
    case Passed = 'passed';
    case Failed = 'failed';
    case Reassigned = 'reassigned';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Assigned => 'Assigned', self::InProgress => 'In progress',
            self::AwaitingSelfTest => 'Awaiting Self Test', self::AwaitingPaceTest => 'Awaiting PACE Test',
            self::Passed => 'Passed', self::Failed => 'Failed', self::Reassigned => 'Reassigned', self::Cancelled => 'Cancelled',
        };
    }

    /** @return list<self> */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Assigned => [self::InProgress, self::Cancelled],
            self::InProgress => [self::AwaitingSelfTest, self::Cancelled],
            self::AwaitingSelfTest => [self::InProgress, self::AwaitingPaceTest],
            self::AwaitingPaceTest => [self::Passed, self::Failed],
            self::Failed => [self::AwaitingPaceTest, self::Reassigned],
            self::Passed, self::Reassigned, self::Cancelled => [],
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Passed, self::Reassigned, self::Cancelled], true);
    }
}
