<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { BookOpenCheck, Pencil, Plus } from '@lucide/vue';
import StudentStatusController from '@/actions/App/Http/Controllers/StudentStatusController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { edit, show } from '@/routes/students';
import {
    create as createEnrollment,
    edit as editEnrollment,
} from '@/routes/students/enrollments';
type Placement = {
    id: number;
    course_id: number;
    status: string;
    is_curriculum_required: boolean;
    placement_reason: string | null;
    course: { name: string; subject: { name: string } };
    starting_pace: { number: string } | null;
    current_pace: { number: string } | null;
    assigned_by: { name: string } | null;
};
type Enrollment = {
    id: number;
    status: string;
    enrolled_on: string;
    academic_year: { name: string };
    term: { name: string };
    level: { name: string };
    student_courses: Placement[];
};
type Student = {
    id: number;
    admission_number: string;
    first_name: string;
    last_name: string;
    other_names: string | null;
    date_of_birth: string | null;
    gender: string | null;
    guardian_name: string;
    guardian_phone: string;
    guardian_email: string | null;
    notes: string | null;
    status: string;
    enrollments: Enrollment[];
};
const props = defineProps<{
    student: Student;
    tab: string;
    statuses: Array<{ value: string; label: string }>;
    canAssign: boolean;
}>();
const fullName = [
    props.student.first_name,
    props.student.other_names,
    props.student.last_name,
]
    .filter(Boolean)
    .join(' ');
function tabUrl(tab: string) {
    return show(props.student.id, { query: { tab } });
}
function confirmStatus(event: Event): void {
    if (!window.confirm('Confirm this student status change?')) {
        event.preventDefault();
    }
}
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Students', href: '/students' },
            { title: 'Student profile', href: '#' },
        ],
    },
});
</script>
<template>
    <Head :title="fullName" />
    <div class="flex max-w-[1400px] flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-3">
                <div>
                    <Heading
                        :title="fullName"
                        :description="student.admission_number"
                    /><Badge
                        class="mt-2"
                        :variant="
                            student.status === 'active' ? 'default' : 'outline'
                        "
                        >{{ student.status }}</Badge
                    >
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button variant="outline" as-child
                    ><Link :href="edit(student.id)"
                        ><Pencil class="size-4" />Edit profile</Link
                    ></Button
                ><Button
                    v-if="canAssign && student.status === 'active'"
                    as-child
                    ><Link :href="createEnrollment(student.id)"
                        ><Plus class="size-4" />New enrolment</Link
                    ></Button
                >
            </div>
        </div>
        <nav class="flex gap-1 border-b" aria-label="Student profile sections">
            <Button
                v-for="item in [
                    { value: 'overview', label: 'Overview' },
                    { value: 'enrollments', label: 'Enrolment history' },
                    { value: 'placements', label: 'Course placements' },
                ]"
                :key="item.value"
                variant="ghost"
                class="rounded-none border-b-2"
                :class="
                    tab === item.value ? 'border-primary' : 'border-transparent'
                "
                as-child
                ><Link :href="tabUrl(item.value)">{{
                    item.label
                }}</Link></Button
            >
        </nav>
        <div v-if="tab === 'overview'" class="grid gap-8 lg:grid-cols-2">
            <section class="space-y-4">
                <h2 class="text-base font-semibold">Student details</h2>
                <dl class="grid grid-cols-[9rem_1fr] gap-x-4 gap-y-3 text-sm">
                    <dt class="text-muted-foreground">Admission number</dt>
                    <dd class="font-mono">{{ student.admission_number }}</dd>
                    <dt class="text-muted-foreground">Date of birth</dt>
                    <dd>
                        {{
                            student.date_of_birth
                                ? new Date(
                                      student.date_of_birth,
                                  ).toLocaleDateString()
                                : 'Not recorded'
                        }}
                    </dd>
                    <dt class="text-muted-foreground">Gender</dt>
                    <dd class="capitalize">
                        {{ student.gender || 'Not specified' }}
                    </dd>
                    <dt class="text-muted-foreground">Guardian</dt>
                    <dd>{{ student.guardian_name }}</dd>
                    <dt class="text-muted-foreground">Phone</dt>
                    <dd>{{ student.guardian_phone }}</dd>
                    <dt class="text-muted-foreground">Email</dt>
                    <dd>{{ student.guardian_email || 'Not recorded' }}</dd>
                </dl>
                <div class="border-t pt-4">
                    <h3 class="mb-2 text-sm font-medium">Internal notes</h3>
                    <p
                        class="text-sm whitespace-pre-wrap text-muted-foreground"
                    >
                        {{ student.notes || 'No notes recorded.' }}
                    </p>
                </div>
            </section>
            <section class="space-y-4">
                <h2 class="text-base font-semibold">Student status</h2>
                <Form
                    v-bind="StudentStatusController.form(student.id)"
                    class="space-y-4 rounded-md border p-4"
                    @submit="confirmStatus"
                    v-slot="{ errors, processing }"
                    ><div class="grid gap-2">
                        <label for="status" class="text-sm font-medium"
                            >Status</label
                        ><select
                            id="status"
                            name="status"
                            class="h-9 rounded-md border bg-transparent px-3 text-sm"
                        >
                            <option
                                v-for="item in statuses"
                                :key="item.value"
                                :value="item.value"
                                :selected="item.value === student.status"
                            >
                                {{ item.label }}
                            </option>
                        </select>
                    </div>
                    <div class="grid gap-2">
                        <label for="reason" class="text-sm font-medium"
                            >Reason for change</label
                        ><Input
                            id="reason"
                            name="reason"
                            placeholder="Required when leaving active status"
                        /><span class="text-xs text-destructive">{{
                            errors.reason
                        }}</span>
                    </div>
                    <Button
                        type="submit"
                        variant="secondary"
                        :disabled="processing"
                        >Update status</Button
                    ></Form
                >
            </section>
        </div>
        <div
            v-else-if="tab === 'enrollments'"
            class="overflow-x-auto rounded-md border"
        >
            <table class="w-full min-w-3xl text-sm">
                <thead class="border-b bg-muted/40 text-left">
                    <tr>
                        <th class="px-4 py-3">Academic year</th>
                        <th class="px-4 py-3">Term</th>
                        <th class="px-4 py-3">Level</th>
                        <th class="px-4 py-3">Enrolled</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Courses</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="enrollment in student.enrollments"
                        :key="enrollment.id"
                    >
                        <td class="px-4 py-3 font-medium">
                            {{ enrollment.academic_year.name }}
                        </td>
                        <td class="px-4 py-3">{{ enrollment.term.name }}</td>
                        <td class="px-4 py-3">{{ enrollment.level.name }}</td>
                        <td class="px-4 py-3">
                            {{
                                new Date(
                                    enrollment.enrolled_on,
                                ).toLocaleDateString()
                            }}
                        </td>
                        <td class="px-4 py-3">
                            <Badge variant="outline">{{
                                enrollment.status
                            }}</Badge>
                        </td>
                        <td class="px-4 py-3">
                            {{
                                enrollment.student_courses.filter(
                                    (course) => course.status === 'active',
                                ).length
                            }}
                        </td>
                    </tr>
                    <tr v-if="student.enrollments.length === 0">
                        <td
                            colspan="6"
                            class="px-4 py-12 text-center text-muted-foreground"
                        >
                            No enrolment history.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-else class="space-y-7">
            <section
                v-for="enrollment in student.enrollments"
                :key="enrollment.id"
                class="space-y-3 border-b pb-7 last:border-0"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="font-semibold">
                            {{ enrollment.academic_year.name }} ·
                            {{ enrollment.level.name }}
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            {{ enrollment.term.name }} ·
                            {{ enrollment.student_courses.length }} course
                            records
                        </p>
                    </div>
                    <Button
                        v-if="canAssign && enrollment.status === 'active'"
                        size="sm"
                        variant="outline"
                        as-child
                        ><Link
                            :href="
                                editEnrollment({
                                    student: student.id,
                                    enrollment: enrollment.id,
                                })
                            "
                            ><Pencil class="size-4" />Edit placement</Link
                        ></Button
                    >
                </div>
                <div class="overflow-x-auto rounded-md border">
                    <table class="w-full min-w-4xl text-sm">
                        <thead class="border-b bg-muted/40 text-left">
                            <tr>
                                <th class="px-3 py-2">Course</th>
                                <th class="px-3 py-2">Subject</th>
                                <th class="px-3 py-2">Source</th>
                                <th class="px-3 py-2">Starting PACE</th>
                                <th class="px-3 py-2">Current PACE</th>
                                <th class="px-3 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="placement in enrollment.student_courses"
                                :key="placement.id"
                            >
                                <td class="px-3 py-2 font-medium">
                                    {{ placement.course.name }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ placement.course.subject.name }}
                                </td>
                                <td class="px-3 py-2">
                                    {{
                                        placement.is_curriculum_required
                                            ? 'Prescribed'
                                            : 'Manual override'
                                    }}
                                </td>
                                <td class="px-3 py-2 font-mono">
                                    {{
                                        placement.starting_pace?.number ||
                                        'Pending'
                                    }}
                                </td>
                                <td class="px-3 py-2 font-mono">
                                    {{
                                        placement.current_pace?.number ||
                                        'Pending'
                                    }}
                                </td>
                                <td class="px-3 py-2">
                                    <Badge variant="outline">{{
                                        placement.status
                                    }}</Badge>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
            <div
                v-if="student.enrollments.length === 0"
                class="py-12 text-center text-muted-foreground"
            >
                <BookOpenCheck class="mx-auto mb-3 size-6" />No course
                placements yet.
            </div>
        </div>
    </div>
</template>
