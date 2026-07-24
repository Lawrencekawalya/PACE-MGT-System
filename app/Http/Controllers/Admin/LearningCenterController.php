<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveLearningCenterRequest;
use App\Models\LearningCenter;
use App\Models\Level;
use App\Models\User;
use App\PermissionName;
use App\RoleName;
use App\Services\ActivityLogger;
use App\Services\LearningCenterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LearningCenterController extends Controller
{
    public function __construct(
        private LearningCenterService $learningCenters,
        private ActivityLogger $activityLogger,
    ) {}

    public function index(): Response
    {
        Gate::authorize(PermissionName::ManageAcademicSetup->value);

        return Inertia::render('admin/learning-centers/Index', [
            'learningCenters' => LearningCenter::query()
                ->with(['levels:id,learning_center_id,name,code,sort_order', 'teachers:id,name,email'])
                ->withCount(['studentEnrollments as active_students_count' => fn ($query) => $query
                    ->where('status', 'active')
                    ->whereHas('academicYear', fn ($query) => $query->where('is_active', true))])
                ->orderBy('name')
                ->get(),
            'levels' => Level::query()
                ->with('learningCenter:id,name')
                ->orderBy('sort_order')
                ->get(['id', 'learning_center_id', 'name', 'code']),
            'teachers' => User::query()
                ->where('is_active', true)
                ->whereHas('roles', fn ($query) => $query->where('name', RoleName::Teacher->value))
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }

    public function store(SaveLearningCenterRequest $request): RedirectResponse
    {
        $learningCenter = $this->learningCenters->save(null, $request->validated());
        $this->activityLogger->record(
            $request->user(),
            'learning-center.created',
            $learningCenter,
            newValues: $this->auditValues($learningCenter),
        );
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Learning center created.']);

        return back();
    }

    public function update(SaveLearningCenterRequest $request, LearningCenter $learningCenter): RedirectResponse
    {
        $learningCenter->load(['levels:id,learning_center_id', 'teachers:id']);
        $old = $this->auditValues($learningCenter);
        $learningCenter = $this->learningCenters->save($learningCenter, $request->validated());
        $this->activityLogger->record(
            $request->user(),
            'learning-center.updated',
            $learningCenter,
            oldValues: $old,
            newValues: $this->auditValues($learningCenter),
        );
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Learning center updated.']);

        return back();
    }

    /** @return array<string, mixed> */
    private function auditValues(LearningCenter $learningCenter): array
    {
        return [
            ...$learningCenter->only(['name', 'code', 'description', 'is_active']),
            'level_ids' => $learningCenter->levels->modelKeys(),
            'teacher_ids' => $learningCenter->teachers->modelKeys(),
        ];
    }
}
