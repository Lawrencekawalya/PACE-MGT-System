<?php

namespace App\Http\Controllers\Admin;

use App\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePromotionDecisionRequest;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\StudentEnrollment;
use App\RoleName;
use App\Services\ActivityLogger;
use App\Services\StudentPromotionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PromotionController extends Controller
{
    public function __construct(
        private StudentPromotionService $promotions,
        private ActivityLogger $activityLogger,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorizeAdministrator($request);
        $validated = $request->validate([
            'source_academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'target_academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'target_term_id' => ['nullable', 'integer', 'exists:terms,id'],
            'status' => ['nullable', Rule::in(['all', EnrollmentStatus::Active->value, EnrollmentStatus::Promoted->value, EnrollmentStatus::Retained->value, EnrollmentStatus::Transferred->value, EnrollmentStatus::Completed->value])],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $academicYears = AcademicYear::query()->with('terms')->orderBy('starts_on')->get();
        $sourceYear = $academicYears->firstWhere(
            'id',
            (int) ($validated['source_academic_year_id'] ?? AcademicYear::query()->where('is_active', true)->value('id')),
        );
        $targetYear = $academicYears
            ->where('is_closed', false)
            ->when($sourceYear !== null, fn ($years) => $years->where('starts_on', '>', $sourceYear->starts_on))
            ->firstWhere('id', (int) ($validated['target_academic_year_id'] ?? 0))
            ?? $academicYears
                ->where('is_closed', false)
                ->when($sourceYear !== null, fn ($years) => $years->where('starts_on', '>', $sourceYear->starts_on))
                ->first();
        $targetTerm = $targetYear?->terms
            ->where('is_closed', false)
            ->firstWhere('id', (int) ($validated['target_term_id'] ?? 0))
            ?? $targetYear?->terms->where('is_closed', false)->first();
        $levels = Level::query()
            ->where('is_active', true)
            ->whereHas('learningCenter', fn ($query) => $query->where('is_active', true))
            ->with('learningCenter:id,name')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'learning_center_id', 'name', 'sort_order']);
        $status = $validated['status'] ?? EnrollmentStatus::Active->value;

        $enrollmentModels = StudentEnrollment::query()
            ->when($sourceYear, fn ($query) => $query->where('academic_year_id', $sourceYear->id))
            ->when($sourceYear === null, fn ($query) => $query->whereRaw('1 = 0'))
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->whereHas('student', fn ($query) => $query
                    ->where('admission_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('other_names', 'like', "%{$search}%"));
            })
            ->with([
                'student:id,admission_number,first_name,last_name,other_names,status',
                'level:id,learning_center_id,name,sort_order',
                'level.learningCenter:id,name',
                'decisionMaker:id,name',
                'nextEnrollment:id,previous_enrollment_id,academic_year_id,term_id,level_id',
                'nextEnrollment.academicYear:id,name',
                'nextEnrollment.term:id,name',
                'nextEnrollment.level:id,learning_center_id,name',
                'nextEnrollment.level.learningCenter:id,name',
            ])
            ->orderBy(
                StudentEnrollment::query()->select('last_name')
                    ->from('students')
                    ->whereColumn('students.id', 'student_enrollments.student_id')
                    ->limit(1),
            )
            ->paginate(25)
            ->withQueryString();
        $enrollmentRows = $enrollmentModels->getCollection()
            ->map(fn (StudentEnrollment $enrollment): array => [
                'id' => $enrollment->id,
                'status' => $enrollment->status->value,
                'decision_at' => $enrollment->decision_at?->toIso8601String(),
                'decision_reason' => $enrollment->decision_reason,
                'decision_maker' => $enrollment->decisionMaker?->name,
                'student' => [
                    'id' => $enrollment->student->id,
                    'admission_number' => $enrollment->student->admission_number,
                    'name' => $enrollment->student->full_name,
                ],
                'level' => $enrollment->level->only(['id', 'name', 'sort_order']),
                'learning_center' => $enrollment->level->learningCenter?->name,
                'recommended_level_id' => $levels->first(
                    fn (Level $level): bool => $level->sort_order > $enrollment->level->sort_order,
                )?->id,
                'next_enrollment' => $enrollment->nextEnrollment === null ? null : [
                    'id' => $enrollment->nextEnrollment->id,
                    'academic_year' => $enrollment->nextEnrollment->academicYear->name,
                    'term' => $enrollment->nextEnrollment->term->name,
                    'level' => $enrollment->nextEnrollment->level->name,
                    'learning_center' => $enrollment->nextEnrollment->level->learningCenter?->name,
                ],
            ]);
        $enrollments = new LengthAwarePaginator(
            $enrollmentRows,
            $enrollmentModels->total(),
            $enrollmentModels->perPage(),
            $enrollmentModels->currentPage(),
            [
                'path' => $enrollmentModels->path(),
                'query' => $request->query(),
            ],
        );

        $summary = StudentEnrollment::query()
            ->when($sourceYear, fn ($query) => $query->where('academic_year_id', $sourceYear->id))
            ->when($sourceYear === null, fn ($query) => $query->whereRaw('1 = 0'))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return Inertia::render('admin/promotions/Index', [
            'academicYears' => $academicYears->map(fn (AcademicYear $year): array => [
                ...$year->only(['id', 'name', 'is_active', 'is_closed']),
                'starts_on' => $year->starts_on->toDateString(),
                'terms' => $year->terms->map(fn ($term): array => [
                    ...$term->only(['id', 'name', 'is_closed']),
                    'starts_on' => $term->starts_on->toDateString(),
                ])->values()->all(),
            ]),
            'levels' => $levels,
            'enrollments' => $enrollments,
            'summary' => [
                'pending' => (int) ($summary[EnrollmentStatus::Active->value] ?? 0),
                'promoted' => (int) ($summary[EnrollmentStatus::Promoted->value] ?? 0),
                'retained' => (int) ($summary[EnrollmentStatus::Retained->value] ?? 0),
                'transferred' => (int) ($summary[EnrollmentStatus::Transferred->value] ?? 0),
                'completed' => (int) ($summary[EnrollmentStatus::Completed->value] ?? 0),
            ],
            'filters' => [
                'source_academic_year_id' => $sourceYear?->id,
                'target_academic_year_id' => $targetYear?->id,
                'target_term_id' => $targetTerm?->id,
                'status' => $status,
                'search' => $validated['search'] ?? '',
            ],
        ]);
    }

    public function store(StorePromotionDecisionRequest $request, StudentEnrollment $enrollment): RedirectResponse
    {
        $oldValues = $enrollment->only(['status', 'decision_by', 'decision_at', 'decision_reason']);
        $enrollment = $this->promotions->decide($enrollment, $request->promotionData(), $request->user());
        $this->activityLogger->record(
            $request->user(),
            'student-enrollment.promotion-decided',
            $enrollment,
            oldValues: $oldValues,
            newValues: [
                ...$enrollment->only(['status', 'decision_by', 'decision_at', 'decision_reason']),
                'next_enrollment_id' => $enrollment->nextEnrollment?->id,
            ],
            reason: $enrollment->decision_reason,
        );
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Year-end decision recorded.']);

        return back();
    }

    private function authorizeAdministrator(Request $request): void
    {
        abort_unless($request->user()->hasRole(RoleName::Administrator), 403);
    }
}
