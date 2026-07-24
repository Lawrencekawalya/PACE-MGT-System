<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import {
    AlertTriangle,
    BookOpenCheck,
    Building2,
    Eye,
    PackageCheck,
    Search,
    UserRoundSearch,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import PaceIssuingController from '@/actions/App/Http/Controllers/PaceIssuingController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { show as showAssignment } from '@/routes/pace-assignments';
import { index } from '@/routes/pace-issuing';

type Mode = 'center' | 'pace' | 'student';
type Option = { id: number; name: string };
type Level = Option & { code: string };
type LearningCenter = Option & {
    code: string;
    levels: Level[];
};
type Inventory = {
    id: number;
    sku: string;
    on_hand: number;
    is_active: boolean;
    is_consumable: boolean;
};
type Assignment = {
    id: number;
    pace_id: number;
    pace: { id: number; number: string; title: string | null };
    inventory: Inventory | null;
    student_course: {
        course: Option;
        enrollment: {
            student: {
                id: number;
                admission_number: string;
                first_name: string;
                last_name: string;
                other_names: string | null;
            };
            learning_center: { id: number; name: string; code: string } | null;
            level: { id: number; name: string; code: string } | null;
        };
    };
};
type ReviewLine = {
    paceId: number;
    paceNumber: string;
    title: string | null;
    required: number;
    onHand: number;
    sku: string | null;
    available: boolean;
};

const props = defineProps<{
    assignments: {
        data: Assignment[];
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    filters: {
        mode: Mode;
        search: string;
        learning_center_id: number | null;
        level_id: number | null;
        course_id: number | null;
    };
    learningCenters: LearningCenter[];
    courses: Option[];
}>();

const mode = ref<Mode>(props.filters.mode);
const search = ref(props.filters.search);
const learningCenterId = ref<number | ''>(
    props.filters.learning_center_id ?? '',
);
const levelId = ref<number | ''>(props.filters.level_id ?? '');
const courseId = ref<number | ''>(props.filters.course_id ?? '');
const selectedIds = ref<number[]>([]);

const availableLevels = computed<Level[]>(() => {
    if (learningCenterId.value === '') {
        return props.learningCenters.flatMap((center) => center.levels);
    }

    return (
        props.learningCenters.find(
            (center) => center.id === Number(learningCenterId.value),
        )?.levels ?? []
    );
});
const selectableIds = computed(() =>
    props.assignments.data
        .filter(
            (assignment) =>
                assignment.inventory?.is_active &&
                assignment.inventory.is_consumable &&
                assignment.inventory.on_hand > 0,
        )
        .map((assignment) => assignment.id),
);
const allSelected = computed(
    () =>
        selectableIds.value.length > 0 &&
        selectableIds.value.every((id) => selectedIds.value.includes(id)),
);
const selectedAssignments = computed(() =>
    props.assignments.data.filter((assignment) =>
        selectedIds.value.includes(assignment.id),
    ),
);
const reviewLines = computed<ReviewLine[]>(() => {
    const lines = new Map<number, ReviewLine>();

    for (const assignment of selectedAssignments.value) {
        const existing = lines.get(assignment.pace_id);

        if (existing) {
            existing.required += 1;
            existing.available = existing.onHand >= existing.required;
            continue;
        }

        lines.set(assignment.pace_id, {
            paceId: assignment.pace_id,
            paceNumber: assignment.pace.number,
            title: assignment.pace.title,
            required: 1,
            onHand: assignment.inventory?.on_hand ?? 0,
            sku: assignment.inventory?.sku ?? null,
            available:
                assignment.inventory?.is_active === true &&
                assignment.inventory.is_consumable &&
                assignment.inventory.on_hand >= 1,
        });
    }

    return [...lines.values()].sort((first, second) =>
        first.paceNumber.localeCompare(second.paceNumber, undefined, {
            numeric: true,
        }),
    );
});
const hasShortage = computed(() =>
    reviewLines.value.some((line) => !line.available),
);
const searchPlaceholder = computed(() => {
    if (mode.value === 'pace') {
        return 'PACE number or title';
    }

    if (mode.value === 'student') {
        return 'Student or admission number';
    }

    return 'Student, admission number or PACE';
});

function setMode(value: Mode): void {
    mode.value = value;
    search.value = '';
    selectedIds.value = [];
    filter();
}
function filter(): void {
    selectedIds.value = [];
    router.get(
        index().url,
        {
            mode: mode.value,
            search: search.value,
            learning_center_id: learningCenterId.value,
            level_id: levelId.value,
            course_id: courseId.value,
        },
        { preserveState: true, replace: true },
    );
}
function changeLearningCenter(): void {
    if (
        levelId.value !== '' &&
        !availableLevels.value.some(
            (level) => level.id === Number(levelId.value),
        )
    ) {
        levelId.value = '';
    }
}
function toggleAssignment(id: number, checked: boolean): void {
    if (checked) {
        selectedIds.value = [...new Set([...selectedIds.value, id])];

        return;
    }

    selectedIds.value = selectedIds.value.filter(
        (selectedId) => selectedId !== id,
    );
}
function togglePage(checked: boolean): void {
    selectedIds.value = checked ? [...selectableIds.value] : [];
}
function confirmIssue(event: globalThis.Event): void {
    if (
        !window.confirm(
            `Confirm physical handover of ${selectedIds.value.length} PACE assignment(s)? Stock will be deducted immediately.`,
        )
    ) {
        event.preventDefault();
    }
}
function visitPage(url: string | null): void {
    if (url === null) {
        return;
    }

    selectedIds.value = [];
    router.get(url);
}

defineOptions({
    layout: { breadcrumbs: [{ title: 'PACE issuing', href: index() }] },
});
</script>

<template>
    <Head title="PACE issuing" />
    <div class="flex max-w-[1600px] flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading
            title="PACE issuing"
            description="Assigned PACEs awaiting physical handover"
        />

        <div class="flex flex-wrap gap-1 border-b pb-3">
            <Button
                :variant="mode === 'center' ? 'secondary' : 'ghost'"
                type="button"
                @click="setMode('center')"
            >
                <Building2 class="size-4" />
                By learning centre
            </Button>
            <Button
                :variant="mode === 'pace' ? 'secondary' : 'ghost'"
                type="button"
                @click="setMode('pace')"
            >
                <BookOpenCheck class="size-4" />
                By PACE
            </Button>
            <Button
                :variant="mode === 'student' ? 'secondary' : 'ghost'"
                type="button"
                @click="setMode('student')"
            >
                <UserRoundSearch class="size-4" />
                By student
            </Button>
        </div>

        <form
            class="grid gap-2 border-b pb-5 md:grid-cols-2 xl:grid-cols-6"
            @submit.prevent="filter"
        >
            <div class="relative md:col-span-2">
                <Search
                    class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    class="pl-9"
                    :placeholder="searchPlaceholder"
                />
            </div>
            <select
                v-model="learningCenterId"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
                @change="changeLearningCenter"
            >
                <option value="">All learning centres</option>
                <option
                    v-for="center in learningCenters"
                    :key="center.id"
                    :value="center.id"
                >
                    {{ center.name }}
                </option>
            </select>
            <select
                v-model="levelId"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">All grades</option>
                <option
                    v-for="level in availableLevels"
                    :key="level.id"
                    :value="level.id"
                >
                    {{ level.name }}
                </option>
            </select>
            <select
                v-model="courseId"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">All courses</option>
                <option
                    v-for="course in courses"
                    :key="course.id"
                    :value="course.id"
                >
                    {{ course.name }}
                </option>
            </select>
            <Button type="submit" variant="secondary">Filter</Button>
        </form>

        <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
            <section class="min-w-0 space-y-4">
                <div class="overflow-x-auto rounded-md border">
                    <table class="w-full min-w-[1050px] text-sm">
                        <thead class="border-b bg-muted/40 text-left">
                            <tr>
                                <th class="w-12 px-4 py-3">
                                    <Checkbox
                                        :model-value="allSelected"
                                        aria-label="Select available assignments on this page"
                                        @update:model-value="
                                            togglePage($event === true)
                                        "
                                    />
                                </th>
                                <th class="px-4 py-3">Learning centre</th>
                                <th class="px-4 py-3">Grade</th>
                                <th class="px-4 py-3">Student</th>
                                <th class="px-4 py-3">Course</th>
                                <th class="px-4 py-3">PACE</th>
                                <th class="px-4 py-3">Stock</th>
                                <th class="w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="assignment in assignments.data"
                                :key="assignment.id"
                            >
                                <td class="px-4 py-3">
                                    <Checkbox
                                        :model-value="
                                            selectedIds.includes(assignment.id)
                                        "
                                        :disabled="
                                            !assignment.inventory?.is_active ||
                                            !assignment.inventory
                                                ?.is_consumable ||
                                            assignment.inventory.on_hand <= 0
                                        "
                                        :aria-label="`Select PACE ${assignment.pace.number} for ${assignment.student_course.enrollment.student.first_name}`"
                                        @update:model-value="
                                            toggleAssignment(
                                                assignment.id,
                                                $event === true,
                                            )
                                        "
                                    />
                                </td>
                                <td class="px-4 py-3">
                                    {{
                                        assignment.student_course.enrollment
                                            .learning_center?.name ||
                                        'Unassigned'
                                    }}
                                </td>
                                <td class="px-4 py-3">
                                    {{
                                        assignment.student_course.enrollment
                                            .level?.name || 'No grade'
                                    }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium">
                                        {{
                                            assignment.student_course.enrollment
                                                .student.first_name
                                        }}
                                        {{
                                            assignment.student_course.enrollment
                                                .student.last_name
                                        }}
                                    </div>
                                    <div
                                        class="font-mono text-xs text-muted-foreground"
                                    >
                                        {{
                                            assignment.student_course.enrollment
                                                .student.admission_number
                                        }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    {{ assignment.student_course.course.name }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-mono font-semibold">
                                        {{ assignment.pace.number }}
                                    </div>
                                    <div
                                        class="max-w-64 truncate text-xs text-muted-foreground"
                                    >
                                        {{
                                            assignment.pace.title || 'No title'
                                        }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <Badge
                                        v-if="
                                            assignment.inventory?.is_active &&
                                            assignment.inventory
                                                .is_consumable &&
                                            assignment.inventory.on_hand > 0
                                        "
                                        variant="outline"
                                    >
                                        {{ assignment.inventory.on_hand }} on
                                        hand
                                    </Badge>
                                    <Badge v-else variant="destructive">
                                        {{
                                            assignment.inventory
                                                ? 'Unavailable'
                                                : 'No inventory item'
                                        }}
                                    </Badge>
                                    <div
                                        v-if="assignment.inventory"
                                        class="mt-1 font-mono text-xs text-muted-foreground"
                                    >
                                        {{ assignment.inventory.sku }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        as-child
                                    >
                                        <Link
                                            :href="
                                                showAssignment(assignment.id)
                                            "
                                            :aria-label="`View assignment ${assignment.id}`"
                                        >
                                            <Eye class="size-4" />
                                        </Link>
                                    </Button>
                                </td>
                            </tr>
                            <tr v-if="assignments.data.length === 0">
                                <td
                                    colspan="8"
                                    class="px-4 py-14 text-center text-muted-foreground"
                                >
                                    No assigned PACEs match these filters.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between gap-4">
                    <Button
                        variant="outline"
                        :disabled="!assignments.prev_page_url"
                        @click="visitPage(assignments.prev_page_url)"
                    >
                        Previous
                    </Button>
                    <span class="text-sm text-muted-foreground">
                        {{ assignments.total }} awaiting issue
                    </span>
                    <Button
                        variant="outline"
                        :disabled="!assignments.next_page_url"
                        @click="visitPage(assignments.next_page_url)"
                    >
                        Next
                    </Button>
                </div>
            </section>

            <aside class="space-y-4 rounded-md border p-4 xl:sticky xl:top-4">
                <div>
                    <h2 class="font-semibold">Issue review</h2>
                    <p class="text-sm text-muted-foreground">
                        {{ selectedIds.length }} assignment(s) selected
                    </p>
                </div>

                <div
                    v-if="reviewLines.length === 0"
                    class="border-y py-8 text-center text-sm text-muted-foreground"
                >
                    No PACEs selected.
                </div>
                <div v-else class="divide-y border-y">
                    <div
                        v-for="line in reviewLines"
                        :key="line.paceId"
                        class="space-y-2 py-3"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="font-mono font-semibold">
                                    {{ line.paceNumber }}
                                </div>
                                <div
                                    class="line-clamp-1 text-xs text-muted-foreground"
                                >
                                    {{ line.title || line.sku }}
                                </div>
                            </div>
                            <Badge
                                :variant="
                                    line.available ? 'outline' : 'destructive'
                                "
                            >
                                {{ line.required }} required
                            </Badge>
                        </div>
                        <div
                            class="flex justify-between text-xs text-muted-foreground"
                        >
                            <span>{{ line.onHand }} on hand</span>
                            <span v-if="line.available">Ready</span>
                            <span v-else class="text-destructive">
                                Short by
                                {{ Math.max(line.required - line.onHand, 0) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div
                    v-if="hasShortage"
                    class="flex gap-2 border-l-4 border-destructive px-3 py-2 text-sm"
                >
                    <AlertTriangle class="mt-0.5 size-4 shrink-0" />
                    <span>Resolve stock shortages before issuing.</span>
                </div>

                <Form
                    v-bind="PaceIssuingController.store.form()"
                    @submit="confirmIssue"
                    @success="selectedIds = []"
                    v-slot="{ errors, processing }"
                >
                    <input
                        v-for="id in selectedIds"
                        :key="id"
                        type="hidden"
                        name="assignment_ids[]"
                        :value="id"
                    />
                    <p
                        v-if="errors.assignment_ids || errors.stock"
                        class="mb-3 text-sm text-destructive"
                    >
                        {{ errors.assignment_ids || errors.stock }}
                    </p>
                    <Button
                        class="w-full"
                        type="submit"
                        :disabled="
                            selectedIds.length === 0 ||
                            hasShortage ||
                            processing
                        "
                    >
                        <PackageCheck class="size-4" />
                        Confirm issue
                    </Button>
                </Form>
            </aside>
        </div>
    </div>
</template>
