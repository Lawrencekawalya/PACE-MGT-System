<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveSubjectRequest;
use App\Models\Subject;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class SubjectController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function store(SaveSubjectRequest $request): RedirectResponse
    {
        $subject = Subject::query()->create($request->validated());
        $this->activityLogger->record($request->user(), 'subject.created', $subject, newValues: $subject->getAttributes());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Subject created.']);

        return back();
    }

    public function update(SaveSubjectRequest $request, Subject $subject): RedirectResponse
    {
        $old = $subject->getAttributes();
        $subject->update($request->validated());
        $this->activityLogger->record($request->user(), 'subject.updated', $subject, $old, $subject->getAttributes());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Subject updated.']);

        return back();
    }
}
