<?php

use App\Models\Pace;
use App\Models\PaceAssignment;
use App\Models\Term;
use App\PaceAssignmentStatus;
use App\RoleName;
use App\Services\TermPaceTargetService;
use Database\Seeders\AccessControlSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
});

test('student progress exposes the active term target without limiting the next PACE', function () {
    $fixture = createReportFixture();
    $administrator = createStaffWithRole(RoleName::Administrator);

    $this->actingAs($administrator)
        ->get(route('students.show', [$fixture['student'], 'tab' => 'progress']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('students/Show')
            ->where('student.enrollments.0.student_courses.0.term_progress.term', 'Term 1')
            ->where('student.enrollments.0.student_courses.0.term_progress.completed', 1)
            ->where('student.enrollments.0.student_courses.0.term_progress.target', 4)
            ->where('student.enrollments.0.student_courses.0.term_progress.remaining', 3)
            ->has('student.enrollments.0.student_courses.0.pace_options', 3));
});

test('term target counts distinct PACEs by completion date and allows work beyond the minimum', function () {
    $fixture = createReportFixture();
    $previousTerm = Term::factory()->create([
        'academic_year_id' => $fixture['year']->id,
        'name' => 'Previous term',
        'starts_on' => now()->subDays(150),
        'ends_on' => now()->subDays(61),
    ]);
    $crossTermPace = Pace::factory()->create([
        'course_id' => $fixture['course']->id,
        'number' => '1004',
        'sequence_order' => 4,
    ]);
    PaceAssignment::factory()->create([
        'student_course_id' => $fixture['studentCourse']->id,
        'pace_id' => $crossTermPace->id,
        'academic_year_id' => $fixture['year']->id,
        'term_id' => $previousTerm->id,
        'status' => PaceAssignmentStatus::Passed,
        'attempt_cycle' => 1,
        'completed_at' => now()->subDay(),
    ]);
    PaceAssignment::factory()->create([
        'student_course_id' => $fixture['studentCourse']->id,
        'pace_id' => $fixture['paces'][0]->id,
        'academic_year_id' => $fixture['year']->id,
        'term_id' => $fixture['term']->id,
        'status' => PaceAssignmentStatus::Passed,
        'attempt_cycle' => 2,
        'completed_at' => now(),
    ]);
    PaceAssignment::factory()->create([
        'student_course_id' => $fixture['studentCourse']->id,
        'pace_id' => $fixture['paces'][2]->id,
        'academic_year_id' => $fixture['year']->id,
        'term_id' => $previousTerm->id,
        'status' => PaceAssignmentStatus::Passed,
        'completed_at' => now()->subDays(90),
    ]);

    $service = app(TermPaceTargetService::class);
    $assignments = $fixture['studentCourse']->paceAssignments()->get();
    $progress = $service->summarize($assignments, $fixture['term'], 4);

    expect($progress['completed'])->toBe(2)
        ->and($progress['remaining'])->toBe(2)
        ->and($progress['exceeded_by'])->toBe(0);

    foreach ([5, 6, 7] as $sequence) {
        $pace = Pace::factory()->create([
            'course_id' => $fixture['course']->id,
            'number' => (string) (1000 + $sequence),
            'sequence_order' => $sequence,
        ]);
        PaceAssignment::factory()->create([
            'student_course_id' => $fixture['studentCourse']->id,
            'pace_id' => $pace->id,
            'academic_year_id' => $fixture['year']->id,
            'term_id' => $fixture['term']->id,
            'status' => PaceAssignmentStatus::Passed,
            'completed_at' => now(),
        ]);
    }

    $progress = $service->summarize(
        $fixture['studentCourse']->paceAssignments()->get(),
        $fixture['term'],
        4,
    );

    expect($progress['completed'])->toBe(5)
        ->and($progress['remaining'])->toBe(0)
        ->and($progress['exceeded_by'])->toBe(1)
        ->and($progress['status'])->toBe('target_achieved')
        ->and($progress['status_label'])->toBe('Target achieved');
});
