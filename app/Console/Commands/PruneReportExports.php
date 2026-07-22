<?php

namespace App\Console\Commands;

use App\Models\ReportExport;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('reports:prune')]
#[Description('Delete expired private report export files and records')]
class PruneReportExports extends Command
{
    public function handle(): int
    {
        $count = 0;
        ReportExport::query()->where('expires_at', '<=', now())->eachById(function (ReportExport $export) use (&$count): void {
            if ($export->path !== null) {
                Storage::disk($export->disk)->delete($export->path);
            }
            $export->delete();
            $count++;
        });
        $this->info("Pruned {$count} expired report exports.");

        return self::SUCCESS;
    }
}
