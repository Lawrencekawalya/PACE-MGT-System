<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveAcademicYearRequest;
use App\Models\AcademicYear;
use App\PermissionName;
use App\Services\AcademicPeriodService;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AcademicPeriodController extends Controller
{
    public function __construct(private AcademicPeriodService $periods, private ActivityLogger $activityLogger) {}

    public function index(): Response
    {
        Gate::authorize(PermissionName::ManageAcademicSetup->value);

        return Inertia::render('admin/academic-periods/Index', [
            'academicYears' => AcademicYear::query()->with('terms')->orderByDesc('starts_on')->get()
                ->map(fn (AcademicYear $year): array => [
                    ...$year->only(['id', 'name', 'is_active', 'is_closed']),
                    'starts_on' => $year->starts_on->toDateString(),
                    'ends_on' => $year->ends_on->toDateString(),
                    'terms' => $year->terms->map(fn ($term): array => [
                        ...$term->only(['id', 'name', 'sort_order', 'is_active', 'is_closed']),
                        'starts_on' => $term->starts_on->toDateString(),
                        'ends_on' => $term->ends_on->toDateString(),
                    ])->values()->all(),
                ]),
        ]);
    }

    public function store(SaveAcademicYearRequest $request): RedirectResponse
    {
        $year = $this->periods->saveYear(null, $request->validated());
        $this->activityLogger->record($request->user(), 'academic-year.created', $year, newValues: $year->only(['name', 'starts_on', 'ends_on', 'is_active', 'is_closed']));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Academic year created.']);

        return back();
    }

    public function update(SaveAcademicYearRequest $request, AcademicYear $academicYear): RedirectResponse
    {
        $old = $academicYear->only(['name', 'starts_on', 'ends_on', 'is_active', 'is_closed']);
        $year = $this->periods->saveYear($academicYear, $request->validated());
        $this->activityLogger->record($request->user(), 'academic-year.updated', $year, $old, $year->only(array_keys($old)));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Academic year updated.']);

        return back();
    }
}
