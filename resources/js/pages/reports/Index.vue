<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { Download, FileSpreadsheet, Printer, RefreshCw } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, reactive } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { show as showInventoryItem } from '@/routes/inventory-items';
import { show as showAssignment } from '@/routes/pace-assignments';
import { download, store } from '@/routes/report-exports';
import { index } from '@/routes/reports';
import { show as showStudent } from '@/routes/students';

type Row = Record<string, any>;
type Export = {
    id: number;
    report_type: string;
    format: string;
    status: string;
    original_filename: string | null;
    row_count: number | null;
    expires_at: string | null;
    created_at: string;
};
type Filters = {
    academic_year_id?: number | null;
    term_id?: number | null;
    level_id?: number | null;
    learning_center_id?: number | null;
    course_id?: number | null;
    student_status?: string;
    assignment_status?: string;
    stock?: string;
    date_from?: string;
    date_to?: string;
};
const props = defineProps<{
    reportType: string;
    reportTypes: Array<{ value: string; label: string }>;
    rows: {
        data: Row[];
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    summary: Record<string, number>;
    filters: Filters;
    options: {
        academicYears: Array<{
            id: number;
            name: string;
            terms: Array<{ id: number; name: string }>;
        }>;
        levels: Array<{ id: number; name: string }>;
        learningCenters: Array<{ id: number; name: string }>;
        courses: Array<{ id: number; name: string }>;
        studentStatuses: Array<{ value: string; label: string }>;
        assignmentStatuses: Array<{ value: string; label: string }>;
    };
    exports: Export[];
}>();

const filters = reactive({
    academic_year_id: props.filters.academic_year_id?.toString() ?? '',
    term_id: props.filters.term_id?.toString() ?? '',
    level_id: props.filters.level_id?.toString() ?? '',
    learning_center_id: props.filters.learning_center_id?.toString() ?? '',
    course_id: props.filters.course_id?.toString() ?? '',
    student_status: props.filters.student_status ?? '',
    assignment_status: props.filters.assignment_status ?? '',
    stock: props.filters.stock ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
});
const selectedYear = computed(() =>
    props.options.academicYears.find(
        (year) => year.id.toString() === filters.academic_year_id,
    ),
);
const currentLabel = computed(
    () =>
        props.reportTypes.find((report) => report.value === props.reportType)
            ?.label ?? 'Reports',
);
let poller: ReturnType<typeof setInterval> | null = null;

function query(reportType = props.reportType): Record<string, string> {
    return Object.fromEntries(
        Object.entries({ report_type: reportType, ...filters }).filter(
            ([, value]) => value !== '',
        ),
    );
}
function applyFilters(): void {
    router.get(index().url, query(), { preserveState: true, replace: true });
}
function switchReport(reportType: string): void {
    router.get(index().url, query(reportType));
}
function resetFilters(): void {
    router.get(index({ query: { report_type: props.reportType } }));
}
function printReport(): void {
    window.print();
}
function summaryLabel(value: string): string {
    return value.replaceAll('_', ' ');
}
function statusVariant(
    status: string,
): 'outline' | 'secondary' | 'destructive' {
    if (status === 'failed' || status === 'Out of stock') {
        return 'destructive';
    }

    if (
        status === 'pending' ||
        status === 'processing' ||
        status === 'Low stock' ||
        status === 'Reversed'
    ) {
        return 'secondary';
    }

    return 'outline';
}
onMounted(() => {
    if (
        props.exports.some((item) =>
            ['pending', 'processing'].includes(item.status),
        )
    ) {
        poller = setInterval(() => router.reload({ only: ['exports'] }), 5000);
    }
});
onBeforeUnmount(() => {
    if (poller) {
        clearInterval(poller);
    }
});
defineOptions({
    layout: { breadcrumbs: [{ title: 'Reports', href: index() }] },
});
</script>

<template>
    <Head title="Reports" />
    <div class="flex max-w-[1600px] flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <Heading
                title="Reports"
                description="Academic progress, operational queues, and inventory oversight"
            />
            <Button class="print:hidden" variant="outline" @click="printReport">
                <Printer class="size-4" />Print
            </Button>
        </div>

        <div class="flex gap-1 overflow-x-auto border-b print:hidden">
            <button
                v-for="report in reportTypes"
                :key="report.value"
                type="button"
                class="border-b-2 px-4 py-2 text-sm font-medium whitespace-nowrap"
                :class="
                    report.value === reportType
                        ? 'border-primary text-foreground'
                        : 'border-transparent text-muted-foreground hover:text-foreground'
                "
                @click="switchReport(report.value)"
            >
                {{ report.label }}
            </button>
        </div>

        <form
            class="grid gap-2 border-b pb-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8 print:hidden"
            @submit.prevent="applyFilters"
        >
            <select
                v-if="reportType !== 'inventory'"
                v-model="filters.academic_year_id"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
                @change="filters.term_id = ''"
            >
                <option value="">All academic years</option>
                <option
                    v-for="year in options.academicYears"
                    :key="year.id"
                    :value="year.id"
                >
                    {{ year.name }}
                </option>
            </select>
            <select
                v-if="reportType !== 'inventory'"
                v-model="filters.term_id"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">All terms</option>
                <option
                    v-for="term in selectedYear?.terms ?? []"
                    :key="term.id"
                    :value="term.id"
                >
                    {{ term.name }}
                </option>
            </select>
            <select
                v-if="reportType !== 'inventory'"
                v-model="filters.level_id"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">All levels</option>
                <option
                    v-for="level in options.levels"
                    :key="level.id"
                    :value="level.id"
                >
                    {{ level.name }}
                </option>
            </select>
            <select
                v-if="reportType === 'pace_issuing'"
                v-model="filters.learning_center_id"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">All learning centres</option>
                <option
                    v-for="center in options.learningCenters"
                    :key="center.id"
                    :value="center.id"
                >
                    {{ center.name }}
                </option>
            </select>
            <select
                v-model="filters.course_id"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">All courses</option>
                <option
                    v-for="course in options.courses"
                    :key="course.id"
                    :value="course.id"
                >
                    {{ course.name }}
                </option>
            </select>
            <select
                v-if="
                    reportType === 'student_progress' ||
                    reportType === 'pending_work'
                "
                v-model="filters.student_status"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">Any student status</option>
                <option
                    v-for="status in options.studentStatuses"
                    :key="status.value"
                    :value="status.value"
                >
                    {{ status.label }}
                </option>
            </select>
            <select
                v-if="
                    reportType === 'student_progress' ||
                    reportType === 'pending_work'
                "
                v-model="filters.assignment_status"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">Any assignment status</option>
                <option
                    v-for="status in options.assignmentStatuses"
                    :key="status.value"
                    :value="status.value"
                >
                    {{ status.label }}
                </option>
            </select>
            <select
                v-if="reportType === 'inventory'"
                v-model="filters.stock"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">Any stock level</option>
                <option value="available">Available</option>
                <option value="low">Low stock</option>
                <option value="out">Out of stock</option>
            </select>
            <Input
                v-model="filters.date_from"
                type="date"
                aria-label="From date"
            />
            <Input v-model="filters.date_to" type="date" aria-label="To date" />
            <div class="flex gap-2 xl:col-span-2">
                <Button class="flex-1" type="submit" variant="secondary"
                    >Apply</Button
                >
                <Button
                    type="button"
                    size="icon"
                    variant="ghost"
                    title="Reset filters"
                    @click="resetFilters"
                >
                    <RefreshCw class="size-4" />
                </Button>
            </div>
        </form>

        <div
            class="grid grid-cols-2 gap-px overflow-hidden rounded-md border bg-border lg:grid-cols-4"
        >
            <div
                v-for="(value, key) in summary"
                :key="key"
                class="bg-background p-4"
            >
                <div class="text-2xl font-semibold">{{ value }}</div>
                <div class="text-xs text-muted-foreground capitalize">
                    {{ summaryLabel(String(key)) }}
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold">{{ currentLabel }}</h2>
                <p class="text-sm text-muted-foreground">
                    {{ rows.total }} matching records
                </p>
            </div>
            <Form
                v-bind="store.form()"
                class="flex gap-2 print:hidden"
                v-slot="{ processing }"
            >
                <input type="hidden" name="report_type" :value="reportType" />
                <input
                    v-for="(value, key) in filters"
                    :key="key"
                    type="hidden"
                    :name="key"
                    :value="value"
                />
                <Button
                    type="submit"
                    name="format"
                    value="csv"
                    variant="outline"
                    :disabled="processing"
                >
                    <Download class="size-4" />CSV
                </Button>
                <Button
                    type="submit"
                    name="format"
                    value="xlsx"
                    variant="outline"
                    :disabled="processing"
                >
                    <FileSpreadsheet class="size-4" />XLSX
                </Button>
            </Form>
        </div>

        <div class="overflow-x-auto rounded-md border">
            <table class="w-full min-w-5xl text-sm">
                <thead class="border-b bg-muted/40 text-left">
                    <tr v-if="reportType === 'student_progress'">
                        <th class="px-3 py-2">Student</th>
                        <th class="px-3 py-2">Level / course</th>
                        <th class="px-3 py-2">Current</th>
                        <th class="px-3 py-2 text-right">Curriculum</th>
                        <th class="px-3 py-2">Term target</th>
                        <th class="px-3 py-2 text-right">Progress</th>
                        <th class="px-3 py-2 text-right">Inactive</th>
                    </tr>
                    <tr v-else-if="reportType === 'course_progress'">
                        <th class="px-3 py-2">Level / course</th>
                        <th class="px-3 py-2 text-right">Students</th>
                        <th class="px-3 py-2 text-right">Completed PACEs</th>
                        <th class="px-3 py-2">Term target</th>
                        <th class="px-3 py-2 text-right">Average</th>
                        <th class="px-3 py-2 text-right">Failed/repeated</th>
                        <th class="px-3 py-2 text-right">Attention</th>
                    </tr>
                    <tr v-else-if="reportType === 'pending_work'">
                        <th class="px-3 py-2">Student</th>
                        <th class="px-3 py-2">Course / PACE</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2">Next action</th>
                        <th class="px-3 py-2">Waiting since</th>
                        <th class="px-3 py-2 text-right">Age</th>
                    </tr>
                    <tr v-else-if="reportType === 'pace_issuing'">
                        <th class="px-3 py-2">Student</th>
                        <th class="px-3 py-2">Learning centre / level</th>
                        <th class="px-3 py-2">Course / PACE</th>
                        <th class="px-3 py-2 text-right">Quantity</th>
                        <th class="px-3 py-2">Issued</th>
                        <th class="px-3 py-2">PACE Officer</th>
                    </tr>
                    <tr v-else>
                        <th class="px-3 py-2">Item</th>
                        <th class="px-3 py-2">Course / PACE</th>
                        <th class="px-3 py-2 text-right">On hand</th>
                        <th class="px-3 py-2 text-right">Received</th>
                        <th class="px-3 py-2 text-right">Issued</th>
                        <th class="px-3 py-2">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <template v-if="reportType === 'student_progress'">
                        <tr
                            v-for="row in rows.data"
                            :key="row.student_course_id"
                        >
                            <td class="px-3 py-3">
                                <Link
                                    class="font-medium hover:underline"
                                    :href="showStudent(row.student_id)"
                                    >{{ row.student }}</Link
                                >
                                <div
                                    class="font-mono text-xs text-muted-foreground"
                                >
                                    {{ row.admission_number }}
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                {{ row.level }}
                                <div class="text-xs text-muted-foreground">
                                    {{ row.course }}
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                <Link
                                    v-if="row.current_assignment_id"
                                    class="hover:underline"
                                    :href="
                                        showAssignment(
                                            row.current_assignment_id,
                                        )
                                    "
                                    >PACE {{ row.current_pace }}</Link
                                ><span v-else>PACE {{ row.current_pace }}</span>
                                <div class="text-xs text-muted-foreground">
                                    {{ row.assignment_status }}
                                </div>
                            </td>
                            <td class="px-3 py-3 text-right font-mono">
                                {{ row.completed_paces }} /
                                {{ row.sequence_total }}
                            </td>
                            <td class="px-3 py-3">
                                <template v-if="row.term_pace_target !== null">
                                    <div class="font-mono">
                                        {{ row.term_completed_paces }} /
                                        {{ row.term_pace_target }}
                                    </div>
                                    <Badge
                                        class="mt-1"
                                        :variant="
                                            row.term_target_status_key ===
                                            'below_target'
                                                ? 'destructive'
                                                : row.term_target_status_key ===
                                                    'on_track'
                                                  ? 'secondary'
                                                  : 'outline'
                                        "
                                    >
                                        {{ row.term_target_status }}
                                    </Badge>
                                    <div
                                        v-if="row.term_target_exceeded_by > 0"
                                        class="mt-1 text-xs text-muted-foreground"
                                    >
                                        +{{ row.term_target_exceeded_by }}
                                        additional
                                    </div>
                                    <div
                                        v-else-if="
                                            row.term_target_remaining > 0
                                        "
                                        class="mt-1 text-xs text-muted-foreground"
                                    >
                                        {{ row.term_target_remaining }}
                                        remaining
                                    </div>
                                </template>
                                <span v-else class="text-muted-foreground"
                                    >Not available</span
                                >
                            </td>
                            <td class="px-3 py-3 text-right font-mono">
                                {{ row.progress_percent }}%
                            </td>
                            <td class="px-3 py-3 text-right">
                                <Badge
                                    :variant="
                                        row.inactive ? 'destructive' : 'outline'
                                    "
                                    >{{ row.days_inactive }} days</Badge
                                >
                            </td>
                        </tr>
                    </template>
                    <template v-else-if="reportType === 'course_progress'">
                        <tr
                            v-for="row in rows.data"
                            :key="`${row.level_id}-${row.course_id}`"
                        >
                            <td class="px-3 py-3 font-medium">
                                {{ row.level }}
                                <div class="text-xs text-muted-foreground">
                                    {{ row.course }}
                                </div>
                            </td>
                            <td class="px-3 py-3 text-right font-mono">
                                {{ row.students }}
                            </td>
                            <td class="px-3 py-3 text-right font-mono">
                                {{ row.completed_paces }}
                            </td>
                            <td class="px-3 py-3">
                                <div class="font-mono">
                                    {{ row.term_completed_paces }} /
                                    {{ row.term_target_total }}
                                </div>
                                <div class="mt-1 text-xs text-muted-foreground">
                                    {{ row.students_target_achieved }} achieved
                                    · {{ row.students_on_track }} on track ·
                                    {{ row.students_below_target }} below
                                </div>
                            </td>
                            <td class="px-3 py-3 text-right font-mono">
                                {{ row.average_progress }}%
                            </td>
                            <td class="px-3 py-3 text-right font-mono">
                                {{ row.failed_cycles }}
                            </td>
                            <td class="px-3 py-3 text-right">
                                <Badge
                                    :variant="
                                        row.inactive_students
                                            ? 'destructive'
                                            : 'outline'
                                    "
                                    >{{ row.inactive_students }}</Badge
                                >
                            </td>
                        </tr>
                    </template>
                    <template v-else-if="reportType === 'pending_work'">
                        <tr v-for="row in rows.data" :key="row.assignment_id">
                            <td class="px-3 py-3">
                                <Link
                                    class="font-medium hover:underline"
                                    :href="showStudent(row.student_id)"
                                    >{{ row.student }}</Link
                                >
                                <div
                                    class="font-mono text-xs text-muted-foreground"
                                >
                                    {{ row.admission_number }}
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                <Link
                                    class="hover:underline"
                                    :href="showAssignment(row.assignment_id)"
                                    >{{ row.course }} · PACE
                                    {{ row.pace }}</Link
                                >
                                <div class="text-xs text-muted-foreground">
                                    {{ row.level }}
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                <Badge variant="outline">{{
                                    row.status
                                }}</Badge>
                            </td>
                            <td class="px-3 py-3">{{ row.next_action }}</td>
                            <td class="px-3 py-3">{{ row.waiting_since }}</td>
                            <td class="px-3 py-3 text-right">
                                <Badge
                                    :variant="
                                        row.overdue
                                            ? 'destructive'
                                            : 'secondary'
                                    "
                                    >{{ row.age_days }} days</Badge
                                >
                            </td>
                        </tr>
                    </template>
                    <template v-else-if="reportType === 'pace_issuing'">
                        <tr v-for="row in rows.data" :key="row.movement_id">
                            <td class="px-3 py-3">
                                <Link
                                    class="font-medium hover:underline"
                                    :href="showStudent(row.student_id)"
                                >
                                    {{ row.student }}
                                </Link>
                                <div
                                    class="font-mono text-xs text-muted-foreground"
                                >
                                    {{ row.admission_number }}
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                {{ row.learning_center }}
                                <div class="text-xs text-muted-foreground">
                                    {{ row.level }}
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                <Link
                                    class="hover:underline"
                                    :href="showAssignment(row.assignment_id)"
                                >
                                    {{ row.course }} · PACE {{ row.pace }}
                                </Link>
                                <div
                                    class="max-w-72 truncate text-xs text-muted-foreground"
                                >
                                    {{ row.pace_title || row.reference }}
                                </div>
                            </td>
                            <td
                                class="px-3 py-3 text-right font-mono font-semibold"
                            >
                                {{ row.quantity }}
                            </td>
                            <td class="px-3 py-3">
                                {{ new Date(row.issued_at).toLocaleString() }}
                                <div class="mt-1">
                                    <Badge :variant="statusVariant(row.status)">
                                        {{ row.status }}
                                    </Badge>
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                {{ row.issued_by }}
                                <div
                                    class="font-mono text-xs text-muted-foreground"
                                >
                                    {{ row.reference }}
                                </div>
                            </td>
                        </tr>
                    </template>
                    <template v-else>
                        <tr
                            v-for="row in rows.data"
                            :key="row.inventory_item_id"
                        >
                            <td class="px-3 py-3">
                                <Link
                                    class="font-mono font-medium hover:underline"
                                    :href="
                                        showInventoryItem(row.inventory_item_id)
                                    "
                                    >{{ row.sku }}</Link
                                >
                                <div class="text-xs text-muted-foreground">
                                    {{ row.item_type }}
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                {{ row.course }}
                                <div class="text-xs text-muted-foreground">
                                    PACE {{ row.pace }}
                                </div>
                            </td>
                            <td
                                class="px-3 py-3 text-right font-mono font-semibold"
                            >
                                {{ row.on_hand }}
                            </td>
                            <td class="px-3 py-3 text-right font-mono">
                                {{ row.received }}
                            </td>
                            <td class="px-3 py-3 text-right font-mono">
                                {{ row.issued }}
                            </td>
                            <td class="px-3 py-3">
                                <Badge
                                    :variant="statusVariant(row.stock_status)"
                                    >{{ row.stock_status }}</Badge
                                >
                            </td>
                        </tr>
                    </template>
                    <tr v-if="rows.data.length === 0">
                        <td
                            colspan="6"
                            class="px-4 py-14 text-center text-muted-foreground"
                        >
                            No records match these filters.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="flex items-center justify-between print:hidden">
            <Button
                variant="outline"
                :disabled="!rows.prev_page_url"
                @click="rows.prev_page_url && router.get(rows.prev_page_url)"
                >Previous</Button
            >
            <span class="text-sm text-muted-foreground"
                >{{ rows.total }} records</span
            >
            <Button
                variant="outline"
                :disabled="!rows.next_page_url"
                @click="rows.next_page_url && router.get(rows.next_page_url)"
                >Next</Button
            >
        </div>

        <section
            v-if="exports.length"
            class="space-y-3 border-t pt-5 print:hidden"
        >
            <h2 class="font-semibold">Recent exports</h2>
            <div class="divide-y rounded-md border">
                <div
                    v-for="item in exports"
                    :key="item.id"
                    class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm"
                >
                    <div>
                        <div class="font-medium">
                            {{
                                item.original_filename ||
                                summaryLabel(item.report_type)
                            }}
                        </div>
                        <div class="text-xs text-muted-foreground">
                            {{ item.format.toUpperCase() }} ·
                            {{ item.row_count ?? '—' }} rows ·
                            {{ new Date(item.created_at).toLocaleString() }}
                        </div>
                    </div>
                    <Badge :variant="statusVariant(item.status)">{{
                        item.status
                    }}</Badge>
                    <Button
                        v-if="item.status === 'completed'"
                        size="sm"
                        variant="outline"
                        as-child
                        ><a :href="download(item.id).url"
                            ><Download class="size-4" />Download</a
                        ></Button
                    >
                </div>
            </div>
        </section>
    </div>
</template>

<style>
@media print {
    aside,
    header {
        display: none !important;
    }
    main {
        padding: 0 !important;
    }
}
</style>
