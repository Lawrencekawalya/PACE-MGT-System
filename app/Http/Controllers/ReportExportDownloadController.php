<?php

namespace App\Http\Controllers;

use App\Models\ReportExport;
use App\ReportExportStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportDownloadController extends Controller
{
    public function __invoke(Request $request, ReportExport $reportExport): StreamedResponse
    {
        Gate::authorize('download', $reportExport);
        abort_unless(
            $reportExport->status === ReportExportStatus::Completed
            && $reportExport->path !== null
            && ($reportExport->expires_at === null || $reportExport->expires_at->isFuture())
            && Storage::disk($reportExport->disk)->exists($reportExport->path),
            404,
        );

        return Storage::disk($reportExport->disk)->download($reportExport->path, $reportExport->original_filename);
    }
}
