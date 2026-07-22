<?php

use App\Models\AcademicYear;
use App\Models\Term;
use App\RoleName;
use Database\Seeders\AccessControlSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AccessControlSeeder::class);
});

test('administrator manages one active academic year and term', function () {
    $administrator = createStaffWithRole(RoleName::Administrator);
    $oldYear = AcademicYear::factory()->create(['name' => '2025', 'is_active' => true]);

    $this->actingAs($administrator)->post(route('admin.academic-years.store'), [
        'name' => '2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'is_active' => true, 'is_closed' => false,
    ])->assertRedirect();

    $year = AcademicYear::query()->where('name', '2026')->sole();
    expect($oldYear->fresh()->is_active)->toBeFalse()->and($year->is_active)->toBeTrue();

    Term::factory()->create(['academic_year_id' => $year->id, 'name' => 'Term 1', 'sort_order' => 1, 'is_active' => true]);
    $this->actingAs($administrator)->post(route('admin.academic-years.terms.store', $year), [
        'name' => 'Term 2', 'sort_order' => 2, 'starts_on' => '2026-05-01', 'ends_on' => '2026-08-15', 'is_active' => true, 'is_closed' => false,
    ])->assertRedirect();

    expect(Term::query()->where('is_active', true)->sole()->name)->toBe('Term 2');
});

test('term dates must fall within the academic year', function () {
    $administrator = createStaffWithRole(RoleName::Administrator);
    $year = AcademicYear::factory()->create(['starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'is_active' => true]);

    $this->actingAs($administrator)->post(route('admin.academic-years.terms.store', $year), [
        'name' => 'Outside', 'sort_order' => 1, 'starts_on' => '2025-12-01', 'ends_on' => '2026-03-01', 'is_active' => false, 'is_closed' => false,
    ])->assertSessionHasErrors('starts_on');
});

test('teacher cannot manage academic periods', function () {
    $this->actingAs(createStaffWithRole(RoleName::Teacher))->get(route('admin.academic-periods.index'))->assertForbidden();
});
