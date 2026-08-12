<?php

use App\Jobs\QueueHeartbeat;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('pace-assignments:notify-stale')->dailyAt('07:00')->withoutOverlapping()->onOneServer();
Schedule::command('notifications:send-operational-summaries')->dailyAt('07:10')->withoutOverlapping()->onOneServer();
Schedule::command('system:heartbeat')->everyMinute()->withoutOverlapping()->onOneServer();
Schedule::job(new QueueHeartbeat)->everyMinute()->onOneServer();
Schedule::command('backup:database')->dailyAt('01:00')->withoutOverlapping()->onOneServer();
Schedule::command('system:prune')->dailyAt('02:30')->withoutOverlapping()->onOneServer();
