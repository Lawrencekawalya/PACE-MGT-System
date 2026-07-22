<?php

namespace App;

enum PermissionName: string
{
    case ManageStaff = 'manage-staff';
    case ManageSchoolSettings = 'manage-school-settings';
    case ManageAcademicSetup = 'manage-academic-setup';
    case ManagePaceCatalogue = 'manage-pace-catalogue';
    case ImportPaceCatalogue = 'import-pace-catalogue';
    case ViewPaceCatalogue = 'view-pace-catalogue';
    case RegisterStudents = 'register-students';
    case AssignPaces = 'assign-paces';
    case IssuePaces = 'issue-paces';
    case EnterTestResults = 'enter-test-results';
    case ApproveRetests = 'approve-retests';
    case AdjustInventory = 'adjust-inventory';
    case ViewAcademicReports = 'view-academic-reports';
    case ViewInventoryReports = 'view-inventory-reports';

    public function label(): string
    {
        return match ($this) {
            self::ManageStaff => 'Manage staff',
            self::ManageSchoolSettings => 'Manage school settings',
            self::ManageAcademicSetup => 'Manage academic setup',
            self::ManagePaceCatalogue => 'Manage PACE catalogue',
            self::ImportPaceCatalogue => 'Import PACE catalogue',
            self::ViewPaceCatalogue => 'View PACE catalogue',
            self::RegisterStudents => 'Register and enroll students',
            self::AssignPaces => 'Assign PACEs academically',
            self::IssuePaces => 'Physically issue PACEs',
            self::EnterTestResults => 'Enter test results',
            self::ApproveRetests => 'Approve ordinary retests and repeats',
            self::AdjustInventory => 'Receive and adjust stock',
            self::ViewAcademicReports => 'View academic reports',
            self::ViewInventoryReports => 'View inventory reports',
        };
    }
}
