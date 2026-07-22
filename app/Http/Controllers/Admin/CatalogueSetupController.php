<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Level;
use App\Models\Subject;
use App\PermissionName;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CatalogueSetupController extends Controller
{
    public function __invoke(): Response
    {
        Gate::authorize(PermissionName::ManageAcademicSetup->value);

        return Inertia::render('admin/catalogue-setup/Index', [
            'levels' => Level::query()->withCount('curriculumRequirements')->orderBy('sort_order')->get(),
            'subjects' => Subject::query()->withCount('courses')->orderBy('name')->get(),
            'courses' => Course::query()->with('subject:id,name')->withCount('paces')->orderBy('name')->get(),
        ]);
    }
}
