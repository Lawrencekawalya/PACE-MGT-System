<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportExportRequest;
use App\Jobs\GenerateReportExport;
use App\Models\ReportExport;
use App\ReportExportStatus;
use App\ReportFormat;
use App\ReportType;
use App\RoleName;
use App\Services\ReportDataService;
use App\Services\ReportExportGenerator;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ReportExportController extends Controller
{
    public function __construct(
        private ReportDataService $reports,
        private ReportExportGenerator $generator,
    ) {}

    public function store(StoreReportExportRequest $request): RedirectResponse|Response
    {
        $validated = $request->validated();
        $reportType = ReportType::from($validated['report_type']);
        $filters = collect($validated)
            ->except(['report_type', 'format'])
            ->filter(fn ($value) => filled($value))
            ->all();
        if (! $reportType->isInventory() && $request->user()->hasRole(RoleName::Teacher) && ! $request->user()->hasRole(RoleName::Administrator)) {
            $filters['teacher_id'] = $request->user()->id;
        }
        $export = ReportExport::query()->create([
            'user_id' => $request->user()->id,
            'report_type' => $reportType,
            'format' => ReportFormat::from($validated['format']),
            'filters' => $filters,
            'status' => ReportExportStatus::Pending,
            'disk' => 'local',
            'expires_at' => now()->addDays((int) config('reports.expiry_days')),
        ]);

        if ($this->reports->rowCount($reportType, $filters) <= (int) config('reports.queue_threshold')) {
            $this->generator->generate($export);

            return Inertia::location(route('report-exports.download', $export));
        }

        GenerateReportExport::dispatch($export);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Report export queued.']);

        return back();
    }
}
