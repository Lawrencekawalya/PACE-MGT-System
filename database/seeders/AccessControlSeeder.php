<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\PermissionName;
use App\RoleName;
use Illuminate\Database\Seeder;

class AccessControlSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (PermissionName::cases() as $permission) {
            Permission::query()->updateOrCreate(
                ['name' => $permission->value],
                ['display_name' => $permission->label()],
            );
        }

        $roles = collect(RoleName::cases())->mapWithKeys(function (RoleName $role): array {
            $model = Role::query()->updateOrCreate(
                ['name' => $role->value],
                ['display_name' => $role->label()],
            );

            return [$role->value => $model];
        });

        $rolePermissions = [
            RoleName::Administrator->value => PermissionName::cases(),
            RoleName::Teacher->value => [
                PermissionName::RegisterStudents,
                PermissionName::AssignPaces,
                PermissionName::EnterTestResults,
                PermissionName::ApproveRetests,
                PermissionName::ViewAcademicReports,
            ],
            RoleName::Storekeeper->value => [
                PermissionName::IssuePaces,
                PermissionName::AdjustInventory,
                PermissionName::ViewInventoryReports,
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            $roles[$roleName]->permissions()->sync(
                Permission::query()->whereIn('name', collect($permissions)->map->value)->pluck('id'),
            );
        }
    }
}
