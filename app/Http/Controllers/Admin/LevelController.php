<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveLevelRequest;
use App\Models\Level;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class LevelController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function store(SaveLevelRequest $request): RedirectResponse
    {
        $level = Level::query()->create($request->validated());
        $this->activityLogger->record($request->user(), 'level.created', $level, newValues: $level->getAttributes());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Level created.']);

        return back();
    }

    public function update(SaveLevelRequest $request, Level $level): RedirectResponse
    {
        $old = $level->getAttributes();
        $level->update($request->validated());
        $this->activityLogger->record($request->user(), 'level.updated', $level, $old, $level->getAttributes());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Level updated.']);

        return back();
    }
}
