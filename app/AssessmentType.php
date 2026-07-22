<?php

namespace App;

enum AssessmentType: string
{
    case SelfTest = 'self_test';
    case PaceTest = 'pace_test';

    public function label(): string
    {
        return match ($this) {
            self::SelfTest => 'Self Test',
            self::PaceTest => 'PACE Test',
        };
    }
}
