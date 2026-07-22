<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('backup:database {--connection= : Database connection name} {--no-prune : Keep expired backups}')]
#[Description('Create a checksummed private database backup')]
class BackupDatabase extends Command
{
    public function handle(DatabaseBackupService $backups): int
    {
        $connection = $this->option('connection');
        $result = $backups->create(is_string($connection) && $connection !== '' ? $connection : null);
        if (! $this->option('no-prune')) {
            $backups->prune();
        }
        $this->info("Backup created: {$result['path']}");
        $this->line("SHA-256: {$result['checksum']}");
        $this->line("Size: {$result['bytes']} bytes");

        return self::SUCCESS;
    }
}
