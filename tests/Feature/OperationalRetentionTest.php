<?php

use App\Models\ActivityLog;
use App\Models\CatalogueImport;
use App\Models\ReportExport;
use App\Models\User;
use App\ReportExportStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    Storage::fake('local');
    config([
        'operations.backup_disk' => 'local',
        'operations.backup_path' => 'backups',
        'operations.backup_retention_days' => 1,
        'operations.catalogue_file_retention_days' => 1,
        'operations.activity_log_retention_days' => 1,
        'operations.notification_retention_days' => 1,
    ]);
});

test('operational retention prunes expired files and records while preserving current data', function () {
    $user = User::factory()->create();
    $oldImport = CatalogueImport::factory()->create([
        'uploaded_by' => $user->id, 'status' => 'committed', 'original_name' => 'old.xlsx',
        'checksum' => str_repeat('a', 64), 'file_path' => 'catalogue-imports/old.xlsx',
        'created_at' => now()->subDays(2),
    ]);
    $currentImport = CatalogueImport::factory()->create([
        'uploaded_by' => $user->id, 'status' => 'committed', 'original_name' => 'current.xlsx',
        'checksum' => str_repeat('b', 64), 'file_path' => 'catalogue-imports/current.xlsx',
    ]);
    Storage::disk('local')->put($oldImport->file_path, 'old');
    Storage::disk('local')->put($currentImport->file_path, 'current');

    $export = ReportExport::factory()->create([
        'user_id' => $user->id, 'status' => ReportExportStatus::Completed,
        'path' => 'report-exports/expired.csv', 'expires_at' => now()->subMinute(),
    ]);
    Storage::disk('local')->put($export->path, 'expired');
    $oldLog = ActivityLog::query()->create(['user_id' => $user->id, 'event' => 'old.event']);
    DB::table('activity_logs')->where('id', $oldLog->id)->update(['created_at' => now()->subDays(2)]);
    $currentLog = ActivityLog::query()->create(['user_id' => $user->id, 'event' => 'current.event']);
    DB::table('notifications')->insert([
        'id' => (string) Str::uuid(), 'type' => 'Test', 'notifiable_type' => User::class,
        'notifiable_id' => $user->id, 'data' => '{}', 'created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2),
    ]);

    $this->artisan('system:prune')->assertSuccessful();

    Storage::disk('local')->assertMissing($oldImport->file_path);
    Storage::disk('local')->assertExists($currentImport->file_path);
    Storage::disk('local')->assertMissing($export->path);
    $this->assertModelMissing($export);
    $this->assertModelMissing($oldLog);
    $this->assertModelExists($currentLog);
    expect(DB::table('notifications')->count())->toBe(0);
});

test('expired backups and checksum sidecars are pruned together', function () {
    Storage::disk('local')->put('backups/old.sqlite', 'backup');
    Storage::disk('local')->put('backups/old.sqlite.sha256', 'checksum');
    touch(Storage::disk('local')->path('backups/old.sqlite'), now()->subDays(2)->timestamp);

    $this->artisan('system:prune')->assertSuccessful();

    Storage::disk('local')->assertMissing('backups/old.sqlite');
    Storage::disk('local')->assertMissing('backups/old.sqlite.sha256');
});
