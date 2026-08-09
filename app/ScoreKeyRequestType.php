<?php

namespace App;

enum ScoreKeyRequestType: string
{
    case NewIssue = 'new_issue';
    case Replacement = 'replacement';
    case AdditionalCopy = 'additional_copy';

    public function label(): string
    {
        return match ($this) {
            self::NewIssue => 'New issue',
            self::Replacement => 'Replacement',
            self::AdditionalCopy => 'Additional copy',
        };
    }
}
