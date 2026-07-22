<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveStudentEnrollmentRequest;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Level;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\PermissionName;
use App\Services\ActivityLogger;
use App\Services\StudentEnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class StudentEnrollmentController extends Controller
{
    public function __construct(private StudentEnrollmentService $enrollments, private ActivityLogger $activityLogger) {}

    public function create(Request $request, Student $student): Response
    {
        Gate::authorize(PermissionName::AssignPaces->value);
        Gate::authorize('update', $student);

        return Inertia::render('students/enrollments/Form', [
            ...$this->formOptions(),
            'student' => $student->only(['id', 'admission_number', 'first_name', 'last_name', 'other_names']),
            'enrollment' => null,
        ]);
    }

    public function store(SaveStudentEnrollmentRequest $request, Student $student): RedirectResponse
    {
        $enrollment = $this->enrollments->save($student, null, $request->validated(), $request->user());
        $this->activityLogger->record($request->user(), 'student-enrollment.created', $enrollment, newValues: $this->auditValues($enrollment));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Student enrolled and course placements saved.']);

        return redirect()->route('students.show', ['student' => $student, 'tab' => 'placements']);
    }

    public function edit(Request $request, Student $student, StudentEnrollment $enrollment): Response
    {
        Gate::authorize(PermissionName::AssignPaces->value);
        Gate::authorize('update', $student);
        $this->ensureEnrollmentOwner($student, $enrollment);
        $enrollment->load('studentCourses');

        return Inertia::render('students/enrollments/Form', [
            ...$this->formOptions(),
            'student' => $student->only(['id', 'admission_number', 'first_name', 'last_name', 'other_names']),
            'enrollment' => [
                ...$enrollment->only(['id', 'academic_year_id', 'term_id', 'level_id']),
                'enrolled_on' => $enrollment->enrolled_on->toDateString(),
                'courses' => $enrollment->studentCourses->map(fn ($placement) => $placement->only(['course_id', 'starting_pace_id', 'placement_reason', 'status'])),
            ],
        ]);
    }

    public function update(SaveStudentEnrollmentRequest $request, Student $student, StudentEnrollment $enrollment): RedirectResponse
    {
        $this->ensureEnrollmentOwner($student, $enrollment);
        $enrollment->load('studentCourses');
        $old = $this->auditValues($enrollment);
        $enrollment = $this->enrollments->save($student, $enrollment, $request->validated(), $request->user());
        $this->activityLogger->record($request->user(), 'student-enrollment.updated', $enrollment, $old, $this->auditValues($enrollment->load('studentCourses')));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Enrolment and course placements updated.']);

        return redirect()->route('students.show', ['student' => $student, 'tab' => 'placements']);
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'academicYears' => AcademicYear::query()->with(['terms' => fn ($query) => $query->where('is_closed', false)])->where('is_closed', false)->orderByDesc('starts_on')->get(),
            'levels' => Level::query()->where('is_active', true)->with([
                'curriculumRequirements' => fn ($query) => $query->where('is_active', true)->with([
                    'course' => fn ($query) => $query->with(['subject:id,name', 'paces' => fn ($query) => $query->where('is_active', true)]),
                    'paces' => fn ($query) => $query->where('is_active', true),
                ]),
            ])->orderBy('sort_order')->get(),
            'courses' => Course::query()->where('is_active', true)->with(['subject:id,name', 'paces' => fn ($query) => $query->where('is_active', true)])->orderBy('name')->get(),
            'today' => now()->toDateString(),
        ];
    }

    private function ensureEnrollmentOwner(Student $student, StudentEnrollment $enrollment): void
    {
        abort_unless($enrollment->student_id === $student->id, 404);
    }

    /** @return array<string, mixed> */
    private function auditValues(StudentEnrollment $enrollment): array
    {
        return [
            ...$enrollment->only(['student_id', 'academic_year_id', 'term_id', 'level_id', 'status', 'enrolled_on']),
            'courses' => $enrollment->studentCourses()->get(['course_id', 'starting_pace_id', 'current_pace_id', 'status'])->toArray(),
        ];
    }
}
