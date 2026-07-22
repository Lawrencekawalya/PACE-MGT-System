<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use SQLite3;
use Symfony\Component\Process\Process;

class DatabaseBackupService
{
    /** @return array{path: string, checksum: string, driver: string, bytes: int} */
    public function create(?string $connectionName = null): array
    {
        [$connectionName, $configuration] = $this->connection($connectionName);
        $driver = $this->configurationString($configuration, 'driver');
        $extension = $driver === 'sqlite' ? 'sqlite' : 'sql';
        $path = trim((string) config('operations.backup_path'), '/').'/database-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(8)).'.'.$extension;
        $temporary = $this->temporaryFile();

        try {
            match ($driver) {
                'sqlite' => $this->backupSqlite($configuration, $temporary),
                'mysql', 'mariadb' => $this->backupMysql($configuration, $temporary),
                'pgsql' => $this->backupPostgres($configuration, $temporary),
                default => throw new RuntimeException("Database backup is not supported for driver {$driver}."),
            };
            $checksum = hash_file('sha256', $temporary);
            if (! is_string($checksum)) {
                throw new RuntimeException('The database backup checksum could not be calculated.');
            }
            $stream = fopen($temporary, 'rb');
            if ($stream === false || ! Storage::disk($this->disk())->put($path, $stream)) {
                throw new RuntimeException('The database backup could not be stored.');
            }
            fclose($stream);
            Storage::disk($this->disk())->put("{$path}.sha256", $checksum);

            return ['path' => $path, 'checksum' => $checksum, 'driver' => $driver, 'bytes' => (int) filesize($temporary)];
        } finally {
            @unlink($temporary);
            DB::purge($connectionName);
        }
    }

    public function restore(string $path, ?string $connectionName = null): void
    {
        [$connectionName, $configuration] = $this->connection($connectionName);
        $this->guardPath($path);
        $disk = Storage::disk($this->disk());
        if (! $disk->exists($path) || ! $disk->exists("{$path}.sha256")) {
            throw new RuntimeException('The backup file or checksum sidecar does not exist.');
        }
        $temporary = $this->temporaryFile();

        try {
            $source = $disk->readStream($path);
            $target = fopen($temporary, 'wb');
            if ($source === null || $target === false) {
                throw new RuntimeException('The database backup could not be opened.');
            }
            stream_copy_to_stream($source, $target);
            fclose($source);
            fclose($target);
            $expected = trim($disk->get("{$path}.sha256"));
            if (! hash_equals($expected, (string) hash_file('sha256', $temporary))) {
                throw new RuntimeException('The database backup checksum does not match.');
            }

            $driver = $this->configurationString($configuration, 'driver');
            DB::purge($connectionName);
            match ($driver) {
                'sqlite' => $this->restoreSqlite($configuration, $temporary),
                'mysql', 'mariadb' => $this->restoreMysql($configuration, $temporary),
                'pgsql' => $this->restorePostgres($configuration, $temporary),
                default => throw new RuntimeException("Database restore is not supported for driver {$driver}."),
            };
        } finally {
            @unlink($temporary);
            DB::purge($connectionName);
        }
    }

    public function prune(): int
    {
        $disk = Storage::disk($this->disk());
        $cutoff = now()->subDays((int) config('operations.backup_retention_days'))->timestamp;
        $count = 0;
        foreach ($disk->files(trim((string) config('operations.backup_path'), '/')) as $path) {
            if (str_ends_with($path, '.sha256') || $disk->lastModified($path) > $cutoff) {
                continue;
            }
            $disk->delete([$path, "{$path}.sha256"]);
            $count++;
        }

        return $count;
    }

    /** @return array{0: string, 1: array<string, mixed>} */
    private function connection(?string $connectionName): array
    {
        $name = $connectionName ?? (string) config('database.default');
        $configuration = config("database.connections.{$name}");
        if (! is_array($configuration)) {
            throw new RuntimeException("Database connection {$name} is not configured.");
        }

        return [$name, $configuration];
    }

    /** @param array<string, mixed> $configuration */
    private function backupSqlite(array $configuration, string $target): void
    {
        $sourcePath = $this->configurationString($configuration, 'database');
        if ($sourcePath === ':memory:' || ! is_file($sourcePath)) {
            throw new RuntimeException('A file-based SQLite database is required for backups.');
        }
        $source = new SQLite3($sourcePath, SQLITE3_OPEN_READONLY);
        $destination = new SQLite3($target);
        try {
            if (! $source->backup($destination)) {
                throw new RuntimeException('SQLite could not create a consistent backup.');
            }
        } finally {
            $destination->close();
            $source->close();
        }
    }

    /** @param array<string, mixed> $configuration */
    private function restoreSqlite(array $configuration, string $source): void
    {
        $database = $this->configurationString($configuration, 'database');
        if ($database === ':memory:') {
            throw new RuntimeException('An in-memory SQLite database cannot be restored.');
        }
        if (! copy($source, $database)) {
            throw new RuntimeException('SQLite database restore failed.');
        }
    }

    /** @param array<string, mixed> $configuration */
    private function backupMysql(array $configuration, string $target): void
    {
        $arguments = ['mysqldump', '--single-transaction', '--skip-lock-tables', '--no-tablespaces', '--host='.$this->configurationString($configuration, 'host'), '--port='.$this->configurationString($configuration, 'port'), '--user='.$this->configurationString($configuration, 'username'), $this->configurationString($configuration, 'database')];
        $this->dumpProcess($arguments, ['MYSQL_PWD' => $this->configurationString($configuration, 'password')], $target);
    }

    /** @param array<string, mixed> $configuration */
    private function backupPostgres(array $configuration, string $target): void
    {
        $arguments = ['pg_dump', '--no-owner', '--no-privileges', '--host='.$this->configurationString($configuration, 'host'), '--port='.$this->configurationString($configuration, 'port'), '--username='.$this->configurationString($configuration, 'username'), $this->configurationString($configuration, 'database')];
        $this->dumpProcess($arguments, ['PGPASSWORD' => $this->configurationString($configuration, 'password')], $target);
    }

    /** @param array<string, mixed> $configuration */
    private function restoreMysql(array $configuration, string $source): void
    {
        $arguments = ['mysql', '--host='.$this->configurationString($configuration, 'host'), '--port='.$this->configurationString($configuration, 'port'), '--user='.$this->configurationString($configuration, 'username'), $this->configurationString($configuration, 'database')];
        $this->restoreProcess($arguments, ['MYSQL_PWD' => $this->configurationString($configuration, 'password')], $source);
    }

    /** @param array<string, mixed> $configuration */
    private function restorePostgres(array $configuration, string $source): void
    {
        $arguments = ['psql', '--host='.$this->configurationString($configuration, 'host'), '--port='.$this->configurationString($configuration, 'port'), '--username='.$this->configurationString($configuration, 'username'), '--dbname='.$this->configurationString($configuration, 'database')];
        $this->restoreProcess($arguments, ['PGPASSWORD' => $this->configurationString($configuration, 'password')], $source);
    }

    /** @param list<string> $arguments
     * @param  array<string, string>  $environment
     */
    private function dumpProcess(array $arguments, array $environment, string $target): void
    {
        $handle = fopen($target, 'wb');
        if ($handle === false) {
            throw new RuntimeException('A temporary backup file could not be opened.');
        }
        $process = new Process($arguments, env: $environment, timeout: 600);
        $errors = '';
        $process->run(function (string $type, string $buffer) use ($handle, &$errors): void {
            if ($type === Process::OUT) {
                fwrite($handle, $buffer);
            } else {
                $errors .= $buffer;
            }
        });
        fclose($handle);
        if (! $process->isSuccessful()) {
            throw new RuntimeException('Database backup command failed: '.Str::limit(trim($errors), 500));
        }
    }

    /** @param list<string> $arguments
     * @param  array<string, string>  $environment
     */
    private function restoreProcess(array $arguments, array $environment, string $source): void
    {
        $handle = fopen($source, 'rb');
        if ($handle === false) {
            throw new RuntimeException('The temporary restore file could not be opened.');
        }
        $process = new Process($arguments, env: $environment, input: $handle, timeout: 600);
        $process->run();
        fclose($handle);
        if (! $process->isSuccessful()) {
            throw new RuntimeException('Database restore command failed: '.Str::limit(trim($process->getErrorOutput()), 500));
        }
    }

    /** @param array<string, mixed> $configuration */
    private function configurationString(array $configuration, string $key): string
    {
        $value = $configuration[$key] ?? '';

        return is_scalar($value) ? (string) $value : '';
    }

    private function guardPath(string $path): void
    {
        $prefix = trim((string) config('operations.backup_path'), '/').'/';
        if (! str_starts_with($path, $prefix) || str_contains($path, '..')) {
            throw new RuntimeException('The backup path is outside the configured backup directory.');
        }
    }

    private function temporaryFile(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'pace-backup-');
        if ($path === false) {
            throw new RuntimeException('A temporary backup file could not be created.');
        }

        return $path;
    }

    private function disk(): string
    {
        return (string) config('operations.backup_disk');
    }
}
