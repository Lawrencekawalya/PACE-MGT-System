<?php

use App\Models\Permission;
use App\Models\Role;
use App\PermissionName;
use App\RoleName;
use Database\Seeders\AccessControlSeeder;

beforeEach(function () {
    $this->seed(AccessControlSeeder::class);
});

test('approved role permission matrix is seeded', function () {
    $teacher = Role::query()->where('name', RoleName::Teacher->value)->with('permissions')->sole();
    $paceOfficer = Role::query()->where('name', RoleName::PaceOfficer->value)->with('permissions')->sole();

    expect($teacher->permissions->pluck('name')->sort()->values()->all())->toBe(collect([
        PermissionName::RegisterStudents,
        PermissionName::AssignPaces,
        PermissionName::EnterTestResults,
        PermissionName::ApproveRetests,
        PermissionName::ViewAcademicReports,
        PermissionName::ViewPaceCatalogue,
    ])->map->value->sort()->values()->all())
        ->and($paceOfficer->display_name)->toBe('PACE Officer')
        ->and($paceOfficer->permissions->pluck('name')->sort()->values()->all())->toBe(collect([
            PermissionName::IssuePaces,
            PermissionName::AdjustInventory,
            PermissionName::ViewInventoryReports,
            PermissionName::ViewPaceCatalogue,
        ])->map->value->sort()->values()->all());
});

test('teachers and PACE Officers cannot manage administration screens', function (RoleName $role) {
    $staff = createStaffWithRole($role);

    $this->actingAs($staff)->get(route('admin.staff.index'))->assertForbidden();
    $this->actingAs($staff)->get(route('admin.school-settings.edit'))->assertForbidden();
})->with([
    'teacher' => RoleName::Teacher,
    'pace officer' => RoleName::PaceOfficer,
]);

test('optional inventory permission can be assigned directly to a teacher', function () {
    $teacher = createStaffWithRole(RoleName::Teacher);
    $teacher->directPermissions()->attach(
        Permission::query()->where('name', PermissionName::IssuePaces->value)->sole(),
    );

    expect($teacher->hasPermission(PermissionName::IssuePaces))->toBeTrue()
        ->and($teacher->hasPermission(PermissionName::AdjustInventory))->toBeFalse();
});
