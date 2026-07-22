<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\CatalogueImport;
use App\Models\ReportExport;
use App\Services\DatabaseBackupService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

#[Signature('system:prune')]
#[Description('Apply configured retention rules to private files and operational records')]
class PruneOperationalData extends Command
{
    public function handle(DatabaseBackupService $backups): int
    {
        $counts = [
            'report exports' => $this->pruneReportExports(),
            'catalogue files' => $this->pruneCatalogueFiles(),
            'activity logs' => ActivityLog::query()->where('created_at', '<', now()->subDays((int) config('operations.activity_log_retention_days')))->delete(),
            'notifications' => DB::table('notifications')->where('created_at', '<', now()->subDays((int) config('operations.notification_retention_days')))->delete(),
            'failed jobs' => DB::table('failed_jobs')->where('failed_at', '<', now()->subDays((int) config('operations.notification_retention_days')))->delete(),
            'backups' => $backups->prune(),
        ];
        $this->table(['Resource', 'Pruned'], collect($counts)->map(fn (int $count, string $resource): array => [$resource, $count]));

        return self::SUCCESS;
    }

    private function pruneReportExports(): int
    {
        $count = 0;
        ReportExport::query()->where('expires_at', '<=', now())->eachById(function (ReportExport $export) use (&$count): void {
            if ($export->path !== null) {
                Storage::disk($export->disk)->delete($export->path);
            }
            $export->delete();
            $count++;
        });

        return $count;
    }

    private function pruneCatalogueFiles(): int
    {
        $count = 0;
        CatalogueImport::query()
            ->whereIn('status', ['committed', 'failed', 'cancelled'])
            ->where('created_at', '<', now()->subDays((int) config('operations.catalogue_file_retention_days')))
            ->eachById(function (CatalogueImport $import) use (&$count): void {
                if (Storage::disk('local')->delete($import->file_path)) {
                    $count++;
                }
            });

        return $count;
    }
}
