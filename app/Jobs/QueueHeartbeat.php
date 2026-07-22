<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class QueueHeartbeat implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 600;

    public function handle(): void
    {
        Cache::forever('system:queue:last-run', now()->toIso8601String());
    }
}
