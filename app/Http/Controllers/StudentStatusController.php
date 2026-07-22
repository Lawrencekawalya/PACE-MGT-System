<?php

namespace App\Http\Controllers;

use App\EnrollmentStatus;
use App\Http\Requests\UpdateStudentStatusRequest;
use App\Models\Student;
use App\Models\StudentCourse;
use App\Services\ActivityLogger;
use App\StudentCourseStatus;
use App\StudentStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StudentStatusController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function __invoke(UpdateStudentStatusRequest $request, Student $student): RedirectResponse
    {
        $data = $request->validated();
        $old = ['status' => $student->status->value];
        DB::transaction(function () use ($data, $request, $student, $old): void {
            $student->update(['status' => $data['status']]);

            if ($student->status !== StudentStatus::Active) {
                $enrollmentStatus = $student->status === StudentStatus::Graduated
                    ? EnrollmentStatus::Completed
                    : EnrollmentStatus::Withdrawn;
                $courseStatus = $student->status === StudentStatus::Graduated
                    ? StudentCourseStatus::Completed
                    : StudentCourseStatus::Withdrawn;
                $activeEnrollments = $student->enrollments()->where('status', EnrollmentStatus::Active)->pluck('id');
                $student->enrollments()->whereIn('id', $activeEnrollments)->update(['status' => $enrollmentStatus]);
                StudentCourse::query()
                    ->whereIn('student_enrollment_id', $activeEnrollments)
                    ->where('status', StudentCourseStatus::Active)
                    ->update(['status' => $courseStatus]);
            }

            $this->activityLogger->record($request->user(), 'student.status-changed', $student, $old, ['status' => $student->status->value], $data['reason'] ?? null);
        });
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Student status updated.']);

        return back();
    }
}
