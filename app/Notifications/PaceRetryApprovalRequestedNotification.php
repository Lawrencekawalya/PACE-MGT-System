<?php

namespace App\Notifications;

use App\Models\PaceRetryApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaceRetryApprovalRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(private PaceRetryApproval $approval) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $this->approval->loadMissing('assignment.pace', 'assignment.studentCourse.enrollment.student');
        $assignment = $this->approval->assignment;
        $student = $assignment->studentCourse->enrollment->student;

        return [
            'pace_retry_approval_id' => $this->approval->id,
            'message' => "{$this->approval->assessment_type->label()} retry {$this->approval->attempt_number} for {$student->full_name} requires approval.",
            'is_over_limit' => $this->approval->is_over_limit,
            'url' => route('assessments.index', ['approvals' => 'pending']),
        ];
    }
}
