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
import DashboardChart from '@/components/dashboard/DashboardChart.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { edit as editSchoolSettings } from '@/routes/admin/school-settings';
import { index as staffIndex } from '@/routes/admin/staff';
import { show as inventoryItemShow } from '@/routes/inventory-items';
import { index as paceAccountsIndex } from '@/routes/pace-accounts';
import { show as assignmentShow } from '@/routes/pace-assignments';
import { index as reportsIndex } from '@/routes/reports';

type Academic = {
    metrics: {
        active_students: number;
        active_assignments: number;
        pending_tests: number;
        pending_approvals: number;
        completed_this_week: number;
        overdue: number;
    };
    charts: {
        target_status_by_subject: {
            categories: string[];
            series: Array<{ name: string; data: number[] }>;
        };
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
    charts: {
        issuance_trend: {
            categories: string[];
            series: Array<{ name: string; data: number[] }>;
        };
        stock_status: {
            labels: string[];
            series: number[];
        };
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
type PaceAccounts = {
    period: {
        academic_year_id: number;
        academic_year: string;
        term_id: number;
        term: string;
    } | null;
    pace_cost: string;
    metrics: {
        students: number;
        total_balance: string;
        funded: number;
        insufficient: number;
        zero: number;
    };
    charts: {
        balance_status: {
            labels: string[];
            series: number[];
        };
        balance_by_center: {
            categories: string[];
            series: Array<{ name: string; data: number[] }>;
        };
    };
    queue: Array<{
        enrollment_id: number;
        student: string;
        admission_number: string;
        learning_center: string;
        level: string;
        balance: string;
        shortfall: string;
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
    paceAccounts: PaceAccounts | null;
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
const formatMoney = (amount: string | number): string =>
    new Intl.NumberFormat('en-UG', {
        style: 'currency',
        currency: 'UGX',
        maximumFractionDigits: 0,
    }).format(Number(amount));
const paceAccountMetrics = computed(() =>
    props.paceAccounts
        ? [
              {
                  label: 'Enrolled students',
                  value: props.paceAccounts.metrics.students,
              },
              {
                  label: 'PACE credit held',
                  value: formatMoney(props.paceAccounts.metrics.total_balance),
              },
              {
                  label: 'Can receive a PACE',
                  value: props.paceAccounts.metrics.funded,
              },
              {
                  label: 'Insufficient balance',
                  value: props.paceAccounts.metrics.insufficient,
                  attention: true,
              },
              {
                  label: 'Zero balance',
                  value: props.paceAccounts.metrics.zero,
                  critical: true,
              },
          ]
        : [],
);
const dashboardDescription = computed(() =>
    props.paceAccounts && !props.academic && !props.inventory
        ? 'Today’s student PACE account position and funding attention'
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

        <section v-if="paceAccounts" class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="font-semibold">PACE accounts</h2>
                    <p class="text-sm text-muted-foreground">
                        Uniform PACE cost:
                        {{
                            Number(paceAccounts.pace_cost) > 0
                                ? formatMoney(paceAccounts.pace_cost)
                                : 'Not configured'
                        }}
                    </p>
                </div>
                <Button size="sm" variant="outline" as-child>
                    <Link :href="paceAccountsIndex()">
                        <ReceiptText class="size-4" />
                        Open PACE accounts
                    </Link>
                </Button>
            </div>

            <div
                class="grid grid-cols-2 gap-px overflow-hidden rounded-md border bg-border md:grid-cols-3 xl:grid-cols-5"
            >
                <div
                    v-for="metric in paceAccountMetrics"
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

            <div
                class="grid gap-6 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]"
            >
                <DashboardChart
                    title="Issue readiness"
                    description="Active students by available PACE credit"
                    type="donut"
                    total-label="Students"
                    :labels="paceAccounts.charts.balance_status.labels"
                    :series="paceAccounts.charts.balance_status.series"
                    :colors="['#059669', '#d97706', '#dc2626']"
                />
                <DashboardChart
                    title="PACE credit by learning center"
                    description="Available student credit carried by each learning center"
                    type="bar"
                    :categories="
                        paceAccounts.charts.balance_by_center.categories
                    "
                    :series="paceAccounts.charts.balance_by_center.series"
                    :colors="['#2563eb']"
                />
            </div>

            <div>
                <div class="mb-2 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold">Funding attention</h3>
                        <p class="text-xs text-muted-foreground">
                            Students who cannot currently receive another PACE
                        </p>
                    </div>
                    <Link
                        class="text-sm text-primary hover:underline"
                        :href="
                            paceAccountsIndex({
                                query: { balance_status: 'insufficient' },
                            })
                        "
                    >
                        Review accounts
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
                                <th class="px-3 py-2 text-right">Balance</th>
                                <th class="px-3 py-2 text-right">Shortfall</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="item in paceAccounts.queue"
                                :key="item.enrollment_id"
                            >
                                <td class="px-3 py-2.5">
                                    <Link
                                        class="font-medium hover:underline"
                                        :href="
                                            paceAccountsIndex({
                                                query: {
                                                    search: item.admission_number,
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
                                <td class="px-3 py-2.5 text-right font-mono">
                                    {{ formatMoney(item.balance) }}
                                </td>
                                <td
                                    class="px-3 py-2.5 text-right font-mono text-destructive"
                                >
                                    {{ formatMoney(item.shortfall) }}
                                </td>
                            </tr>
                            <tr v-if="paceAccounts.queue.length === 0">
                                <td
                                    colspan="4"
                                    class="px-4 py-10 text-center text-muted-foreground"
                                >
                                    <ReceiptText class="mx-auto mb-2 size-5" />
                                    All active students have enough credit for
                                    another PACE.
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
            <DashboardChart
                title="Subject target status"
                description="Active student subjects against the current term PACE target"
                type="bar"
                stacked
                horizontal
                :categories="
                    academic.charts.target_status_by_subject.categories
                "
                :series="academic.charts.target_status_by_subject.series"
                :colors="['#059669', '#0891b2', '#dc2626']"
            />
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
            <div
                class="grid gap-6 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]"
            >
                <DashboardChart
                    title="PACE issuance trend"
                    description="Physical issues recorded over the last eight weeks"
                    type="line"
                    :categories="inventory.charts.issuance_trend.categories"
                    :series="inventory.charts.issuance_trend.series"
                    :colors="['#0891b2']"
                />
                <DashboardChart
                    title="Stock status"
                    description="Active inventory items by current stock position"
                    type="donut"
                    total-label="Inventory items"
                    :labels="inventory.charts.stock_status.labels"
                    :series="inventory.charts.stock_status.series"
                    :colors="['#059669', '#d97706', '#dc2626']"
                />
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
