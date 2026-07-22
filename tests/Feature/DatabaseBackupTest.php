<?php

use App\Services\DatabaseBackupService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->databasePath = tempnam(sys_get_temp_dir(), 'pace-backup-test-');
    config([
        'operations.backup_disk' => 'local',
        'operations.backup_path' => 'backups',
        'database.connections.backup_test' => [
            'driver' => 'sqlite',
            'database' => $this->databasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ]);
    DB::purge('backup_test');
    DB::connection('backup_test')->statement('create table samples (id integer primary key, name text not null)');
    DB::connection('backup_test')->table('samples')->insert(['name' => 'original']);
});

afterEach(function () {
    DB::purge('backup_test');
    @unlink($this->databasePath);
});

test('sqlite backup is private checksummed and restorable', function () {
    $backups = app(DatabaseBackupService::class);
    $backup = $backups->create('backup_test');

    Storage::disk('local')->assertExists($backup['path']);
    Storage::disk('local')->assertExists("{$backup['path']}.sha256");
    expect($backup['driver'])->toBe('sqlite')
        ->and($backup['bytes'])->toBeGreaterThan(0);

    DB::connection('backup_test')->table('samples')->insert(['name' => 'later']);
    expect(DB::connection('backup_test')->table('samples')->count())->toBe(2);
    $backups->restore($backup['path'], 'backup_test');

    expect(DB::connection('backup_test')->table('samples')->pluck('name')->all())->toBe(['original']);
});

test('restore rejects a backup whose checksum does not match', function () {
    $backups = app(DatabaseBackupService::class);
    $backup = $backups->create('backup_test');
    Storage::disk('local')->put($backup['path'], 'tampered');

    expect(fn () => $backups->restore($backup['path'], 'backup_test'))
        ->toThrow(RuntimeException::class, 'checksum does not match');
});

test('restore command requires explicit force confirmation', function () {
    $this->artisan('backup:restore', ['backup' => 'backups/example.sqlite'])
        ->assertFailed()
        ->expectsOutputToContain('Restore refused');
});
