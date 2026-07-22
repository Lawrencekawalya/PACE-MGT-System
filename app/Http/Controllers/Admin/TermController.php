<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveTermRequest;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Services\AcademicPeriodService;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class TermController extends Controller
{
    public function __construct(private AcademicPeriodService $periods, private ActivityLogger $activityLogger) {}

    public function store(SaveTermRequest $request, AcademicYear $academicYear): RedirectResponse
    {
        $term = $this->periods->saveTerm($academicYear, null, $request->validated());
        $this->activityLogger->record($request->user(), 'term.created', $term, newValues: $term->only(['academic_year_id', 'name', 'sort_order', 'starts_on', 'ends_on', 'is_active', 'is_closed']));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Term created.']);

        return back();
    }

    public function update(SaveTermRequest $request, AcademicYear $academicYear, Term $term): RedirectResponse
    {
        abort_unless($term->academic_year_id === $academicYear->id, 404);
        $old = $term->only(['name', 'sort_order', 'starts_on', 'ends_on', 'is_active', 'is_closed']);
        $term = $this->periods->saveTerm($academicYear, $term, $request->validated());
        $this->activityLogger->record($request->user(), 'term.updated', $term, $old, $term->only(array_keys($old)));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Term updated.']);

        return back();
    }
}
