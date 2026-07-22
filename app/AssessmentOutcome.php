<?php

namespace App;

enum AssessmentOutcome: string
{
    case Passed = 'passed';
    case Failed = 'failed';
}
