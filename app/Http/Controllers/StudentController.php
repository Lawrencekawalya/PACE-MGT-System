<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\Student;
use App\Services\ActivityLogger;
use App\Services\StudentRegistrationService;
use App\StudentStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function __construct(private StudentRegistrationService $students, private ActivityLogger $activityLogger) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Student::class);
        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'status' => $request->string('status')->toString(),
            'level_id' => $request->integer('level_id') ?: null,
            'academic_year_id' => $request->integer('academic_year_id') ?: null,
        ];

        $students = Student::query()
            ->with(['enrollments' => fn ($query) => $query->with(['academicYear:id,name', 'level:id,name'])->latest('enrolled_on')])
            ->when($filters['search'], fn ($query, $search) => $query->where(fn ($query) => $query
                ->where('admission_number', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('other_names', 'like', "%{$search}%")
                ->orWhere('guardian_name', 'like', "%{$search}%")
                ->orWhere('guardian_phone', 'like', "%{$search}%")))
            ->when(in_array($filters['status'], array_column(StudentStatus::cases(), 'value'), true), fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['level_id'], fn ($query, $levelId) => $query->whereHas('enrollments', fn ($query) => $query->where('level_id', $levelId)))
            ->when($filters['academic_year_id'], fn ($query, $yearId) => $query->whereHas('enrollments', fn ($query) => $query->where('academic_year_id', $yearId)))
            ->orderBy('last_name')->orderBy('first_name')
            ->paginate(20)->withQueryString();

        return Inertia::render('students/Index', [
            'students' => $students,
            'filters' => $filters,
            'levels' => Level::query()->orderBy('sort_order')->get(['id', 'name']),
            'academicYears' => AcademicYear::query()->orderByDesc('starts_on')->get(['id', 'name']),
            'statuses' => collect(StudentStatus::cases())->map(fn (StudentStatus $status) => ['value' => $status->value, 'label' => $status->label()]),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Student::class);

        return Inertia::render('students/Create');
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $student = $this->students->create($request->validated());
        $this->activityLogger->record($request->user(), 'student.created', $student, newValues: $student->only(['admission_number', 'first_name', 'last_name', 'guardian_name', 'status']));
        Inertia::flash('toast', ['type' => 'success', 'message' => "Student {$student->admission_number} registered. Continue with enrolment."]);

        return redirect()->route('students.enrollments.create', $student);
    }

    public function show(Request $request, Student $student): Response
    {
        Gate::authorize('view', $student);
        $student->load(['enrollments' => fn ($query) => $query
            ->with([
                'academicYear:id,name', 'term:id,name', 'level:id,name',
                'studentCourses.course.subject:id,name', 'studentCourses.startingPace:id,number',
                'studentCourses.currentPace:id,number', 'studentCourses.assignedBy:id,name',
            ])->latest('enrolled_on')]);

        return Inertia::render('students/Show', [
            'student' => $student,
            'tab' => in_array($request->string('tab')->toString(), ['overview', 'enrollments', 'placements'], true)
                ? $request->string('tab')->toString() : 'overview',
            'statuses' => collect(StudentStatus::cases())->map(fn (StudentStatus $status) => ['value' => $status->value, 'label' => $status->label()]),
            'canAssign' => $request->user()?->can('assign-paces') ?? false,
        ]);
    }

    public function edit(Student $student): Response
    {
        Gate::authorize('update', $student);

        return Inertia::render('students/Edit', [
            'student' => [
                ...$student->only(['id', 'admission_number', 'first_name', 'last_name', 'other_names', 'gender', 'guardian_name', 'guardian_phone', 'guardian_email', 'notes']),
                'date_of_birth' => $student->date_of_birth?->toDateString(),
            ],
        ]);
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $old = $student->only(['first_name', 'last_name', 'other_names', 'date_of_birth', 'gender', 'guardian_name', 'guardian_phone', 'guardian_email', 'notes']);
        $student->update($request->validated());
        $this->activityLogger->record($request->user(), 'student.updated', $student, $old, $student->only(array_keys($old)));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Student profile updated.']);

        return redirect()->route('students.show', $student);
    }
}
