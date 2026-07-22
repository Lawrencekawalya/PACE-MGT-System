<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('pace-assignments:notify-stale')->dailyAt('07:00')->withoutOverlapping();
Schedule::command('reports:prune')->dailyAt('02:00')->withoutOverlapping();
