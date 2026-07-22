<?php

namespace App;

enum ReportFormat: string
{
    case Csv = 'csv';
    case Xlsx = 'xlsx';

    public function label(): string
    {
        return strtoupper($this->value);
    }
}
