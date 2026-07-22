<?php

namespace App\Http\Controllers;

use App\AssessmentType;
use App\Http\Requests\StorePaceAttemptRequest;
use App\Models\PaceAssignment;
use App\Services\ActivityLogger;
use App\Services\PaceAssessmentService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class PaceAttemptController extends Controller
{
    public function __construct(private PaceAssessmentService $assessments, private ActivityLogger $activityLogger) {}

    public function store(StorePaceAttemptRequest $request, PaceAssignment $paceAssignment): RedirectResponse
    {
        $attempt = $this->assessments->finalize(
            $paceAssignment, AssessmentType::from($request->string('assessment_type')->toString()),
            $request->float('score'), $request->string('notes')->trim()->toString() ?: null, $request->user(),
        );
        $this->activityLogger->record($request->user(), 'pace-attempt.finalized', $attempt, newValues: $attempt->only(['assessment_type', 'attempt_number', 'score', 'pass_mark_used', 'outcome']));
        Inertia::flash('toast', ['type' => 'success', 'message' => "{$attempt->assessment_type->label()} result finalized as {$attempt->outcome->value}."]);

        return redirect()->route('pace-assignments.show', $paceAssignment);
    }
}
