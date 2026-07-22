<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('backup:restore {backup : Relative backup path on the configured disk} {--connection= : Database connection name} {--force : Confirm destructive restore}')]
#[Description('Verify and restore a database backup after creating a safety backup')]
class RestoreDatabase extends Command
{
    public function handle(DatabaseBackupService $backups): int
    {
        if (! $this->option('force')) {
            $this->error('Restore refused. Re-run with --force after verifying the selected backup.');

            return self::FAILURE;
        }
        $connection = $this->option('connection');
        $connectionName = is_string($connection) && $connection !== '' ? $connection : null;
        $safety = $backups->create($connectionName);
        $this->warn("Safety backup created: {$safety['path']}");
        $this->call('down', ['--retry' => 60]);
        try {
            $backups->restore((string) $this->argument('backup'), $connectionName);
        } finally {
            $this->call('up');
        }
        $this->info('Database restore completed and checksum verified.');

        return self::SUCCESS;
    }
}
