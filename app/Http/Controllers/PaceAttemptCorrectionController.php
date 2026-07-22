<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaceAttemptCorrectionRequest;
use App\Models\PaceAttempt;
use App\Services\ActivityLogger;
use App\Services\PaceAssessmentService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class PaceAttemptCorrectionController extends Controller
{
    public function __construct(private PaceAssessmentService $assessments, private ActivityLogger $activityLogger) {}

    public function store(StorePaceAttemptCorrectionRequest $request, PaceAttempt $paceAttempt): RedirectResponse
    {
        $correction = $this->assessments->correct($paceAttempt, $request->float('score'), $request->string('reason')->toString(), $request->user());
        $this->activityLogger->record($request->user(), 'pace-attempt.corrected', $paceAttempt, ['score' => $paceAttempt->score, 'outcome' => $paceAttempt->outcome->value], ['score' => $correction->score, 'outcome' => $correction->outcome->value], $correction->reason);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Assessment correction recorded without altering the original result.']);

        return back();
    }
}
