<?php

namespace App;

enum OperationalAlertStatus: string
{
    case Active = 'active';
    case Resolved = 'resolved';
}
