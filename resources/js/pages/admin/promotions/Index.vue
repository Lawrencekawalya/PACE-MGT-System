<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, RefreshCw, Save } from '@lucide/vue';
import { computed, reactive, watch } from 'vue';
import PromotionController from '@/actions/App/Http/Controllers/Admin/PromotionController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/admin/promotions';
import { show as showStudent } from '@/routes/students';

type AcademicYear = {
    id: number;
    name: string;
    starts_on: string;
    is_active: boolean;
    is_closed: boolean;
    terms: Array<{
        id: number;
        name: string;
        starts_on: string;
        is_closed: boolean;
    }>;
};
type Level = {
    id: number;
    name: string;
    sort_order: number;
    learning_center: { id: number; name: string };
};
type Enrollment = {
    id: number;
    status: string;
    decision_at: string | null;
    decision_reason: string | null;
    decision_maker: string | null;
    student: { id: number; admission_number: string; name: string };
    level: { id: number; name: string; sort_order: number };
    learning_center: string | null;
    recommended_level_id: number | null;
    next_enrollment: {
        id: number;
        academic_year: string;
        term: string;
        level: string;
        learning_center: string | null;
    } | null;
};
type Paginator = {
    data: Enrollment[];
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

const props = defineProps<{
    academicYears: AcademicYear[];
    levels: Level[];
    enrollments: Paginator;
    summary: Record<string, number>;
    filters: {
        source_academic_year_id: number | null;
        target_academic_year_id: number | null;
        target_term_id: number | null;
        status: string;
        search: string;
    };
}>();

const filters = reactive({
    source_academic_year_id:
        props.filters.source_academic_year_id?.toString() ?? '',
    target_academic_year_id:
        props.filters.target_academic_year_id?.toString() ?? '',
    target_term_id: props.filters.target_term_id?.toString() ?? '',
    status: props.filters.status,
    search: props.filters.search,
});
const decisions = reactive<Record<number, string>>({});
const targetLevels = reactive<Record<number, string>>({});

for (const enrollment of props.enrollments.data) {
    decisions[enrollment.id] = '';
    targetLevels[enrollment.id] =
        enrollment.recommended_level_id?.toString() ?? '';
}

const sourceYear = computed(() =>
    props.academicYears.find(
        (year) => year.id.toString() === filters.source_academic_year_id,
    ),
);
const targetYears = computed(() =>
    props.academicYears.filter(
        (year) =>
            !year.is_closed &&
            (!sourceYear.value || year.starts_on > sourceYear.value.starts_on),
    ),
);
const targetYear = computed(() =>
    targetYears.value.find(
        (year) => year.id.toString() === filters.target_academic_year_id,
    ),
);
const targetTerms = computed(
    () => targetYear.value?.terms.filter((term) => !term.is_closed) ?? [],
);

watch(
    () => filters.source_academic_year_id,
    () => {
        filters.target_academic_year_id = '';
        filters.target_term_id = '';
    },
);
watch(
    () => filters.target_academic_year_id,
    () => {
        if (
            !targetTerms.value.some(
                (term) => term.id.toString() === filters.target_term_id,
            )
        ) {
            filters.target_term_id = '';
        }
    },
);

function applyFilters(): void {
    router.get(
        index().url,
        Object.fromEntries(
            Object.entries(filters).filter(([, value]) => value !== ''),
        ),
        { preserveState: true, replace: true },
    );
}
function resetFilters(): void {
    router.get(index());
}
function statusLabel(status: string): string {
    return status.replaceAll('_', ' ');
}
function needsNextEnrollment(decision: string): boolean {
    return decision === 'promoted' || decision === 'retained';
}
function submissionDisabled(enrollment: Enrollment, processing: boolean) {
    const decision = decisions[enrollment.id];

    return (
        processing ||
        decision === '' ||
        (needsNextEnrollment(decision) &&
            (filters.target_academic_year_id === '' ||
                filters.target_term_id === '')) ||
        (decision === 'promoted' && targetLevels[enrollment.id] === '')
    );
}
function confirmDecision(event: Event, enrollment: Enrollment): void {
    const decision = statusLabel(decisions[enrollment.id]);

    if (
        !window.confirm(
            `Record ${decision} as the final year-end decision for ${enrollment.student.name}?`,
        )
    ) {
        event.preventDefault();
    }
}

defineOptions({
    layout: { breadcrumbs: [{ title: 'Promotions', href: index() }] },
});
</script>

<template>
    <Head title="Promotions" />
    <div class="flex max-w-[1600px] flex-1 flex-col gap-7 p-4 md:p-6">
        <Heading
            title="Year-end promotions"
            description="Review enrollment outcomes and prepare the next academic year"
        />

        <form
            class="grid gap-4 border-b pb-6 md:grid-cols-2 xl:grid-cols-6"
            @submit.prevent="applyFilters"
        >
            <div class="grid gap-2">
                <Label for="source-year">Source year</Label>
                <select
                    id="source-year"
                    v-model="filters.source_academic_year_id"
                    class="h-9 rounded-md border bg-transparent px-3 text-sm"
                >
                    <option value="">Select year</option>
                    <option
                        v-for="year in academicYears"
                        :key="year.id"
                        :value="year.id"
                    >
                        {{ year.name }}
                    </option>
                </select>
            </div>
            <div class="grid gap-2">
                <Label for="target-year">Target year</Label>
                <select
                    id="target-year"
                    v-model="filters.target_academic_year_id"
                    class="h-9 rounded-md border bg-transparent px-3 text-sm"
                >
                    <option value="">Select year</option>
                    <option
                        v-for="year in targetYears"
                        :key="year.id"
                        :value="year.id"
                    >
                        {{ year.name }}
                    </option>
                </select>
            </div>
            <div class="grid gap-2">
                <Label for="target-term">Target term</Label>
                <select
                    id="target-term"
                    v-model="filters.target_term_id"
                    class="h-9 rounded-md border bg-transparent px-3 text-sm"
                >
                    <option value="">Select term</option>
                    <option
                        v-for="term in targetTerms"
                        :key="term.id"
                        :value="term.id"
                    >
                        {{ term.name }}
                    </option>
                </select>
            </div>
            <div class="grid gap-2">
                <Label for="decision-status">Decision status</Label>
                <select
                    id="decision-status"
                    v-model="filters.status"
                    class="h-9 rounded-md border bg-transparent px-3 text-sm capitalize"
                >
                    <option value="active">Pending</option>
                    <option value="promoted">Promoted</option>
                    <option value="retained">Retained</option>
                    <option value="transferred">Transferred</option>
                    <option value="completed">Completed</option>
                    <option value="all">All decisions</option>
                </select>
            </div>
            <div class="grid gap-2">
                <Label for="promotion-search">Student</Label>
                <Input
                    id="promotion-search"
                    v-model="filters.search"
                    placeholder="Name or admission number"
                />
            </div>
            <div class="flex items-end gap-2">
                <Button type="submit" class="flex-1">Apply</Button>
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
            class="grid grid-cols-2 gap-px overflow-hidden rounded-md border bg-border lg:grid-cols-5"
        >
            <div
                v-for="(value, key) in summary"
                :key="key"
                class="bg-background p-4"
            >
                <div class="text-2xl font-semibold">{{ value }}</div>
                <div class="text-xs text-muted-foreground capitalize">
                    {{ statusLabel(String(key)) }}
                </div>
            </div>
        </div>

        <div class="overflow-x-auto rounded-md border">
            <table class="w-full min-w-[1180px] text-sm">
                <thead class="border-b bg-muted/40 text-left">
                    <tr>
                        <th class="px-4 py-3">Student</th>
                        <th class="px-4 py-3">Current placement</th>
                        <th class="px-4 py-3">Decision</th>
                        <th class="px-4 py-3">Next placement</th>
                        <th class="px-4 py-3">Reason and confirmation</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="enrollment in enrollments.data"
                        :key="enrollment.id"
                        class="align-top"
                    >
                        <td class="px-4 py-4">
                            <Link
                                :href="showStudent(enrollment.student.id)"
                                class="font-medium hover:underline"
                            >
                                {{ enrollment.student.name }}
                            </Link>
                            <div
                                class="font-mono text-xs text-muted-foreground"
                            >
                                {{ enrollment.student.admission_number }}
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="font-medium">
                                {{ enrollment.level.name }}
                            </div>
                            <div class="text-xs text-muted-foreground">
                                {{ enrollment.learning_center || 'Unassigned' }}
                            </div>
                        </td>

                        <template v-if="enrollment.status === 'active'">
                            <td class="px-4 py-4">
                                <select
                                    v-model="decisions[enrollment.id]"
                                    :form="`promotion-${enrollment.id}`"
                                    name="decision"
                                    class="h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                                    required
                                >
                                    <option value="">Select decision</option>
                                    <option value="promoted">Promoted</option>
                                    <option value="retained">Retained</option>
                                    <option value="transferred">
                                        Transferred
                                    </option>
                                    <option value="completed">
                                        Completed programme
                                    </option>
                                </select>
                            </td>
                            <td class="px-4 py-4">
                                <select
                                    v-if="
                                        decisions[enrollment.id] === 'promoted'
                                    "
                                    v-model="targetLevels[enrollment.id]"
                                    :form="`promotion-${enrollment.id}`"
                                    name="target_level_id"
                                    class="h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                                    required
                                >
                                    <option value="">Select next grade</option>
                                    <option
                                        v-for="level in levels"
                                        :key="level.id"
                                        :value="level.id"
                                        :disabled="
                                            level.sort_order <=
                                            enrollment.level.sort_order
                                        "
                                    >
                                        {{ level.name }} /
                                        {{ level.learning_center.name }}
                                    </option>
                                </select>
                                <template
                                    v-else-if="
                                        decisions[enrollment.id] === 'retained'
                                    "
                                >
                                    <input
                                        :form="`promotion-${enrollment.id}`"
                                        type="hidden"
                                        name="target_level_id"
                                        :value="enrollment.level.id"
                                    />
                                    <span class="font-medium">
                                        {{ enrollment.level.name }}
                                    </span>
                                    <div class="text-xs text-muted-foreground">
                                        Same grade
                                    </div>
                                </template>
                                <span v-else class="text-muted-foreground">
                                    No next enrollment
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <Form
                                    :id="`promotion-${enrollment.id}`"
                                    v-bind="
                                        PromotionController.store.form(
                                            enrollment.id,
                                        )
                                    "
                                    class="grid gap-2"
                                    v-slot="{ errors, processing }"
                                    @submit="
                                        confirmDecision($event, enrollment)
                                    "
                                >
                                    <input
                                        type="hidden"
                                        name="target_academic_year_id"
                                        :value="
                                            needsNextEnrollment(
                                                decisions[enrollment.id],
                                            )
                                                ? filters.target_academic_year_id
                                                : ''
                                        "
                                    />
                                    <input
                                        type="hidden"
                                        name="target_term_id"
                                        :value="
                                            needsNextEnrollment(
                                                decisions[enrollment.id],
                                            )
                                                ? filters.target_term_id
                                                : ''
                                        "
                                    />
                                    <Input
                                        name="reason"
                                        placeholder="Optional decision reason"
                                    />
                                    <InputError
                                        :message="
                                            errors.decision ||
                                            errors.target_academic_year_id ||
                                            errors.target_term_id ||
                                            errors.target_level_id ||
                                            errors.reason
                                        "
                                    />
                                    <Button
                                        type="submit"
                                        size="sm"
                                        :disabled="
                                            submissionDisabled(
                                                enrollment,
                                                processing,
                                            )
                                        "
                                    >
                                        <Save class="size-4" />
                                        Record decision
                                    </Button>
                                </Form>
                            </td>
                        </template>

                        <template v-else>
                            <td class="px-4 py-4">
                                <Badge class="capitalize">
                                    {{ statusLabel(enrollment.status) }}
                                </Badge>
                                <div
                                    v-if="enrollment.decision_maker"
                                    class="mt-1 text-xs text-muted-foreground"
                                >
                                    {{ enrollment.decision_maker }}
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <template v-if="enrollment.next_enrollment">
                                    <div class="flex items-center gap-2">
                                        <ArrowRight class="size-4" />
                                        <span class="font-medium">
                                            {{
                                                enrollment.next_enrollment.level
                                            }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{
                                            enrollment.next_enrollment
                                                .academic_year
                                        }}
                                        /
                                        {{ enrollment.next_enrollment.term }}
                                        /
                                        {{
                                            enrollment.next_enrollment
                                                .learning_center
                                        }}
                                    </div>
                                </template>
                                <span v-else class="text-muted-foreground">
                                    No next enrollment
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <div>
                                    {{ enrollment.decision_reason || '-' }}
                                </div>
                                <div
                                    v-if="enrollment.decision_at"
                                    class="mt-1 text-xs text-muted-foreground"
                                >
                                    {{
                                        new Date(
                                            enrollment.decision_at,
                                        ).toLocaleString()
                                    }}
                                </div>
                            </td>
                        </template>
                    </tr>
                    <tr v-if="enrollments.data.length === 0">
                        <td
                            colspan="5"
                            class="px-4 py-14 text-center text-muted-foreground"
                        >
                            No enrollments match these filters.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between">
            <Button
                variant="outline"
                :disabled="!enrollments.prev_page_url"
                @click="
                    enrollments.prev_page_url &&
                    router.get(enrollments.prev_page_url)
                "
            >
                Previous
            </Button>
            <span class="text-sm text-muted-foreground">
                {{ enrollments.total }} enrollments
            </span>
            <Button
                variant="outline"
                :disabled="!enrollments.next_page_url"
                @click="
                    enrollments.next_page_url &&
                    router.get(enrollments.next_page_url)
                "
            >
                Next
            </Button>
        </div>
    </div>
</template>
