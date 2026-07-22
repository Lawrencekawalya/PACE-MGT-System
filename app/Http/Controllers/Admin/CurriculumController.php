<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveCurriculumRequirementRequest;
use App\Models\Course;
use App\Models\CurriculumRequirement;
use App\Models\Level;
use App\PermissionName;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CurriculumController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function index(): Response
    {
        Gate::authorize(PermissionName::ManagePaceCatalogue->value);

        return Inertia::render('admin/curriculum/Index', [
            'levels' => Level::query()->orderBy('sort_order')->get(['id', 'name']),
            'courses' => Course::query()->with('paces:id,course_id,number,sequence_order')->orderBy('name')->get(['id', 'name']),
            'requirements' => CurriculumRequirement::query()
                ->with(['level:id,name', 'course:id,name', 'paces:id,number'])
                ->orderBy('level_id')->orderBy('sort_order')->get(),
        ]);
    }

    public function store(SaveCurriculumRequirementRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $requirement = DB::transaction(function () use ($data): CurriculumRequirement {
            $requirement = CurriculumRequirement::query()->updateOrCreate(
                ['level_id' => $data['level_id'], 'course_id' => $data['course_id']],
                [
                    'is_required' => $data['is_required'],
                    'sort_order' => $data['sort_order'],
                    'is_active' => $data['is_active'],
                ],
            );
            $sequence = [];
            foreach ($data['pace_ids'] as $index => $paceId) {
                if (is_int($paceId)) {
                    $sequence[$paceId] = ['sequence_order' => $index + 1];
                }
            }
            $requirement->paces()->detach();
            $requirement->paces()->attach($sequence);

            return $requirement;
        });
        $this->activityLogger->record($request->user(), 'curriculum-requirement.saved', $requirement, newValues: $data);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Curriculum sequence saved.']);

        return back();
    }
}
