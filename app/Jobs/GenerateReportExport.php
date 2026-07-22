<?php

namespace App\Jobs;

use App\Models\ReportExport;
use App\ReportExportStatus;
use App\Services\ReportExportGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use Throwable;

class GenerateReportExport implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public ReportExport $export) {}

    public function handle(ReportExportGenerator $generator): void
    {
        $generator->generate($this->export);
    }

    public function failed(?Throwable $exception): void
    {
        $export = $this->export->fresh();

        if ($export?->status === ReportExportStatus::Failed) {
            return;
        }

        $export?->update([
            'status' => ReportExportStatus::Failed,
            'error_message' => Str::limit($exception?->getMessage() ?? 'Export generation failed.', 1000),
        ]);
    }
}
