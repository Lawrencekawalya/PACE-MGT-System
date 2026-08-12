<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('notifications:send-operational-summaries')]
#[Description('Run all operational notification monitors immediately')]
class SendOperationalNotificationSummaries extends Command
{
    public function handle(): int
    {
        foreach ([
            'notifications:monitor-system-health',
            'notifications:monitor-inventory',
            'notifications:monitor-orders',
            'notifications:monitor-pace-accounts',
        ] as $command) {
            $this->call($command);
        }

        return self::SUCCESS;
    }
}
