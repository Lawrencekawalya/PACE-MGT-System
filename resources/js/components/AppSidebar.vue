<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    CalendarRange,
    ClipboardCheck,
    FileSpreadsheet,
    GraduationCap,
    LayoutDashboard,
    Library,
    ListChecks,
    ListTree,
    PackageOpen,
    School,
    Settings2,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as academicPeriodsIndex } from '@/routes/admin/academic-periods';
import { index as catalogueImportsIndex } from '@/routes/admin/catalogue-imports';
import { index as catalogueSetupIndex } from '@/routes/admin/catalogue-setup';
import { index as curriculumIndex } from '@/routes/admin/curriculum';
import { index as pacesIndex } from '@/routes/admin/paces';
import { edit as editSchoolSettings } from '@/routes/admin/school-settings';
import { index as staffIndex } from '@/routes/admin/staff';
import { index as assessmentsIndex } from '@/routes/assessments';
import { index as inventoryIndex } from '@/routes/inventory';
import { index as paceAssignmentsIndex } from '@/routes/pace-assignments';
import { index as studentsIndex } from '@/routes/students';
import type { NavItem } from '@/types';

const page = usePage();
const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        { title: 'Dashboard', href: dashboard(), icon: LayoutDashboard },
    ];

    if (page.props.auth.permissions.includes('manage-staff')) {
        items.push({ title: 'Staff', href: staffIndex(), icon: Users });
    }

    if (page.props.auth.permissions.includes('manage-school-settings')) {
        items.push({
            title: 'School settings',
            href: editSchoolSettings(),
            icon: School,
        });
    }

    if (page.props.auth.permissions.includes('register-students')) {
        items.push({
            title: 'Students',
            href: studentsIndex(),
            icon: GraduationCap,
        });
    }

    if (
        page.props.auth.permissions.includes('assign-paces') ||
        page.props.auth.permissions.includes('issue-paces')
    ) {
        items.push({
            title: 'PACE work queue',
            href: paceAssignmentsIndex(),
            icon: ListChecks,
        });
    }

    if (
        page.props.auth.permissions.includes('enter-test-results') ||
        page.props.auth.permissions.includes('approve-retests') ||
        page.props.auth.permissions.includes('view-academic-reports')
    ) {
        items.push({
            title: 'Assessments',
            href: assessmentsIndex(),
            icon: ClipboardCheck,
        });
    }

    if (
        page.props.auth.permissions.includes('view-inventory-reports') ||
        page.props.auth.permissions.includes('adjust-inventory') ||
        page.props.auth.permissions.includes('issue-paces')
    ) {
        items.push({
            title: 'Inventory',
            href: inventoryIndex(),
            icon: PackageOpen,
        });
    }

    if (page.props.auth.permissions.includes('manage-academic-setup')) {
        items.push({
            title: 'Academic periods',
            href: academicPeriodsIndex(),
            icon: CalendarRange,
        });
        items.push({
            title: 'Catalogue setup',
            href: catalogueSetupIndex(),
            icon: Settings2,
        });
    }

    if (page.props.auth.permissions.includes('view-pace-catalogue')) {
        items.push({
            title: 'PACE catalogue',
            href: pacesIndex(),
            icon: Library,
        });
    }

    if (page.props.auth.permissions.includes('manage-pace-catalogue')) {
        items.push({
            title: 'Curriculum',
            href: curriculumIndex(),
            icon: ListTree,
        });
    }

    if (page.props.auth.permissions.includes('import-pace-catalogue')) {
        items.push({
            title: 'Catalogue imports',
            href: catalogueImportsIndex(),
            icon: FileSpreadsheet,
        });
    }

    return items;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
