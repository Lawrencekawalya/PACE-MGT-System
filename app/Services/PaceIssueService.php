<?php

namespace App\Services;

use App\Models\PaceAssignment;
use App\Models\User;
use App\PaceAssignmentStatus;
use Illuminate\Support\Facades\DB;

class PaceIssueService
{
    public function __construct(private PaceAssignmentService $assignments) {}

    public function issue(PaceAssignment $assignment, User $actor): PaceAssignment
    {
        return DB::transaction(function () use ($assignment, $actor): PaceAssignment {
            $assignment = $this->assignments->transition($assignment, PaceAssignmentStatus::InProgress, $actor);
            $assignment->forceFill(['issued_by' => $actor->id, 'issued_at' => now()])->save();

            return $assignment->refresh();
        }, 3);
    }
}
