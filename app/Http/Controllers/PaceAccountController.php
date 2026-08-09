<?php

namespace App\Http\Controllers;

use App\EnrollmentStatus;
use App\Http\Requests\RecordPacePaymentRequest;
use App\Http\Requests\UpdatePaceCostRequest;
use App\Models\AcademicYear;
use App\Models\LearningCenter;
use App\Models\Level;
use App\Models\PaceAccountTransaction;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\PermissionName;
use App\RoleName;
use App\Services\PaceAccountService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PaceAccountController extends Controller
{
    public function __construct(private PaceAccountService $accounts) {}

    public function index(Request $request): Response
    {
        Gate::authorize(PermissionName::ManagePaceAccounts->value);
        $filters = $request->validate([
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'learning_center_id' => ['nullable', 'integer', 'exists:learning_centers,id'],
            'level_id' => ['nullable', 'integer', 'exists:levels,id'],
            'balance_status' => ['nullable', Rule::in(['funded', 'insufficient', 'zero'])],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $academicYearId = (int) ($filters['academic_year_id']
            ?? AcademicYear::query()->where('is_active', true)->value('id'));
        $filters['academic_year_id'] = $academicYearId ?: null;
        $settings = SchoolSetting::current();
        $paceCost = (string) $settings->pace_cost;
        $balanceSql = '(select coalesce(sum(pace_account_transactions.amount), 0) from pace_account_transactions where pace_account_transactions.student_id = student_enrollments.student_id)';

        $baseQuery = StudentEnrollment::query()
            ->where('academic_year_id', $academicYearId)
            ->where('status', EnrollmentStatus::Active);
        $this->applyRosterFilters($baseQuery, $filters);
        $totalStudents = (clone $baseQuery)->count();
        $studentIds = (clone $baseQuery)->pluck('student_id');
        $totalBalance = PaceAccountTransaction::query()
            ->whereIn('student_id', $studentIds)
            ->sum('amount');
        $funded = (clone $baseQuery)->whereRaw("{$balanceSql} >= ?", [$paceCost])->count();
        $zero = (clone $baseQuery)->whereRaw("{$balanceSql} <= 0")->count();

        $query = clone $baseQuery;
        $this->applyBalanceFilter($query, $filters['balance_status'] ?? null, $paceCost);
        $enrollments = $query
            ->with([
                'student:id,admission_number,first_name,last_name,other_names',
                'student.paceAccountTransactions' => fn ($query) => $query
                    ->with([
                        'recordedBy:id,name',
                        'paceAssignment.pace:id,number,title',
                    ])
                    ->latest('recorded_at')
                    ->limit(10),
                'level:id,name',
                'learningCenter:id,name',
            ])
            ->select('student_enrollments.*')
            ->selectRaw("{$balanceSql} as pace_balance")
            ->orderBy('level_id')
            ->orderBy('student_id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (StudentEnrollment $enrollment): array => $this->row($enrollment, $paceCost));

        return Inertia::render('pace-accounts/Index', [
            'enrollments' => $enrollments,
            'summary' => [
                'students' => $totalStudents,
                'total_balance' => number_format((float) $totalBalance, 2, '.', ''),
                'funded' => $funded,
                'insufficient' => $totalStudents - $funded,
                'zero' => $zero,
            ],
            'filters' => $filters,
            'paceCost' => $paceCost,
            'canSetPaceCost' => $request->user()->hasRole(RoleName::Accountant),
            'today' => now()->toDateString(),
            'options' => [
                'academicYears' => AcademicYear::query()->latest('starts_on')->get(['id', 'name']),
                'learningCenters' => LearningCenter::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
                'levels' => Level::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name']),
            ],
        ]);
    }

    public function storePayment(RecordPacePaymentRequest $request, Student $student): RedirectResponse
    {
        $validated = $request->validated();
        $this->accounts->recordPayment(
            $student,
            (string) $validated['amount'],
            Carbon::parse($validated['paid_on'])->startOfDay(),
            $validated['reference'] ?? null,
            $validated['notes'] ?? null,
            $request->user(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "PACE payment recorded for {$student->full_name}.",
        ]);

        return back();
    }

    public function updateCost(UpdatePaceCostRequest $request): RedirectResponse
    {
        $settings = $this->accounts->updatePaceCost(
            (string) $request->validated('pace_cost'),
            $request->user(),
        );
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Uniform PACE cost updated to UGX '.number_format((float) $settings->pace_cost, 0).'.',
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
    private function applyBalanceFilter(Builder $query, ?string $status, string $paceCost): void
    {
        match ($status) {
            'funded' => $query->whereRaw('(select coalesce(sum(pace_account_transactions.amount), 0) from pace_account_transactions where pace_account_transactions.student_id = student_enrollments.student_id) >= ?', [$paceCost]),
            'insufficient' => $query->whereRaw('(select coalesce(sum(pace_account_transactions.amount), 0) from pace_account_transactions where pace_account_transactions.student_id = student_enrollments.student_id) < ?', [$paceCost]),
            'zero' => $query->whereRaw('(select coalesce(sum(pace_account_transactions.amount), 0) from pace_account_transactions where pace_account_transactions.student_id = student_enrollments.student_id) <= 0'),
            default => null,
        };
    }

    /** @return array<string, mixed> */
    private function row(StudentEnrollment $enrollment, string $paceCost): array
    {
        $balance = (string) $enrollment->getAttribute('pace_balance');

        return [
            'id' => $enrollment->id,
            'student' => [
                'id' => $enrollment->student->id,
                'admission_number' => $enrollment->student->admission_number,
                'name' => $enrollment->student->full_name,
            ],
            'level' => $enrollment->level->name,
            'learning_center' => $enrollment->learning_center_id === null
                ? 'Unassigned'
                : $enrollment->learningCenter->name,
            'balance' => number_format((float) $balance, 2, '.', ''),
            'can_issue' => (float) $paceCost > 0 && (float) $balance >= (float) $paceCost,
            'paces_available' => (float) $paceCost > 0 ? (int) floor((float) $balance / (float) $paceCost) : 0,
            'transactions' => $enrollment->student->paceAccountTransactions->map(fn (PaceAccountTransaction $transaction): array => [
                'id' => $transaction->id,
                'type' => $transaction->type->value,
                'type_label' => $transaction->type->label(),
                'amount' => $transaction->amount,
                'balance_after' => $transaction->balance_after,
                'reference' => $transaction->reference,
                'notes' => $transaction->notes,
                'pace' => $transaction->paceAssignment?->pace?->number,
                'recorded_by' => $transaction->recordedBy?->name,
                'recorded_at' => $transaction->recorded_at->toIso8601String(),
            ])->values(),
        ];
    }
}
