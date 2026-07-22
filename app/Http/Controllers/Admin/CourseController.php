<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveCourseRequest;
use App\Models\Course;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class CourseController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function store(SaveCourseRequest $request): RedirectResponse
    {
        $course = Course::query()->create($request->validated());
        $this->activityLogger->record($request->user(), 'course.created', $course, newValues: $course->getAttributes());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Course created.']);

        return back();
    }

    public function update(SaveCourseRequest $request, Course $course): RedirectResponse
    {
        $old = $course->getAttributes();
        $course->update($request->validated());
        $this->activityLogger->record($request->user(), 'course.updated', $course, $old, $course->getAttributes());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Course updated.']);

        return back();
    }
}
