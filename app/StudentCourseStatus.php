<?php

namespace App;

enum StudentCourseStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Withdrawn = 'withdrawn';
}
