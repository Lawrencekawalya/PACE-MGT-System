<?php

use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\Level;
use App\Models\Pace;
use App\Models\Subject;
use App\RoleName;
use Database\Seeders\AccessControlSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
});

test('administrator creates and audits catalogue records', function () {
    $administrator = createStaffWithRole(RoleName::Administrator);
    $subject = Subject::factory()->create(['name' => 'Mathematics']);
    $course = Course::factory()->create(['subject_id' => $subject->id, 'name' => 'Maths', 'code' => 'MATHS']);

    $this->actingAs($administrator)->post(route('admin.paces.store'), [
        'course_id' => $course->id, 'number' => '1001', 'title' => null, 'edition' => '', 'sequence_order' => 1001, 'is_active' => true,
    ])->assertRedirect();

    expect(Pace::query()->where('number', '1001')->exists())->toBeTrue()
        ->and(ActivityLog::query()->where('event', 'pace.created')->exists())->toBeTrue();
});

test('pace identity is unique by course number and edition', function () {
    $administrator = createStaffWithRole(RoleName::Administrator);
    $pace = Pace::factory()->create(['number' => '1001', 'edition' => '', 'sequence_order' => 1001]);

    $this->actingAs($administrator)->post(route('admin.paces.store'), [
        'course_id' => $pace->course_id, 'number' => '1001', 'edition' => '', 'sequence_order' => 1002, 'is_active' => true,
    ])->assertSessionHasErrors('number');
});

test('authorized staff can filter catalogue but cannot modify it', function () {
    $teacher = createStaffWithRole(RoleName::Teacher);
    $subject = Subject::factory()->create(['name' => 'Science']);
    $course = Course::factory()->create(['subject_id' => $subject->id, 'name' => 'Biology']);
    Pace::factory()->create(['course_id' => $course->id, 'number' => '1097', 'sequence_order' => 1097]);
    Level::factory()->create();

    $this->actingAs($teacher)->get(route('admin.paces.index', ['search' => '1097']))
        ->assertOk()->assertInertia(fn (Assert $page) => $page->component('admin/paces/Index')->has('paces.data', 1));

    $this->actingAs($teacher)->post(route('admin.paces.store'), [])->assertForbidden();
});
