<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Search, UserPlus } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { create, index, show } from '@/routes/students';
type Option = { id: number; name: string };
type Enrollment = {
    id: number;
    status: string;
    academic_year: Option;
    level: Option;
    learning_center: Option | null;
};
type Student = {
    id: number;
    admission_number: string;
    first_name: string;
    last_name: string;
    other_names: string | null;
    guardian_name: string;
    guardian_phone: string;
    status: string;
    enrollments: Enrollment[];
};
const props = defineProps<{
    students: {
        data: Student[];
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    filters: {
        search: string;
        status: string;
        level_id: number | null;
        academic_year_id: number | null;
    };
    levels: Option[];
    academicYears: Option[];
    statuses: Array<{ value: string; label: string }>;
}>();
const search = ref(props.filters.search);
const status = ref(props.filters.status);
const levelId = ref(props.filters.level_id ?? '');
const yearId = ref(props.filters.academic_year_id ?? '');
function filter(): void {
    router.get(
        index().url,
        {
            search: search.value,
            status: status.value,
            level_id: levelId.value,
            academic_year_id: yearId.value,
        },
        { preserveState: true, replace: true },
    );
}
function fullName(student: Student): string {
    return [student.first_name, student.other_names, student.last_name]
        .filter(Boolean)
        .join(' ');
}
defineOptions({
    layout: { breadcrumbs: [{ title: 'Students', href: index() }] },
});
</script>
<template>
    <Head title="Students" />
    <div class="flex max-w-[1500px] flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Students"
                description="Register students and review enrolment and course placement"
            /><Button as-child
                ><Link :href="create()"
                    ><UserPlus class="size-4" />Register student</Link
                ></Button
            >
        </div>
        <form class="grid gap-2 lg:grid-cols-6" @submit.prevent="filter">
            <div class="relative lg:col-span-2">
                <Search
                    class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                /><Input
                    v-model="search"
                    class="pl-9"
                    placeholder="Name, admission number, guardian"
                    aria-label="Search students"
                />
            </div>
            <select
                v-model="status"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">Any status</option>
                <option
                    v-for="item in statuses"
                    :key="item.value"
                    :value="item.value"
                >
                    {{ item.label }}
                </option></select
            ><select
                v-model="levelId"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">All levels</option>
                <option
                    v-for="level in levels"
                    :key="level.id"
                    :value="level.id"
                >
                    {{ level.name }}
                </option></select
            ><select
                v-model="yearId"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">All academic years</option>
                <option
                    v-for="year in academicYears"
                    :key="year.id"
                    :value="year.id"
                >
                    {{ year.name }}
                </option></select
            ><Button type="submit" variant="secondary">Filter</Button>
        </form>
        <div class="overflow-x-auto rounded-md border">
            <table class="w-full min-w-4xl text-sm">
                <thead class="border-b bg-muted/40 text-left">
                    <tr>
                        <th class="px-4 py-3">Student</th>
                        <th class="px-4 py-3">Admission number</th>
                        <th class="px-4 py-3">Current level</th>
                        <th class="px-4 py-3">Academic year</th>
                        <th class="px-4 py-3">Guardian</th>
                        <th class="px-4 py-3">Learning center</th>
                        <th class="px-4 py-3">Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="student in students.data" :key="student.id">
                        <td class="px-4 py-3 font-medium">
                            {{ fullName(student) }}
                        </td>
                        <td class="px-4 py-3 font-mono text-xs">
                            {{ student.admission_number }}
                        </td>
                        <td class="px-4 py-3">
                            {{
                                student.enrollments[0]?.level.name ||
                                'Not enrolled'
                            }}
                        </td>
                        <td class="px-4 py-3">
                            {{
                                student.enrollments[0]?.academic_year.name ||
                                '—'
                            }}
                        </td>
                        <td class="px-4 py-3">
                            <div>{{ student.guardian_name }}</div>
                            <div class="text-xs text-muted-foreground">
                                {{ student.guardian_phone }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            {{
                                student.enrollments[0]?.learning_center?.name ||
                                'Unassigned'
                            }}
                        </td>
                        <td class="px-4 py-3">
                            <Badge
                                :variant="
                                    student.status === 'active'
                                        ? 'default'
                                        : 'outline'
                                "
                                >{{ student.status }}</Badge
                            >
                        </td>
                        <td class="px-4 py-3">
                            <Button size="icon" variant="ghost" as-child
                                ><Link
                                    :href="show(student.id)"
                                    :aria-label="`View ${fullName(student)}`"
                                    ><Eye class="size-4" /></Link
                            ></Button>
                        </td>
                    </tr>
                    <tr v-if="students.data.length === 0">
                        <td
                            colspan="8"
                            class="px-4 py-12 text-center text-muted-foreground"
                        >
                            No students match these filters.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="flex items-center justify-between text-sm">
            <span class="text-muted-foreground"
                >{{ students.total }} students</span
            >
            <div class="flex gap-2">
                <Button
                    size="sm"
                    variant="outline"
                    :disabled="!students.prev_page_url"
                    as-child
                    ><Link
                        v-if="students.prev_page_url"
                        :href="students.prev_page_url"
                        >Previous</Link
                    ><span v-else>Previous</span></Button
                ><Button
                    size="sm"
                    variant="outline"
                    :disabled="!students.next_page_url"
                    as-child
                    ><Link
                        v-if="students.next_page_url"
                        :href="students.next_page_url"
                        >Next</Link
                    ><span v-else>Next</span></Button
                >
            </div>
        </div>
    </div>
</template>
