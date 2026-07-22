<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Level;
use App\Models\ReportExport;
use App\PaceAssignmentStatus;
use App\ReportType;
use App\Services\ReportDataService;
use App\StudentStatus;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(private ReportDataService $reports) {}

    public function __invoke(Request $request): Response
    {
        $fallback = $request->user()->can('view-academic-reports') ? ReportType::StudentProgress : ReportType::Inventory;
        $type = ReportType::tryFrom($request->string('report_type')->toString()) ?? $fallback;
        $this->reports->authorize($request->user(), $type);
        $filters = $request->validate([
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'term_id' => ['nullable', 'integer', 'exists:terms,id'],
            'level_id' => ['nullable', 'integer', 'exists:levels,id'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'student_status' => ['nullable', Rule::enum(StudentStatus::class)],
            'assignment_status' => ['nullable', Rule::enum(PaceAssignmentStatus::class)],
            'stock' => ['nullable', Rule::in(['available', 'low', 'out'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);
        if (! $type->isInventory() && empty($filters['academic_year_id'])) {
            $filters['academic_year_id'] = AcademicYear::query()->where('is_active', true)->value('id');
        }
        $result = $this->reports->data($type, $filters);
        $page = max(1, $request->integer('page', 1));
        $perPage = 25;
        $paginator = new LengthAwarePaginator(
            $result['rows']->forPage($page, $perPage)->values(),
            $result['rows']->count(), $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return Inertia::render('reports/Index', [
            'reportType' => $type->value,
            'reportTypes' => collect(ReportType::cases())
                ->filter(fn (ReportType $report) => $request->user()->can($report->isInventory() ? 'view-inventory-reports' : 'view-academic-reports'))
                ->map(fn (ReportType $report) => ['value' => $report->value, 'label' => $report->label()])->values(),
            'rows' => $paginator,
            'summary' => $result['summary'],
            'filters' => $filters,
            'options' => [
                'academicYears' => AcademicYear::query()->with('terms:id,academic_year_id,name')->latest('starts_on')->get(['id', 'name']),
                'levels' => Level::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name']),
                'courses' => Course::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
                'studentStatuses' => collect(StudentStatus::cases())->map(fn ($status) => ['value' => $status->value, 'label' => $status->label()]),
                'assignmentStatuses' => collect(PaceAssignmentStatus::cases())->map(fn ($status) => ['value' => $status->value, 'label' => $status->label()]),
            ],
            'exports' => ReportExport::query()->where('user_id', $request->user()->id)->latest()->limit(10)->get(),
        ]);
    }
}
