<?php

namespace App\Console\Commands;

use App\Services\SystemHealthService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('system:check')]
#[Description('Check infrastructure and release readiness')]
class SystemCheck extends Command
{
    public function handle(SystemHealthService $health): int
    {
        $infrastructure = $health->infrastructure();
        $checks = [...$infrastructure['checks'], ...$health->releaseChecks()];
        $this->table(['Check', 'Status', 'Detail'], collect($checks)->map(fn (array $check): array => [
            $check['label'], strtoupper($check['status']), $check['detail'],
        ]));

        return collect($infrastructure['checks'])->contains('status', 'failed')
            ? self::FAILURE
            : self::SUCCESS;
    }
}
