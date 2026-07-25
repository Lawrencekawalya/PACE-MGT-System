<?php

namespace App\Http\Controllers;

use App\EnrollmentStatus;
use App\Http\Requests\UpdateTuitionClearanceRequest;
use App\Models\AcademicYear;
use App\Models\LearningCenter;
use App\Models\Level;
use App\Models\SchoolSetting;
use App\Models\StudentEnrollment;
use App\Models\Term;
use App\Models\TuitionClearance;
use App\PaceAssignmentStatus;
use App\PermissionName;
use App\Services\TermPaceTargetService;
use App\Services\TuitionClearanceService;
use App\TuitionClearanceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TuitionClearanceController extends Controller
{
    public function __construct(
        private TuitionClearanceService $clearances,
        private TermPaceTargetService $termTargets,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize(PermissionName::ManageTuitionClearance->value);
        $filters = $request->validate([
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'term_id' => ['nullable', 'integer', 'exists:terms,id'],
            'learning_center_id' => ['nullable', 'integer', 'exists:learning_centers,id'],
            'level_id' => ['nullable', 'integer', 'exists:levels,id'],
            'status' => ['nullable', Rule::enum(TuitionClearanceStatus::class)],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $academicYearId = (int) ($filters['academic_year_id']
            ?? AcademicYear::query()->where('is_active', true)->value('id'));
        $term = Term::query()
            ->where('academic_year_id', $academicYearId)
            ->when(
                $filters['term_id'] ?? null,
                fn ($query, $termId) => $query->whereKey($termId),
                fn ($query) => $query->where('is_active', true),
            )
            ->first();
        $filters['academic_year_id'] = $academicYearId ?: null;
        $filters['term_id'] = $term?->id;
        $target = SchoolSetting::current()->term_pace_target;

        $baseQuery = StudentEnrollment::query()
            ->where('academic_year_id', $academicYearId)
            ->where('status', EnrollmentStatus::Active);
        $this->applyRosterFilters($baseQuery, $filters);
        $summary = $term === null ? $this->emptySummary() : [
            'students' => (clone $baseQuery)->count(),
            'fully_paid' => $this->statusCount($baseQuery, $term, TuitionClearanceStatus::FullyPaid),
            'partially_paid' => $this->statusCount($baseQuery, $term, TuitionClearanceStatus::PartiallyPaid),
            'unconfirmed' => $this->statusCount($baseQuery, $term, TuitionClearanceStatus::Unconfirmed),
        ];

        $enrollments = null;
        if ($term !== null) {
            $query = clone $baseQuery;
            $this->applyClearanceStatusFilter($query, $term, $filters['status'] ?? null);
            $enrollments = $query
                ->with([
                    'student:id,admission_number,first_name,last_name,other_names',
                    'level:id,name',
                    'learningCenter:id,name',
                    'tuitionClearances' => fn ($query) => $query
                        ->where('term_id', $term->id)
                        ->with(['recordedBy:id,name', 'events.changedBy:id,name']),
                    'studentCourses' => fn ($query) => $query->with([
                        'course:id,name',
                        'paceAssignments' => fn ($query) => $query
                            ->where('status', PaceAssignmentStatus::Passed)
                            ->whereBetween('completed_at', [
                                $term->starts_on->copy()->startOfDay(),
                                $term->ends_on->copy()->endOfDay(),
                            ]),
                    ]),
                ])
                ->orderBy('level_id')
                ->orderBy('student_id')
                ->paginate(25)
                ->withQueryString()
                ->through(fn (StudentEnrollment $enrollment): array => $this->row($enrollment, $term, $target));
        }

        return Inertia::render('tuition-clearances/Index', [
            'enrollments' => $enrollments,
            'summary' => $summary,
            'filters' => $filters,
            'target' => $target,
            'statuses' => collect(TuitionClearanceStatus::cases())
                ->map(fn (TuitionClearanceStatus $status) => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ]),
            'options' => [
                'academicYears' => AcademicYear::query()
                    ->with(['terms:id,academic_year_id,name'])
                    ->latest('starts_on')
                    ->get(['id', 'name']),
                'learningCenters' => LearningCenter::query()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name']),
                'levels' => Level::query()
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get(['id', 'name']),
            ],
        ]);
    }

    public function update(
        UpdateTuitionClearanceRequest $request,
        StudentEnrollment $studentEnrollment,
    ): RedirectResponse {
        $validated = $request->validated();
        $status = TuitionClearanceStatus::from($validated['status']);
        $termId = (int) $validated['term_id'];
        $term = Term::query()->whereKey($termId)->firstOrFail();
        $this->clearances->record(
            $studentEnrollment,
            $term,
            $status,
            $validated['reference'] ?? null,
            $validated['notes'] ?? null,
            $request->user(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Tuition clearance updated to {$status->label()}.",
        ]);

        return back();
    }

    /** @param Builder<StudentEnrollment> $query
     * @param  array<string, mixed>  $filters
     */
    private function applyRosterFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['learning_center_id'] ?? null, fn ($query, $id) => $query->where('learning_center_id', $id))
            ->when($filters['level_id'] ?? null, fn ($query, $id) => $query->where('level_id', $id))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->whereHas(
                'student',
                fn ($query) => $query
                    ->where('admission_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('other_names', 'like', "%{$search}%"),
            ));
    }

    /** @param Builder<StudentEnrollment> $query */
    private function applyClearanceStatusFilter(
        Builder $query,
        Term $term,
        TuitionClearanceStatus|string|null $status,
    ): void {
        if ($status === null || $status === '') {
            return;
        }

        $status = $status instanceof TuitionClearanceStatus
            ? $status
            : TuitionClearanceStatus::from($status);
        if ($status === TuitionClearanceStatus::Unconfirmed) {
            $query->where(function ($query) use ($term): void {
                $query->whereDoesntHave(
                    'tuitionClearances',
                    fn ($query) => $query->where('term_id', $term->id),
                )->orWhereHas(
                    'tuitionClearances',
                    fn ($query) => $query->where('term_id', $term->id)
                        ->where('status', TuitionClearanceStatus::Unconfirmed),
                );
            });

            return;
        }

        $query->whereHas(
            'tuitionClearances',
            fn ($query) => $query->where('term_id', $term->id)->where('status', $status),
        );
    }

    /** @param Builder<StudentEnrollment> $baseQuery */
    private function statusCount(
        Builder $baseQuery,
        Term $term,
        TuitionClearanceStatus $status,
    ): int {
        $query = clone $baseQuery;
        $this->applyClearanceStatusFilter($query, $term, $status);

        return $query->count();
    }

    /** @return array{students: int, fully_paid: int, partially_paid: int, unconfirmed: int} */
    private function emptySummary(): array
    {
        return ['students' => 0, 'fully_paid' => 0, 'partially_paid' => 0, 'unconfirmed' => 0];
    }

    /** @return array<string, mixed> */
    private function row(StudentEnrollment $enrollment, Term $term, int $target): array
    {
        /** @var TuitionClearance|null $clearance */
        $clearance = $enrollment->tuitionClearances->first();
        $status = $clearance->status ?? TuitionClearanceStatus::Unconfirmed;
        $courseProgress = $enrollment->studentCourses->map(function ($studentCourse) use ($term, $target): array {
            $progress = $this->termTargets->summarize($studentCourse->paceAssignments, $term, $target);

            return [
                'course' => $studentCourse->course->name,
                'completed' => $progress['completed'],
                'target' => $progress['target'],
                'status' => $progress['status'],
                'status_label' => $progress['status_label'],
            ];
        })->values();
        $subjectsAtTarget = $courseProgress
            ->where('completed', '>=', $target)
            ->count();

        return [
            'id' => $enrollment->id,
            'student' => [
                'id' => $enrollment->student->id,
                'admission_number' => $enrollment->student->admission_number,
                'name' => $enrollment->student->full_name,
            ],
            'level' => $enrollment->level->name,
            'learning_center' => $enrollment->learningCenter->name ?? 'Unassigned',
            'clearance' => [
                'status' => $status->value,
                'status_label' => $status->label(),
                'reference' => $clearance?->reference,
                'notes' => $clearance?->notes,
                'recorded_at' => $clearance?->recorded_at?->toIso8601String(),
                'recorded_by' => $clearance?->recordedBy?->name,
            ],
            'subjects_at_target' => $subjectsAtTarget,
            'additional_pace_status' => match (true) {
                $status === TuitionClearanceStatus::FullyPaid => 'eligible',
                $subjectsAtTarget > 0 => 'restricted',
                default => 'not_yet_required',
            },
            'course_progress' => $courseProgress,
            'history' => $clearance?->events->take(10)->map(fn ($event): array => [
                'id' => $event->id,
                'from_status' => $event->from_status?->label(),
                'to_status' => $event->to_status->label(),
                'reference' => $event->reference,
                'notes' => $event->notes,
                'changed_by' => $event->changedBy?->name,
                'changed_at' => $event->changed_at->toIso8601String(),
            ])->values() ?? [],
        ];
    }
}
