<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpenText,
    CalendarRange,
    ChartNoAxesCombined,
    ClipboardCheck,
    ClipboardList,
    FileSpreadsheet,
    GraduationCap,
    LandPlot,
    LayoutDashboard,
    Library,
    ListChecks,
    ListTree,
    PackageCheck,
    PackageOpen,
    Route,
    RefreshCw,
    ReceiptText,
    School,
    ServerCog,
    Settings2,
    Users,
    Warehouse,
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
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { dashboard, documentation } from '@/routes';
import { systemStatus } from '@/routes/admin';
import { index as academicPeriodsIndex } from '@/routes/admin/academic-periods';
import { index as catalogueImportsIndex } from '@/routes/admin/catalogue-imports';
import { index as catalogueSetupIndex } from '@/routes/admin/catalogue-setup';
import { index as curriculumIndex } from '@/routes/admin/curriculum';
import { index as learningCentersIndex } from '@/routes/admin/learning-centers';
import { index as pacesIndex } from '@/routes/admin/paces';
import { index as promotionsIndex } from '@/routes/admin/promotions';
import { edit as editSchoolSettings } from '@/routes/admin/school-settings';
import { index as staffIndex } from '@/routes/admin/staff';
import { index as assessmentsIndex } from '@/routes/assessments';
import { index as inventoryIndex } from '@/routes/inventory';
import { index as paceAccountsIndex } from '@/routes/pace-accounts';
import { index as paceAssignmentsIndex } from '@/routes/pace-assignments';
import { index as paceIssuingIndex } from '@/routes/pace-issuing';
import { index as purchaseOrdersIndex } from '@/routes/purchase-orders';
import { index as reordersIndex } from '@/routes/reorders';
import { index as reportsIndex } from '@/routes/reports';
import { index as studentsIndex } from '@/routes/students';
import { index as suppliersIndex } from '@/routes/suppliers';
import type { NavGroup, NavItem } from '@/types';

const page = usePage();
const { isCurrentUrl } = useCurrentUrl();
const hasPermission = (permission: string): boolean =>
    page.props.auth.permissions.includes(permission);

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        { title: 'Dashboard', href: dashboard(), icon: LayoutDashboard },
    ];

    if (
        hasPermission('view-academic-reports') ||
        hasPermission('view-inventory-reports')
    ) {
        items.push({
            title: 'Reports',
            href: reportsIndex(),
            icon: ChartNoAxesCombined,
        });
    }

    if (hasPermission('manage-tuition-clearance')) {
        items.push({
            title: 'PACE accounts',
            href: paceAccountsIndex(),
            icon: ReceiptText,
        });
    }

    return items;
});

const navGroups = computed<NavGroup[]>(() => {
    const studentLearning: NavItem[] = [];
    const inventoryAndOrders: NavItem[] = [];
    const schoolAdministration: NavItem[] = [];
    const catalogueAndCurriculum: NavItem[] = [];

    if (hasPermission('register-students')) {
        studentLearning.push({
            title: 'Students',
            href: studentsIndex(),
            icon: GraduationCap,
        });
    }

    if (hasPermission('assign-paces') || hasPermission('issue-paces')) {
        studentLearning.push({
            title: 'PACE work queue',
            href: paceAssignmentsIndex(),
            icon: ListChecks,
        });
    }

    if (
        hasPermission('enter-test-results') ||
        hasPermission('approve-retests') ||
        hasPermission('view-academic-reports')
    ) {
        studentLearning.push({
            title: 'Assessments',
            href: assessmentsIndex(),
            icon: ClipboardCheck,
        });
    }

    if (hasPermission('issue-paces')) {
        inventoryAndOrders.push({
            title: 'PACE issuing',
            href: paceIssuingIndex(),
            icon: PackageCheck,
        });
    }

    if (
        hasPermission('view-inventory-reports') ||
        hasPermission('adjust-inventory') ||
        hasPermission('issue-paces')
    ) {
        inventoryAndOrders.push({
            title: 'Inventory',
            href: inventoryIndex(),
            icon: PackageOpen,
        });
    }

    if (hasPermission('manage-purchase-orders')) {
        inventoryAndOrders.push({
            title: 'Reorder queue',
            href: reordersIndex(),
            icon: RefreshCw,
        });
    }

    if (
        hasPermission('manage-purchase-orders') ||
        hasPermission('approve-purchase-orders') ||
        hasPermission('receive-purchase-orders')
    ) {
        inventoryAndOrders.push({
            title: 'Purchase orders',
            href: purchaseOrdersIndex(),
            icon: ClipboardList,
        });
    }

    if (hasPermission('manage-suppliers')) {
        inventoryAndOrders.push({
            title: 'Suppliers',
            href: suppliersIndex(),
            icon: Warehouse,
        });
    }

    if (hasPermission('manage-staff')) {
        schoolAdministration.push({
            title: 'Staff',
            href: staffIndex(),
            icon: Users,
        });
    }

    if (hasPermission('manage-school-settings')) {
        schoolAdministration.push(
            {
                title: 'School settings',
                href: editSchoolSettings(),
                icon: School,
            },
            {
                title: 'System status',
                href: systemStatus(),
                icon: ServerCog,
            },
        );
    }

    if (hasPermission('manage-academic-setup')) {
        schoolAdministration.push(
            {
                title: 'Learning centers',
                href: learningCentersIndex(),
                icon: LandPlot,
            },
            {
                title: 'Academic periods',
                href: academicPeriodsIndex(),
                icon: CalendarRange,
            },
        );

        if (page.props.auth.roles.includes('administrator')) {
            schoolAdministration.push({
                title: 'Promotions',
                href: promotionsIndex(),
                icon: Route,
            });
        }

        catalogueAndCurriculum.push({
            title: 'Catalogue setup',
            href: catalogueSetupIndex(),
            icon: Settings2,
        });
    }

    if (hasPermission('view-pace-catalogue')) {
        catalogueAndCurriculum.push({
            title: 'PACE catalogue',
            href: pacesIndex(),
            icon: Library,
        });
    }

    if (hasPermission('manage-pace-catalogue')) {
        catalogueAndCurriculum.push({
            title: 'Curriculum',
            href: curriculumIndex(),
            icon: ListTree,
        });
    }

    if (hasPermission('import-pace-catalogue')) {
        catalogueAndCurriculum.push({
            title: 'Catalogue imports',
            href: catalogueImportsIndex(),
            icon: FileSpreadsheet,
        });
    }

    return [
        {
            title: 'Student learning',
            icon: GraduationCap,
            items: studentLearning,
        },
        {
            title: 'Inventory & orders',
            icon: Warehouse,
            items: inventoryAndOrders,
        },
        {
            title: 'School administration',
            icon: School,
            items: schoolAdministration,
        },
        {
            title: 'Catalogue & curriculum',
            icon: Library,
            items: catalogueAndCurriculum,
        },
    ].filter((group) => group.items.length > 0);
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
            <NavMain :items="mainNavItems" :groups="navGroups" />
        </SidebarContent>

        <SidebarFooter>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        as-child
                        :is-active="isCurrentUrl(documentation())"
                        tooltip="System guide"
                    >
                        <Link :href="documentation()" prefetch>
                            <BookOpenText />
                            <span>System guide</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
