<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    CheckCircle2,
    Circle,
    ClipboardCheck,
    PackageCheck,
    ReceiptText,
    School,
    ShieldCheck,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { edit as editSchoolSettings } from '@/routes/admin/school-settings';
import { index as staffIndex } from '@/routes/admin/staff';
import { show as inventoryItemShow } from '@/routes/inventory-items';
import { show as assignmentShow } from '@/routes/pace-assignments';
import { index as reportsIndex } from '@/routes/reports';
import { index as tuitionClearancesIndex } from '@/routes/tuition-clearances';

type Academic = {
    metrics: {
        active_students: number;
        active_assignments: number;
        pending_tests: number;
        pending_approvals: number;
        completed_this_week: number;
        overdue: number;
    };
    queue: Array<{
        id: number;
        student: string;
        admission_number: string;
        course: string;
        pace: string;
        status: string;
    }>;
};
type Inventory = {
    metrics: {
        on_hand: number;
        issued_this_week: number;
        low_stock: number;
        out_of_stock: number;
        awaiting_issue: number;
    };
    queue: Array<{
        id: number;
        sku: string;
        course: string;
        pace: string | null;
        on_hand: number;
        reorder_level: number;
    }>;
};
type Clearance = {
    period: {
        academic_year_id: number;
        academic_year: string;
        term_id: number;
        term: string;
    } | null;
    target: number;
    metrics: {
        students: number;
        fully_paid: number;
        partially_paid: number;
        unconfirmed: number;
        restricted: number;
        approaching_or_at_target: number;
    };
    queue: Array<{
        enrollment_id: number;
        student: string;
        admission_number: string;
        learning_center: string;
        level: string;
        course: string;
        completed: number;
        target: number;
        clearance_status: string;
        clearance_status_label: string;
        restricted: boolean;
    }>;
};
const props = defineProps<{
    setup: {
        school_settings: boolean;
        roles: boolean;
        administrator: boolean;
    } | null;
    academic: Academic | null;
    inventory: Inventory | null;
    clearance: Clearance | null;
}>();
const page = usePage();
const setupItems = computed(() => [
    {
        label: 'School profile configured',
        complete: props.setup?.school_settings ?? false,
        icon: School,
        href: editSchoolSettings(),
    },
    {
        label: 'Access roles installed',
        complete: props.setup?.roles ?? false,
        icon: ShieldCheck,
        href: staffIndex(),
    },
    {
        label: 'Active administrator available',
        complete: props.setup?.administrator ?? false,
        icon: Users,
        href: staffIndex(),
    },
]);
const academicMetrics = computed(() =>
    props.academic
        ? [
              {
                  label: 'Active students',
                  value: props.academic.metrics.active_students,
              },
              {
                  label: 'Active assignments',
                  value: props.academic.metrics.active_assignments,
              },
              {
                  label: 'Pending tests',
                  value: props.academic.metrics.pending_tests,
              },
              {
                  label: 'Retry approvals',
                  value: props.academic.metrics.pending_approvals,
              },
              {
                  label: 'Completed this week',
                  value: props.academic.metrics.completed_this_week,
              },
              {
                  label: 'Overdue actions',
                  value: props.academic.metrics.overdue,
                  attention: true,
              },
          ]
        : [],
);
const inventoryMetrics = computed(() =>
    props.inventory
        ? [
              {
                  label: 'Units on hand',
                  value: props.inventory.metrics.on_hand,
              },
              {
                  label: 'Issued this week',
                  value: props.inventory.metrics.issued_this_week,
              },
              {
                  label: 'Awaiting issue',
                  value: props.inventory.metrics.awaiting_issue,
              },
              {
                  label: 'Low stock',
                  value: props.inventory.metrics.low_stock,
                  attention: true,
              },
              {
                  label: 'Out of stock',
                  value: props.inventory.metrics.out_of_stock,
                  attention: true,
              },
          ]
        : [],
);
const clearanceMetrics = computed(() =>
    props.clearance
        ? [
              {
                  label: 'Enrolled students',
                  value: props.clearance.metrics.students,
              },
              {
                  label: 'Fully paid',
                  value: props.clearance.metrics.fully_paid,
              },
              {
                  label: 'Partially paid',
                  value: props.clearance.metrics.partially_paid,
                  attention: true,
              },
              {
                  label: 'Unconfirmed',
                  value: props.clearance.metrics.unconfirmed,
                  attention: true,
              },
              {
                  label: 'Restricted from extras',
                  value: props.clearance.metrics.restricted,
                  critical: true,
              },
              {
                  label: 'Near or at subject target',
                  value: props.clearance.metrics.approaching_or_at_target,
              },
          ]
        : [],
);
const dashboardDescription = computed(() =>
    props.clearance && !props.academic && !props.inventory
        ? 'Today’s tuition-clearance workload and additional PACE eligibility'
        : 'Today’s PACE operations and exceptions',
);
defineOptions({
    layout: { breadcrumbs: [{ title: 'Dashboard', href: dashboard() }] },
});
</script>

<template>
    <Head title="Dashboard" />
    <div class="flex max-w-[1500px] flex-1 flex-col gap-8 p-4 md:p-6">
        <Heading
            :title="`Welcome, ${page.props.auth.user.name}`"
            :description="dashboardDescription"
        />

        <section v-if="clearance" class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="font-semibold">Tuition clearance</h2>
                    <p class="text-sm text-muted-foreground">
                        <template v-if="clearance.period">
                            {{ clearance.period.academic_year }} ·
                            {{ clearance.period.term }} ·
                            {{ clearance.target }}-PACE subject target
                        </template>
                        <template v-else>
                            No active academic term is configured
                        </template>
                    </p>
                </div>
                <Button size="sm" variant="outline" as-child>
                    <Link :href="tuitionClearancesIndex()">
                        <ReceiptText class="size-4" />
                        Open clearance
                    </Link>
                </Button>
            </div>

            <div
                class="grid grid-cols-2 gap-px overflow-hidden rounded-md border bg-border md:grid-cols-3 xl:grid-cols-6"
            >
                <div
                    v-for="metric in clearanceMetrics"
                    :key="metric.label"
                    class="bg-background p-4"
                >
                    <div
                        class="flex items-center gap-2 text-2xl font-semibold"
                        :class="
                            metric.critical && metric.value
                                ? 'text-destructive'
                                : metric.attention && metric.value
                                  ? 'text-amber-700 dark:text-amber-400'
                                  : ''
                        "
                    >
                        <AlertTriangle
                            v-if="
                                (metric.critical || metric.attention) &&
                                metric.value
                            "
                            class="size-4"
                        />
                        {{ metric.value }}
                    </div>
                    <div class="text-xs text-muted-foreground">
                        {{ metric.label }}
                    </div>
                </div>
            </div>

            <div>
                <div class="mb-2 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold">
                            Clearance attention
                        </h3>
                        <p class="text-xs text-muted-foreground">
                            Unconfirmed or partially paid students near the
                            subject target
                        </p>
                    </div>
                    <Link
                        class="text-sm text-primary hover:underline"
                        :href="
                            tuitionClearancesIndex({
                                query: { status: 'unconfirmed' },
                            })
                        "
                    >
                        Review unconfirmed
                    </Link>
                </div>
                <div class="overflow-x-auto rounded-md border">
                    <table class="w-full min-w-4xl text-sm">
                        <thead class="border-b bg-muted/40 text-left">
                            <tr>
                                <th class="px-3 py-2">Student</th>
                                <th class="px-3 py-2">
                                    Learning center / grade
                                </th>
                                <th class="px-3 py-2">Subject progress</th>
                                <th class="px-3 py-2">Clearance</th>
                                <th class="px-3 py-2">Eligibility</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="item in clearance.queue"
                                :key="item.enrollment_id"
                            >
                                <td class="px-3 py-2.5">
                                    <Link
                                        class="font-medium hover:underline"
                                        :href="
                                            tuitionClearancesIndex({
                                                query: {
                                                    search: item.admission_number,
                                                    term_id:
                                                        clearance.period
                                                            ?.term_id,
                                                },
                                            })
                                        "
                                    >
                                        {{ item.student }}
                                    </Link>
                                    <div
                                        class="font-mono text-xs text-muted-foreground"
                                    >
                                        {{ item.admission_number }}
                                    </div>
                                </td>
                                <td class="px-3 py-2.5">
                                    {{ item.learning_center }}
                                    <div class="text-xs text-muted-foreground">
                                        {{ item.level }}
                                    </div>
                                </td>
                                <td class="px-3 py-2.5">
                                    {{ item.course }}
                                    <div
                                        class="font-mono text-xs text-muted-foreground"
                                    >
                                        {{ item.completed }} /
                                        {{ item.target }} passed
                                    </div>
                                </td>
                                <td class="px-3 py-2.5">
                                    <Badge variant="outline">
                                        {{ item.clearance_status_label }}
                                    </Badge>
                                </td>
                                <td class="px-3 py-2.5">
                                    <Badge
                                        :variant="
                                            item.restricted
                                                ? 'destructive'
                                                : 'secondary'
                                        "
                                    >
                                        {{
                                            item.restricted
                                                ? 'Additional PACEs restricted'
                                                : 'Approaching target'
                                        }}
                                    </Badge>
                                </td>
                            </tr>
                            <tr v-if="clearance.queue.length === 0">
                                <td
                                    colspan="5"
                                    class="px-4 py-10 text-center text-muted-foreground"
                                >
                                    <ReceiptText class="mx-auto mb-2 size-5" />
                                    No students currently require clearance
                                    attention.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section v-if="academic" class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="font-semibold">Academic operations</h2>
                    <p class="text-sm text-muted-foreground">
                        Progress, assessment, and follow-up workload
                    </p>
                </div>
                <Button size="sm" variant="outline" as-child
                    ><Link
                        :href="
                            reportsIndex({
                                query: { report_type: 'student_progress' },
                            })
                        "
                        >Open reports</Link
                    ></Button
                >
            </div>
            <div
                class="grid grid-cols-2 gap-px overflow-hidden rounded-md border bg-border md:grid-cols-3 xl:grid-cols-6"
            >
                <div
                    v-for="metric in academicMetrics"
                    :key="metric.label"
                    class="bg-background p-4"
                >
                    <div
                        class="flex items-center gap-2 text-2xl font-semibold"
                        :class="
                            metric.attention && metric.value
                                ? 'text-destructive'
                                : ''
                        "
                    >
                        <AlertTriangle
                            v-if="metric.attention && metric.value"
                            class="size-4"
                        />{{ metric.value }}
                    </div>
                    <div class="text-xs text-muted-foreground">
                        {{ metric.label }}
                    </div>
                </div>
            </div>
            <div>
                <div class="mb-2 flex items-center justify-between">
                    <h3 class="text-sm font-semibold">Overdue actions</h3>
                    <Link
                        class="text-sm text-primary hover:underline"
                        :href="
                            reportsIndex({
                                query: { report_type: 'pending_work' },
                            })
                        "
                        >View full queue</Link
                    >
                </div>
                <div class="overflow-x-auto rounded-md border">
                    <table class="w-full min-w-3xl text-sm">
                        <thead class="border-b bg-muted/40 text-left">
                            <tr>
                                <th class="px-3 py-2">Student</th>
                                <th class="px-3 py-2">Course</th>
                                <th class="px-3 py-2">PACE</th>
                                <th class="px-3 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="item in academic.queue" :key="item.id">
                                <td class="px-3 py-2">
                                    <Link
                                        class="font-medium hover:underline"
                                        :href="assignmentShow(item.id)"
                                        >{{ item.student }}</Link
                                    >
                                    <div
                                        class="font-mono text-xs text-muted-foreground"
                                    >
                                        {{ item.admission_number }}
                                    </div>
                                </td>
                                <td class="px-3 py-2">{{ item.course }}</td>
                                <td class="px-3 py-2 font-mono">
                                    {{ item.pace }}
                                </td>
                                <td class="px-3 py-2">
                                    <Badge variant="destructive">{{
                                        item.status
                                    }}</Badge>
                                </td>
                            </tr>
                            <tr v-if="academic.queue.length === 0">
                                <td
                                    colspan="4"
                                    class="px-4 py-10 text-center text-muted-foreground"
                                >
                                    <ClipboardCheck
                                        class="mx-auto mb-2 size-5"
                                    />No overdue academic actions.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section v-if="inventory" class="space-y-4 border-t pt-7">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="font-semibold">Inventory operations</h2>
                    <p class="text-sm text-muted-foreground">
                        Stock availability and replenishment exceptions
                    </p>
                </div>
                <Button size="sm" variant="outline" as-child
                    ><Link
                        :href="
                            reportsIndex({
                                query: { report_type: 'inventory' },
                            })
                        "
                        >Inventory report</Link
                    ></Button
                >
            </div>
            <div
                class="grid grid-cols-2 gap-px overflow-hidden rounded-md border bg-border md:grid-cols-5"
            >
                <div
                    v-for="metric in inventoryMetrics"
                    :key="metric.label"
                    class="bg-background p-4"
                >
                    <div
                        class="flex items-center gap-2 text-2xl font-semibold"
                        :class="
                            metric.attention && metric.value
                                ? 'text-destructive'
                                : ''
                        "
                    >
                        <AlertTriangle
                            v-if="metric.attention && metric.value"
                            class="size-4"
                        />{{ metric.value }}
                    </div>
                    <div class="text-xs text-muted-foreground">
                        {{ metric.label }}
                    </div>
                </div>
            </div>
            <div>
                <div class="mb-2 flex items-center justify-between">
                    <h3 class="text-sm font-semibold">Reorder queue</h3>
                    <Link
                        class="text-sm text-primary hover:underline"
                        :href="
                            reportsIndex({
                                query: {
                                    report_type: 'inventory',
                                    stock: 'low',
                                },
                            })
                        "
                        >View all low stock</Link
                    >
                </div>
                <div class="divide-y rounded-md border">
                    <Link
                        v-for="item in inventory.queue"
                        :key="item.id"
                        :href="inventoryItemShow(item.id)"
                        class="grid grid-cols-[1fr_auto] gap-4 px-4 py-3 text-sm hover:bg-muted/40"
                    >
                        <div>
                            <div class="font-mono font-medium">
                                {{ item.sku }}
                            </div>
                            <div class="text-xs text-muted-foreground">
                                {{ item.course
                                }}<span v-if="item.pace">
                                    · PACE {{ item.pace }}</span
                                >
                            </div>
                        </div>
                        <div class="text-right">
                            <div
                                class="font-mono font-semibold"
                                :class="
                                    item.on_hand === 0
                                        ? 'text-destructive'
                                        : 'text-amber-700'
                                "
                            >
                                {{ item.on_hand }}
                            </div>
                            <div class="text-xs text-muted-foreground">
                                Reorder {{ item.reorder_level }}
                            </div>
                        </div>
                    </Link>
                    <div
                        v-if="inventory.queue.length === 0"
                        class="px-4 py-10 text-center text-sm text-muted-foreground"
                    >
                        <PackageCheck class="mx-auto mb-2 size-5" />No stock
                        items require attention.
                    </div>
                </div>
            </div>
        </section>

        <section v-if="setup" class="max-w-4xl border-t pt-7">
            <div class="mb-3 flex items-center justify-between gap-4">
                <div>
                    <h2 class="font-semibold">System readiness</h2>
                    <p class="text-sm text-muted-foreground">
                        Foundation configuration checks
                    </p>
                </div>
                <span class="text-sm font-medium"
                    >{{ setupItems.filter((item) => item.complete).length }} / 3
                    complete</span
                >
            </div>
            <div class="divide-y rounded-md border">
                <div
                    v-for="item in setupItems"
                    :key="item.label"
                    class="flex min-h-14 items-center justify-between gap-4 px-4 py-3"
                >
                    <div class="flex items-center gap-3">
                        <CheckCircle2
                            v-if="item.complete"
                            class="size-5 text-emerald-600"
                        /><Circle
                            v-else
                            class="size-5 text-muted-foreground"
                        /><component
                            :is="item.icon"
                            class="size-4 text-muted-foreground"
                        /><span class="text-sm font-medium">{{
                            item.label
                        }}</span>
                    </div>
                    <Button variant="ghost" size="sm" as-child
                        ><Link :href="item.href">Open</Link></Button
                    >
                </div>
            </div>
        </section>
    </div>
</template>
