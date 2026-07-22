<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { AlertTriangle, Eye, Search } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index, show } from '@/routes/pace-assignments';

type Assignment = {
    id: number;
    status: string;
    assigned_at: string;
    override_reason: string | null;
    pace: { number: string; title: string | null };
    student_course: {
        course: { name: string };
        enrollment: {
            student: {
                admission_number: string;
                first_name: string;
                last_name: string;
            };
        };
    };
};
const props = defineProps<{
    assignments: {
        data: Assignment[];
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    filters: {
        search: string;
        status: string;
        course_id: number | null;
        date_from: string | null;
        date_to: string | null;
        exceptions: boolean;
    };
    statuses: Array<{ value: string; label: string }>;
    courses: Array<{ id: number; name: string }>;
    summary: { active: number; awaiting_test: number; exceptions: number };
}>();
const search = ref(props.filters.search);
const status = ref(props.filters.status);
const courseId = ref(props.filters.course_id ?? '');
const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');
const exceptions = ref(props.filters.exceptions);
function filter(): void {
    router.get(
        index().url,
        {
            search: search.value,
            status: status.value,
            course_id: courseId.value,
            date_from: dateFrom.value,
            date_to: dateTo.value,
            exceptions: exceptions.value ? 1 : 0,
        },
        { preserveState: true, replace: true },
    );
}
function label(value: string): string {
    return props.statuses.find((item) => item.value === value)?.label ?? value;
}
defineOptions({
    layout: { breadcrumbs: [{ title: 'PACE work queue', href: index() }] },
});
</script>

<template>
    <Head title="PACE work queue" />
    <div class="flex max-w-[1500px] flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading
            title="PACE work queue"
            description="Follow assignments from academic allocation through testing"
        />
        <div
            class="grid grid-cols-3 gap-px overflow-hidden rounded-md border bg-border"
        >
            <button
                class="bg-background p-4 text-left"
                type="button"
                @click="
                    exceptions = false;
                    filter();
                "
            >
                <div class="text-2xl font-semibold">{{ summary.active }}</div>
                <div class="text-xs text-muted-foreground">
                    Active assignments
                </div>
            </button>
            <button
                class="bg-background p-4 text-left"
                type="button"
                @click="
                    status = 'awaiting_self_test';
                    exceptions = false;
                    filter();
                "
            >
                <div class="text-2xl font-semibold">
                    {{ summary.awaiting_test }}
                </div>
                <div class="text-xs text-muted-foreground">Awaiting tests</div>
            </button>
            <button
                class="bg-background p-4 text-left"
                type="button"
                @click="
                    exceptions = true;
                    filter();
                "
            >
                <div class="flex items-center gap-2 text-2xl font-semibold">
                    <AlertTriangle class="size-5 text-amber-600" />{{
                        summary.exceptions
                    }}
                </div>
                <div class="text-xs text-muted-foreground">Exceptions</div>
            </button>
        </div>
        <form class="grid gap-2 lg:grid-cols-7" @submit.prevent="filter">
            <div class="relative lg:col-span-2">
                <Search
                    class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                /><Input
                    v-model="search"
                    class="pl-9"
                    placeholder="Student, admission no. or PACE"
                />
            </div>
            <select
                v-model="status"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">All statuses</option>
                <option
                    v-for="item in statuses"
                    :key="item.value"
                    :value="item.value"
                >
                    {{ item.label }}
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
            <Input
                v-model="dateFrom"
                type="date"
                aria-label="Assigned from"
            /><Input v-model="dateTo" type="date" aria-label="Assigned to" />
            <Button type="submit" variant="secondary">Filter</Button>
        </form>
        <div
            v-if="exceptions"
            class="flex items-center justify-between border-y border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:bg-amber-950/30 dark:text-amber-100"
        >
            <span
                >Showing manual overrides and assignments open for at least 14
                days.</span
            ><Button
                size="sm"
                variant="ghost"
                @click="
                    exceptions = false;
                    filter();
                "
                >Clear</Button
            >
        </div>
        <div class="overflow-x-auto rounded-md border">
            <table class="w-full min-w-5xl text-sm">
                <thead class="border-b bg-muted/40 text-left">
                    <tr>
                        <th class="px-4 py-3">Student</th>
                        <th class="px-4 py-3">Course</th>
                        <th class="px-4 py-3">PACE</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Assigned</th>
                        <th class="px-4 py-3">Exception</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="assignment in assignments.data"
                        :key="assignment.id"
                    >
                        <td class="px-4 py-3">
                            <div class="font-medium">
                                {{
                                    assignment.student_course.enrollment.student
                                        .first_name
                                }}
                                {{
                                    assignment.student_course.enrollment.student
                                        .last_name
                                }}
                            </div>
                            <div
                                class="font-mono text-xs text-muted-foreground"
                            >
                                {{
                                    assignment.student_course.enrollment.student
                                        .admission_number
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
                            <div class="text-xs text-muted-foreground">
                                {{ assignment.pace.title }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <Badge variant="outline">{{
                                label(assignment.status)
                            }}</Badge>
                        </td>
                        <td class="px-4 py-3">
                            {{
                                new Date(
                                    assignment.assigned_at,
                                ).toLocaleDateString()
                            }}
                        </td>
                        <td class="px-4 py-3">
                            <span
                                v-if="assignment.override_reason"
                                class="text-amber-700"
                                >Manual override</span
                            ><span v-else>None</span>
                        </td>
                        <td class="px-4 py-3">
                            <Button size="icon" variant="ghost" as-child
                                ><Link
                                    :href="show(assignment.id)"
                                    :aria-label="`View assignment ${assignment.id}`"
                                    ><Eye class="size-4" /></Link
                            ></Button>
                        </td>
                    </tr>
                    <tr v-if="assignments.data.length === 0">
                        <td
                            colspan="7"
                            class="px-4 py-14 text-center text-muted-foreground"
                        >
                            No assignments match these filters.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="flex justify-between">
            <Button
                variant="outline"
                :disabled="!assignments.prev_page_url"
                @click="
                    assignments.prev_page_url &&
                    router.get(assignments.prev_page_url)
                "
                >Previous</Button
            ><span class="text-sm text-muted-foreground"
                >{{ assignments.total }} records</span
            ><Button
                variant="outline"
                :disabled="!assignments.next_page_url"
                @click="
                    assignments.next_page_url &&
                    router.get(assignments.next_page_url)
                "
                >Next</Button
            >
        </div>
    </div>
</template>
