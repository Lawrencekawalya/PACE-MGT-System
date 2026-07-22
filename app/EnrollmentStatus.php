<?php

namespace App;

enum EnrollmentStatus: string
{
    case Active = 'active';
    case Promoted = 'promoted';
    case Retained = 'retained';
    case Transferred = 'transferred';
    case Completed = 'completed';
    case Withdrawn = 'withdrawn';
}
