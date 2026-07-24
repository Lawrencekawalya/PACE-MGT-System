<?php

namespace App;

enum ReportType: string
{
    case StudentProgress = 'student_progress';
    case CourseProgress = 'course_progress';
    case PendingWork = 'pending_work';
    case PaceIssuing = 'pace_issuing';
    case Inventory = 'inventory';

    public function label(): string
    {
        return match ($this) {
            self::StudentProgress => 'Student progress',
            self::CourseProgress => 'Course comparison',
            self::PendingWork => 'Pending and overdue',
            self::PaceIssuing => 'PACE issuing',
            self::Inventory => 'Inventory status',
        };
    }

    public function isInventory(): bool
    {
        return in_array($this, [self::PaceIssuing, self::Inventory], true);
    }
}
