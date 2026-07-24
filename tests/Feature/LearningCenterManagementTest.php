<?php

use App\Models\AcademicYear;
use App\Models\LearningCenter;
use App\Models\Level;
use App\Models\StudentEnrollment;
use App\RoleName;
use Database\Seeders\AccessControlSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
});

test('administrator creates a center with several exclusive grades and teachers', function () {
    $administrator = createStaffWithRole(RoleName::Administrator);
    $teachers = collect([
        createStaffWithRole(RoleName::Teacher),
        createStaffWithRole(RoleName::Teacher),
    ]);
    $levels = Level::factory()->count(3)->create();

    $this->actingAs($administrator)->post(route('admin.learning-centers.store'), [
        'name' => 'Lower Learning Center',
        'code' => 'LOWER',
        'description' => 'Grades one through three.',
        'is_active' => true,
        'level_ids' => $levels->modelKeys(),
        'teacher_ids' => $teachers->pluck('id')->all(),
    ])->assertRedirect();

    $center = LearningCenter::query()->where('code', 'LOWER')->with(['levels', 'teachers'])->sole();

    expect($center->levels->modelKeys())->toEqualCanonicalizing($levels->modelKeys())
        ->and($center->teachers->modelKeys())->toEqualCanonicalizing($teachers->pluck('id')->all());

    $this->actingAs($administrator)->get(route('admin.learning-centers.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/learning-centers/Index')
            ->has('learningCenters', 1)
            ->has('levels', 3)
            ->has('teachers', 2));
});

test('a grade cannot be assigned to two learning centers', function () {
    $administrator = createStaffWithRole(RoleName::Administrator);
    $level = Level::factory()->create();
    $first = LearningCenter::factory()->create();
    $level->update(['learning_center_id' => $first->id]);

    $this->actingAs($administrator)->post(route('admin.learning-centers.store'), [
        'name' => 'Second Center',
        'code' => 'SECOND',
        'is_active' => true,
        'level_ids' => [$level->id],
        'teacher_ids' => [],
    ])->assertSessionHasErrors('level_ids');

    expect($level->fresh()->learning_center_id)->toBe($first->id);
});

test('only active teachers may be assigned to a learning center', function () {
    $administrator = createStaffWithRole(RoleName::Administrator);
    $storekeeper = createStaffWithRole(RoleName::PaceOfficer);

    $this->actingAs($administrator)->post(route('admin.learning-centers.store'), [
        'name' => 'Invalid Center',
        'code' => 'INVALID',
        'is_active' => true,
        'teacher_ids' => [$storekeeper->id],
    ])->assertSessionHasErrors('teacher_ids');
});

test('current student enrollments prevent unsafe grade removal', function () {
    $administrator = createStaffWithRole(RoleName::Administrator);
    $center = LearningCenter::factory()->create();
    $level = Level::factory()->create(['learning_center_id' => $center->id]);
    $year = AcademicYear::factory()->create(['is_active' => true]);
    StudentEnrollment::factory()->create([
        'learning_center_id' => $center->id,
        'level_id' => $level->id,
        'academic_year_id' => $year->id,
        'status' => 'active',
    ]);

    $this->actingAs($administrator)->put(route('admin.learning-centers.update', $center), [
        'name' => $center->name,
        'code' => $center->code,
        'description' => $center->description,
        'is_active' => true,
        'level_ids' => [],
        'teacher_ids' => [],
    ])->assertSessionHasErrors('level_ids');

    expect($level->fresh()->learning_center_id)->toBe($center->id);
});

test('assigning a legacy grade backfills only enrollments without a center snapshot', function () {
    $administrator = createStaffWithRole(RoleName::Administrator);
    $center = LearningCenter::factory()->create();
    $otherCenter = LearningCenter::factory()->create();
    $level = Level::factory()->create();
    $unknown = StudentEnrollment::factory()->create([
        'level_id' => $level->id,
        'learning_center_id' => null,
    ]);
    $historical = StudentEnrollment::factory()->create([
        'level_id' => $level->id,
        'learning_center_id' => $otherCenter->id,
    ]);
    $level->update(['learning_center_id' => null]);

    $this->actingAs($administrator)->put(route('admin.learning-centers.update', $center), [
        'name' => $center->name,
        'code' => $center->code,
        'is_active' => true,
        'level_ids' => [$level->id],
        'teacher_ids' => [],
    ])->assertRedirect();

    expect($unknown->fresh()->learning_center_id)->toBe($center->id)
        ->and($historical->fresh()->learning_center_id)->toBe($otherCenter->id);
});

test('teachers cannot manage learning centers', function () {
    $teacher = createStaffWithRole(RoleName::Teacher);

    $this->actingAs($teacher)->get(route('admin.learning-centers.index'))->assertForbidden();
    $this->actingAs($teacher)->post(route('admin.learning-centers.store'), [])->assertForbidden();
});
