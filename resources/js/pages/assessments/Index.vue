<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ClipboardCheck, Eye, Search } from '@lucide/vue';
import { ref } from 'vue';
import PaceRetryApprovalController from '@/actions/App/Http/Controllers/PaceRetryApprovalController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index } from '@/routes/assessments';
import { show } from '@/routes/pace-assignments';

type Student = {
    admission_number: string;
    first_name: string;
    last_name: string;
};
type Assignment = {
    id: number;
    status: string;
    submitted_at: string | null;
    pace: { number: string; title: string | null };
    student_course: {
        course: { name: string };
        enrollment: { student: Student };
    };
};
type Approval = {
    id: number;
    assessment_type: string;
    attempt_number: number;
    is_over_limit: boolean;
    requested_at: string;
    request_reason: string;
    requested_by: { name: string } | null;
    assignment: Assignment;
};
const props = defineProps<{
    assignments: {
        data: Assignment[];
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    approvals: Approval[];
    search: string;
    canEnterResults: boolean;
    canApprove: boolean;
    canApproveOverLimit: boolean;
}>();
const search = ref(props.search);
function filter(): void {
    router.get(
        index().url,
        { search: search.value },
        { preserveState: true, replace: true },
    );
}
function label(value: string): string {
    return value
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}
function confirmDecision(event: Event, decision: string): void {
    if (!window.confirm(`${decision} this retry request?`)) {
        event.preventDefault();
    }
}
defineOptions({
    layout: { breadcrumbs: [{ title: 'Assessments', href: index() }] },
});
</script>

<template>
    <Head title="Assessments" />
    <div class="flex max-w-[1500px] flex-1 flex-col gap-7 p-4 md:p-6">
        <Heading
            title="Assessments"
            description="Record waiting tests and decide retry requests"
        />
        <section class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-semibold">Awaiting tests</h2>
                    <p class="text-sm text-muted-foreground">
                        {{ assignments.total }} assignments ready for assessment
                    </p>
                </div>
                <form class="flex gap-2" @submit.prevent="filter">
                    <div class="relative">
                        <Search
                            class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        /><Input
                            v-model="search"
                            class="w-72 pl-9"
                            placeholder="Student or PACE"
                        />
                    </div>
                    <Button type="submit" variant="secondary">Search</Button>
                </form>
            </div>
            <div class="overflow-x-auto rounded-md border">
                <table class="w-full min-w-4xl text-sm">
                    <thead class="border-b bg-muted/40 text-left">
                        <tr>
                            <th class="px-4 py-3">Student</th>
                            <th class="px-4 py-3">Course</th>
                            <th class="px-4 py-3">PACE</th>
                            <th class="px-4 py-3">Assessment</th>
                            <th class="px-4 py-3">Waiting since</th>
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
                            <td class="px-4 py-3 font-mono font-semibold">
                                {{ assignment.pace.number }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge variant="outline">{{
                                    assignment.status === 'awaiting_self_test'
                                        ? 'Self Test'
                                        : 'PACE Test'
                                }}</Badge>
                            </td>
                            <td class="px-4 py-3">
                                {{
                                    assignment.submitted_at
                                        ? new Date(
                                              assignment.submitted_at,
                                          ).toLocaleDateString()
                                        : 'Today'
                                }}
                            </td>
                            <td class="px-4 py-3">
                                <Button size="icon" variant="ghost" as-child
                                    ><Link
                                        :href="show(assignment.id)"
                                        :aria-label="`Assess PACE ${assignment.pace.number}`"
                                        ><Eye class="size-4" /></Link
                                ></Button>
                            </td>
                        </tr>
                        <tr v-if="assignments.data.length === 0">
                            <td
                                colspan="6"
                                class="px-4 py-12 text-center text-muted-foreground"
                            >
                                <ClipboardCheck class="mx-auto mb-2 size-6" />No
                                tests are waiting.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
        <section class="space-y-4 border-t pt-7">
            <div>
                <h2 class="font-semibold">Retry approval queue</h2>
                <p class="text-sm text-muted-foreground">
                    Pending Self Test and PACE Test retry decisions
                </p>
            </div>
            <div class="divide-y rounded-md border">
                <div
                    v-for="approval in approvals"
                    :key="approval.id"
                    class="grid gap-4 p-4 lg:grid-cols-[1fr_1fr_22rem]"
                >
                    <div>
                        <div class="font-medium">
                            {{
                                approval.assignment.student_course.enrollment
                                    .student.first_name
                            }}
                            {{
                                approval.assignment.student_course.enrollment
                                    .student.last_name
                            }}
                        </div>
                        <div class="text-xs text-muted-foreground">
                            PACE {{ approval.assignment.pace.number }} ·
                            {{ approval.assignment.student_course.course.name }}
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-medium">
                            {{ label(approval.assessment_type) }} attempt
                            {{ approval.attempt_number }}
                            <Badge
                                v-if="approval.is_over_limit"
                                class="ml-1"
                                variant="destructive"
                                >Over limit</Badge
                            >
                        </div>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ approval.request_reason }}
                        </p>
                        <div class="mt-1 text-xs text-muted-foreground">
                            Requested by {{ approval.requested_by?.name }} on
                            {{
                                new Date(
                                    approval.requested_at,
                                ).toLocaleDateString()
                            }}
                        </div>
                    </div>
                    <Form
                        v-if="
                            canApprove &&
                            (!approval.is_over_limit || canApproveOverLimit)
                        "
                        v-bind="
                            PaceRetryApprovalController.update.form(approval.id)
                        "
                        class="grid gap-2"
                        v-slot="{ errors, processing }"
                        ><Input
                            name="reason"
                            placeholder="Decision reason"
                            required
                        /><span class="text-xs text-destructive">{{
                            errors.reason || errors.decision
                        }}</span>
                        <div class="flex gap-2">
                            <Button
                                class="flex-1"
                                type="submit"
                                name="decision"
                                value="approved"
                                :disabled="processing"
                                @click="
                                    (event) => confirmDecision(event, 'Approve')
                                "
                                >Approve</Button
                            ><Button
                                class="flex-1"
                                type="submit"
                                name="decision"
                                value="rejected"
                                variant="outline"
                                :disabled="processing"
                                @click="
                                    (event) => confirmDecision(event, 'Reject')
                                "
                                >Reject</Button
                            >
                        </div></Form
                    >
                    <p v-else class="text-sm text-muted-foreground">
                        Administrator decision required.
                    </p>
                </div>
                <div
                    v-if="approvals.length === 0"
                    class="p-10 text-center text-sm text-muted-foreground"
                >
                    No retry requests are pending.
                </div>
            </div>
        </section>
    </div>
</template>
