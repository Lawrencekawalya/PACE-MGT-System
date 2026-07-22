<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SavePaceRequest;
use App\Models\Course;
use App\Models\Level;
use App\Models\Pace;
use App\Models\Subject;
use App\PermissionName;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PaceController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function index(Request $request): Response
    {
        Gate::authorize(PermissionName::ViewPaceCatalogue->value);
        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'course_id' => $request->integer('course_id') ?: null,
            'subject_id' => $request->integer('subject_id') ?: null,
            'level_id' => $request->integer('level_id') ?: null,
            'status' => $request->string('status')->toString(),
        ];

        $paces = Pace::query()
            ->with(['course:id,subject_id,name,code', 'course.subject:id,name'])
            ->when($filters['search'], fn ($query, $search) => $query->where(fn ($query) => $query
                ->where('number', 'like', "%{$search}%")
                ->orWhere('title', 'like', "%{$search}%")
                ->orWhereHas('course', fn ($query) => $query->where('name', 'like', "%{$search}%"))))
            ->when($filters['course_id'], fn ($query, $courseId) => $query->where('course_id', $courseId))
            ->when($filters['subject_id'], fn ($query, $subjectId) => $query->whereHas('course', fn ($query) => $query->where('subject_id', $subjectId)))
            ->when($filters['level_id'], fn ($query, $levelId) => $query->whereHas('curriculumRequirements', fn ($query) => $query->where('level_id', $levelId)))
            ->when(in_array($filters['status'], ['active', 'inactive'], true), fn ($query) => $query->where('is_active', $filters['status'] === 'active'))
            ->orderBy('course_id')->orderBy('sequence_order')
            ->paginate(30)->withQueryString();

        return Inertia::render('admin/paces/Index', [
            'paces' => $paces,
            'filters' => $filters,
            'courses' => Course::query()->orderBy('name')->get(['id', 'name']),
            'subjects' => Subject::query()->orderBy('name')->get(['id', 'name']),
            'levels' => Level::query()->orderBy('sort_order')->get(['id', 'name']),
            'canManage' => $request->user()?->can(PermissionName::ManagePaceCatalogue->value) ?? false,
            'summary' => [
                'total' => Pace::query()->count(),
                'inactive' => Pace::query()->where('is_active', false)->count(),
                'courses_without_paces' => Course::query()->doesntHave('paces')->count(),
                'duplicates' => 0,
            ],
        ]);
    }

    public function show(Pace $pace): Response
    {
        Gate::authorize(PermissionName::ViewPaceCatalogue->value);
        $pace->load(['course.subject', 'curriculumRequirements.level']);

        return Inertia::render('admin/paces/Show', ['pace' => $pace]);
    }

    public function store(SavePaceRequest $request): RedirectResponse
    {
        $pace = Pace::query()->create($request->validated());
        $this->activityLogger->record($request->user(), 'pace.created', $pace, newValues: $pace->getAttributes());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'PACE created.']);

        return back();
    }

    public function update(SavePaceRequest $request, Pace $pace): RedirectResponse
    {
        $old = $pace->getAttributes();
        $pace->update($request->validated());
        $this->activityLogger->record($request->user(), 'pace.updated', $pace, $old, $pace->getAttributes());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'PACE updated.']);

        return back();
    }
}
