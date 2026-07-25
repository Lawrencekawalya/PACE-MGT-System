<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ChevronDown, ChevronRight, Search, ShieldCheck } from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index, update } from '@/routes/tuition-clearances';

type StatusOption = { value: string; label: string };
type ClearanceRow = {
    id: number;
    student: { id: number; admission_number: string; name: string };
    level: string;
    learning_center: string;
    clearance: {
        status: string;
        status_label: string;
        reference: string | null;
        notes: string | null;
        recorded_at: string | null;
        recorded_by: string | null;
    };
    subjects_at_target: number;
    additional_pace_status: 'eligible' | 'restricted' | 'not_yet_required';
    course_progress: Array<{
        course: string;
        completed: number;
        target: number;
        status: string;
        status_label: string;
    }>;
    history: Array<{
        id: number;
        from_status: string | null;
        to_status: string;
        reference: string | null;
        notes: string | null;
        changed_by: string | null;
        changed_at: string;
    }>;
};
type Paginator = {
    data: ClearanceRow[];
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};
type AcademicYear = {
    id: number;
    name: string;
    terms: Array<{ id: number; name: string }>;
};

const props = defineProps<{
    enrollments: Paginator | null;
    summary: {
        students: number;
        fully_paid: number;
        partially_paid: number;
        unconfirmed: number;
    };
    filters: {
        academic_year_id: number | null;
        term_id: number | null;
        learning_center_id?: number | null;
        level_id?: number | null;
        status?: string | null;
        search?: string | null;
    };
    target: number;
    statuses: StatusOption[];
    options: {
        academicYears: AcademicYear[];
        learningCenters: Array<{ id: number; name: string }>;
        levels: Array<{ id: number; name: string }>;
    };
}>();

const filters = ref({
    academic_year_id: props.filters.academic_year_id?.toString() ?? '',
    term_id: props.filters.term_id?.toString() ?? '',
    learning_center_id: props.filters.learning_center_id?.toString() ?? '',
    level_id: props.filters.level_id?.toString() ?? '',
    status: props.filters.status ?? '',
    search: props.filters.search ?? '',
});
const expandedId = ref<number | null>(null);
const selectedYear = computed(() =>
    props.options.academicYears.find(
        (year) => year.id.toString() === filters.value.academic_year_id,
    ),
);

function applyFilters(): void {
    router.get(
        index().url,
        Object.fromEntries(
            Object.entries(filters.value).filter(([, value]) => value !== ''),
        ),
        { preserveState: true, replace: true },
    );
}

function resetFilters(): void {
    router.get(index());
}

function clearanceVariant(status: string): 'default' | 'secondary' | 'outline' {
    if (status === 'fully_paid') {
        return 'default';
    }

    return status === 'partially_paid' ? 'secondary' : 'outline';
}

function eligibilityLabel(
    status: ClearanceRow['additional_pace_status'],
): string {
    if (status === 'eligible') {
        return 'Additional PACEs eligible';
    }

    if (status === 'restricted') {
        return 'Additional PACEs restricted';
    }

    return 'Clearance not yet required';
}

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Tuition clearance', href: index() }],
    },
});
</script>

<template>
    <Head title="Tuition clearance" />

    <div class="flex max-w-[1600px] flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading
            title="Tuition clearance"
            description="Term clearance status and additional PACE eligibility"
        />

        <div
            class="grid grid-cols-2 gap-px overflow-hidden rounded-md border bg-border lg:grid-cols-4"
        >
            <div class="bg-background p-4">
                <div class="text-2xl font-semibold">{{ summary.students }}</div>
                <div class="text-xs text-muted-foreground">
                    Enrolled students
                </div>
            </div>
            <div class="bg-background p-4">
                <div class="text-2xl font-semibold">
                    {{ summary.fully_paid }}
                </div>
                <div class="text-xs text-muted-foreground">Fully paid</div>
            </div>
            <div class="bg-background p-4">
                <div class="text-2xl font-semibold">
                    {{ summary.partially_paid }}
                </div>
                <div class="text-xs text-muted-foreground">Partially paid</div>
            </div>
            <div class="bg-background p-4">
                <div class="text-2xl font-semibold text-muted-foreground">
                    {{ summary.unconfirmed }}
                </div>
                <div class="text-xs text-muted-foreground">Unconfirmed</div>
            </div>
        </div>

        <form
            class="grid gap-2 border-b pb-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7"
            @submit.prevent="applyFilters"
        >
            <select
                v-model="filters.academic_year_id"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
                @change="filters.term_id = ''"
            >
                <option value="">Academic year</option>
                <option
                    v-for="year in options.academicYears"
                    :key="year.id"
                    :value="year.id"
                >
                    {{ year.name }}
                </option>
            </select>
            <select
                v-model="filters.term_id"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">Active term</option>
                <option
                    v-for="term in selectedYear?.terms ?? []"
                    :key="term.id"
                    :value="term.id"
                >
                    {{ term.name }}
                </option>
            </select>
            <select
                v-model="filters.learning_center_id"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">All learning centers</option>
                <option
                    v-for="center in options.learningCenters"
                    :key="center.id"
                    :value="center.id"
                >
                    {{ center.name }}
                </option>
            </select>
            <select
                v-model="filters.level_id"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">All grades</option>
                <option
                    v-for="level in options.levels"
                    :key="level.id"
                    :value="level.id"
                >
                    {{ level.name }}
                </option>
            </select>
            <select
                v-model="filters.status"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">Any clearance</option>
                <option
                    v-for="status in statuses"
                    :key="status.value"
                    :value="status.value"
                >
                    {{ status.label }}
                </option>
            </select>
            <div class="relative">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="filters.search"
                    class="pl-9"
                    placeholder="Student or admission no."
                    aria-label="Search students"
                />
            </div>
            <div class="flex gap-2">
                <Button class="flex-1" type="submit" variant="secondary">
                    Filter
                </Button>
                <Button type="button" variant="ghost" @click="resetFilters">
                    Reset
                </Button>
            </div>
        </form>

        <div class="overflow-x-auto rounded-md border">
            <table class="w-full min-w-6xl text-sm">
                <thead class="border-b bg-muted/40 text-left">
                    <tr>
                        <th class="w-12 px-3 py-3">
                            <span class="sr-only">Details</span>
                        </th>
                        <th class="px-3 py-3">Student</th>
                        <th class="px-3 py-3">Learning center / grade</th>
                        <th class="px-3 py-3">Clearance</th>
                        <th class="px-3 py-3">Additional PACEs</th>
                        <th class="px-3 py-3">Last updated</th>
                        <th class="w-[34rem] px-3 py-3">Update clearance</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <template
                        v-for="enrollment in enrollments?.data ?? []"
                        :key="enrollment.id"
                    >
                        <tr>
                            <td class="px-3 py-3">
                                <Button
                                    type="button"
                                    size="icon"
                                    variant="ghost"
                                    :aria-label="`View ${enrollment.student.name} details`"
                                    @click="
                                        expandedId =
                                            expandedId === enrollment.id
                                                ? null
                                                : enrollment.id
                                    "
                                >
                                    <ChevronDown
                                        v-if="expandedId === enrollment.id"
                                        class="size-4"
                                    />
                                    <ChevronRight v-else class="size-4" />
                                </Button>
                            </td>
                            <td class="px-3 py-3">
                                <div class="font-medium">
                                    {{ enrollment.student.name }}
                                </div>
                                <div
                                    class="font-mono text-xs text-muted-foreground"
                                >
                                    {{ enrollment.student.admission_number }}
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                {{ enrollment.learning_center }}
                                <div class="text-xs text-muted-foreground">
                                    {{ enrollment.level }}
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                <Badge
                                    :variant="
                                        clearanceVariant(
                                            enrollment.clearance.status,
                                        )
                                    "
                                >
                                    {{ enrollment.clearance.status_label }}
                                </Badge>
                                <div
                                    v-if="enrollment.clearance.reference"
                                    class="mt-1 font-mono text-xs text-muted-foreground"
                                >
                                    {{ enrollment.clearance.reference }}
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                <Badge
                                    :variant="
                                        enrollment.additional_pace_status ===
                                        'restricted'
                                            ? 'destructive'
                                            : 'outline'
                                    "
                                >
                                    {{
                                        eligibilityLabel(
                                            enrollment.additional_pace_status,
                                        )
                                    }}
                                </Badge>
                                <div
                                    v-if="enrollment.subjects_at_target"
                                    class="mt-1 text-xs text-muted-foreground"
                                >
                                    {{ enrollment.subjects_at_target }}
                                    subject(s) at target
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                <template
                                    v-if="enrollment.clearance.recorded_at"
                                >
                                    {{
                                        new Date(
                                            enrollment.clearance.recorded_at,
                                        ).toLocaleDateString()
                                    }}
                                    <div class="text-xs text-muted-foreground">
                                        {{
                                            enrollment.clearance.recorded_by ??
                                            'Unknown'
                                        }}
                                    </div>
                                </template>
                                <span v-else class="text-muted-foreground"
                                    >Not recorded</span
                                >
                            </td>
                            <td class="px-3 py-3">
                                <Form
                                    v-bind="update.form(enrollment.id)"
                                    class="grid grid-cols-[9rem_1fr_1fr_auto] gap-2"
                                    v-slot="{ errors, processing }"
                                    :options="{ preserveScroll: true }"
                                >
                                    <input
                                        type="hidden"
                                        name="term_id"
                                        :value="filters.term_id"
                                    />
                                    <select
                                        name="status"
                                        class="h-9 rounded-md border bg-transparent px-2 text-sm"
                                        :value="enrollment.clearance.status"
                                    >
                                        <option
                                            v-for="status in statuses"
                                            :key="status.value"
                                            :value="status.value"
                                        >
                                            {{ status.label }}
                                        </option>
                                    </select>
                                    <Input
                                        name="reference"
                                        maxlength="100"
                                        placeholder="Reference"
                                        :default-value="
                                            enrollment.clearance.reference ?? ''
                                        "
                                    />
                                    <Input
                                        name="notes"
                                        maxlength="1000"
                                        placeholder="Internal note"
                                        :default-value="
                                            enrollment.clearance.notes ?? ''
                                        "
                                    />
                                    <Button
                                        type="submit"
                                        :disabled="processing"
                                    >
                                        Save
                                    </Button>
                                    <InputError
                                        class="col-span-full"
                                        :message="
                                            errors.status ||
                                            errors.reference ||
                                            errors.notes ||
                                            errors.term_id
                                        "
                                    />
                                </Form>
                            </td>
                        </tr>
                        <tr v-if="expandedId === enrollment.id">
                            <td colspan="7" class="bg-muted/20 px-5 py-5">
                                <div
                                    class="grid gap-6 lg:grid-cols-[1fr_1.2fr]"
                                >
                                    <section>
                                        <h3 class="font-medium">
                                            Subject term progress
                                        </h3>
                                        <div class="mt-3 divide-y border-y">
                                            <div
                                                v-for="course in enrollment.course_progress"
                                                :key="course.course"
                                                class="flex items-center justify-between gap-4 py-2.5"
                                            >
                                                <span>{{ course.course }}</span>
                                                <span
                                                    class="flex items-center gap-2"
                                                >
                                                    <span class="font-mono">
                                                        {{ course.completed }} /
                                                        {{ course.target }}
                                                    </span>
                                                    <Badge variant="outline">
                                                        {{
                                                            course.status_label
                                                        }}
                                                    </Badge>
                                                </span>
                                            </div>
                                            <div
                                                v-if="
                                                    enrollment.course_progress
                                                        .length === 0
                                                "
                                                class="py-4 text-muted-foreground"
                                            >
                                                No prescribed subjects.
                                            </div>
                                        </div>
                                    </section>
                                    <section>
                                        <h3 class="font-medium">
                                            Clearance history
                                        </h3>
                                        <div class="mt-3 divide-y border-y">
                                            <div
                                                v-for="event in enrollment.history"
                                                :key="event.id"
                                                class="py-2.5"
                                            >
                                                <div
                                                    class="flex flex-wrap items-center justify-between gap-2"
                                                >
                                                    <span class="font-medium">
                                                        {{
                                                            event.from_status ??
                                                            'Not recorded'
                                                        }}
                                                        →
                                                        {{ event.to_status }}
                                                    </span>
                                                    <span
                                                        class="text-xs text-muted-foreground"
                                                    >
                                                        {{
                                                            new Date(
                                                                event.changed_at,
                                                            ).toLocaleString()
                                                        }}
                                                    </span>
                                                </div>
                                                <div
                                                    class="mt-1 text-xs text-muted-foreground"
                                                >
                                                    {{
                                                        event.changed_by ??
                                                        'Unknown'
                                                    }}
                                                    <template
                                                        v-if="event.reference"
                                                    >
                                                        ·
                                                        {{ event.reference }}
                                                    </template>
                                                    <template
                                                        v-if="event.notes"
                                                    >
                                                        · {{ event.notes }}
                                                    </template>
                                                </div>
                                            </div>
                                            <div
                                                v-if="
                                                    enrollment.history
                                                        .length === 0
                                                "
                                                class="py-4 text-muted-foreground"
                                            >
                                                No clearance changes recorded.
                                            </div>
                                        </div>
                                    </section>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr v-if="(enrollments?.data.length ?? 0) === 0">
                        <td
                            colspan="7"
                            class="px-4 py-14 text-center text-muted-foreground"
                        >
                            <ShieldCheck class="mx-auto mb-3 size-6" />
                            No active students match these filters.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="enrollments"
            class="flex items-center justify-between gap-4 text-sm"
        >
            <span class="text-muted-foreground">
                {{ enrollments.total }} student{{
                    enrollments.total === 1 ? '' : 's'
                }}
            </span>
            <div class="flex gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!enrollments.prev_page_url"
                    as-child
                >
                    <Link
                        v-if="enrollments.prev_page_url"
                        :href="enrollments.prev_page_url"
                    >
                        Previous
                    </Link>
                    <span v-else>Previous</span>
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!enrollments.next_page_url"
                    as-child
                >
                    <Link
                        v-if="enrollments.next_page_url"
                        :href="enrollments.next_page_url"
                    >
                        Next
                    </Link>
                    <span v-else>Next</span>
                </Button>
            </div>
        </div>
    </div>
</template>
