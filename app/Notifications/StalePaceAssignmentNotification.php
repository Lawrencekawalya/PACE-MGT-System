<?php

namespace App\Notifications;

use App\Models\PaceAssignment;
use App\NotificationCategory;
use App\NotificationPriority;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StalePaceAssignmentNotification extends Notification
{
    use Queueable;

    public function __construct(private PaceAssignment $assignment) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $this->assignment->loadMissing('pace', 'studentCourse.enrollment.student', 'studentCourse.course');
        $student = $this->assignment->studentCourse->enrollment->student;

        return [
            'pace_assignment_id' => $this->assignment->id,
            'category' => NotificationCategory::Academic->value,
            'priority' => NotificationPriority::Warning->value,
            'title' => 'PACE assignment needs attention',
            'message' => "PACE {$this->assignment->pace->number} for {$student->full_name} needs attention.",
            'status' => $this->assignment->status->value,
            'url' => route('pace-assignments.show', $this->assignment),
            'event_key' => "pace-assignment:{$this->assignment->id}:stale:".today()->toDateString(),
            'context' => ['pace_assignment_id' => $this->assignment->id],
        ];
    }
}
