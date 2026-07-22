<?php

namespace App\Console\Commands;

use App\Services\DataIntegrityService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('system:validate-data')]
#[Description('Validate catalogue, stock, ownership, and active academic periods')]
class ValidateSystemData extends Command
{
    public function handle(DataIntegrityService $integrity): int
    {
        $checks = $integrity->validate();

        $this->table(
            ['Check', 'Status', 'Detail'],
            collect($checks)->map(fn (array $check): array => [
                $check['label'], strtoupper($check['status']), $check['detail'],
            ])->all(),
        );

        foreach ($checks as $check) {
            foreach ($check['issues'] as $issue) {
                $this->line("  - {$issue}");
            }
        }

        if (collect($checks)->contains('status', 'failed')) {
            $this->error('System data validation failed.');

            return self::FAILURE;
        }

        $this->info('System data validation passed.');

        return self::SUCCESS;
    }
}
