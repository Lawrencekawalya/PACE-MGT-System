<?php

namespace App\Http\Controllers;

use App\Http\Requests\IssueScoreKeyRequestRequest;
use App\Http\Requests\RejectScoreKeyRequestRequest;
use App\Models\ScoreKeyRequest;
use App\Services\ScoreKeyRequestService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ScoreKeyRequestWorkflowController extends Controller
{
    public function __construct(private ScoreKeyRequestService $requests) {}

    public function issue(IssueScoreKeyRequestRequest $request, ScoreKeyRequest $scoreKeyRequest): RedirectResponse
    {
        $this->requests->issue(
            $scoreKeyRequest,
            (int) $request->validated('quantity'),
            $request->validated('notes'),
            $request->user(),
        );
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Score Key stock permanently issued to the Teacher.']);

        return back();
    }

    public function reject(RejectScoreKeyRequestRequest $request, ScoreKeyRequest $scoreKeyRequest): RedirectResponse
    {
        $this->requests->reject($scoreKeyRequest, $request->validated('reason'), $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Score Key request rejected.']);

        return back();
    }
}
