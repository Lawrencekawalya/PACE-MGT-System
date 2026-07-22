<?php

namespace App;

enum StudentStatus: string
{
    case Active = 'active';
    case Withdrawn = 'withdrawn';
    case Graduated = 'graduated';
    case Archived = 'archived';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
