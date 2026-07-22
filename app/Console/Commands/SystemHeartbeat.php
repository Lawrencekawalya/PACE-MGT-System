<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Signature('system:heartbeat')]
#[Description('Record a scheduler heartbeat for operational monitoring')]
class SystemHeartbeat extends Command
{
    public function handle(): int
    {
        Cache::put('system:scheduler:last-run', now()->toIso8601String(), now()->addMinutes(10));
        $this->info('Scheduler heartbeat recorded.');

        return self::SUCCESS;
    }
}
