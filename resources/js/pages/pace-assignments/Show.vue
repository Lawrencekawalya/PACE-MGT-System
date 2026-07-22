<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, PackageCheck, Repeat2, Send, XCircle } from '@lucide/vue';
import PaceAssignmentStatusController from '@/actions/App/Http/Controllers/PaceAssignmentStatusController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index } from '@/routes/pace-assignments';
import { show as showStudent } from '@/routes/students';

type Event = {
    id: number;
    from_status: string | null;
    to_status: string;
    changed_at: string;
    reason: string | null;
    changed_by: { name: string } | null;
};
type Assignment = {
    id: number;
    status: string;
    attempt_cycle: number;
    assigned_at: string;
    issued_at: string | null;
    started_at: string | null;
    submitted_at: string | null;
    completed_at: string | null;
    cancelled_at: string | null;
    override_reason: string | null;
    pace: {
        number: string;
        title: string | null;
        course: { name: string; subject: { name: string } };
    };
    academic_year: { name: string };
    term: { name: string };
    assigned_by: { name: string } | null;
    issued_by: { name: string } | null;
    student_course: {
        enrollment: {
            student: {
                id: number;
                admission_number: string;
                first_name: string;
                last_name: string;
            };
        };
    };
    status_events: Event[];
};
defineProps<{
    assignment: Assignment;
    availableTransitions: Array<{ value: string; label: string }>;
    canIssue: boolean;
    canAssign: boolean;
    canApproveRepeat: boolean;
}>();
function confirmAction(event: globalThis.Event, message: string): void {
    if (!window.confirm(message)) {
        event.preventDefault();
    }
}
function statusLabel(value: string): string {
    return value
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'PACE work queue', href: index() },
            { title: 'Assignment', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`PACE ${assignment.pace.number}`" />
    <div class="flex max-w-[1200px] flex-1 flex-col gap-6 p-4 md:p-6">
        <Button variant="ghost" size="sm" class="w-fit" as-child
            ><Link :href="index()"
                ><ArrowLeft class="size-4" />Work queue</Link
            ></Button
        >
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                :title="`PACE ${assignment.pace.number}`"
                :description="`${assignment.pace.course.name} · cycle ${assignment.attempt_cycle}`"
            /><Badge variant="outline">{{
                statusLabel(assignment.status)
            }}</Badge>
        </div>
        <div class="grid gap-8 lg:grid-cols-[1fr_22rem]">
            <section class="space-y-6">
                <div
                    class="grid gap-4 border-y py-5 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <div>
                        <div class="text-xs text-muted-foreground">Student</div>
                        <Link
                            class="font-medium hover:underline"
                            :href="
                                showStudent(
                                    assignment.student_course.enrollment.student
                                        .id,
                                    { query: { tab: 'progress' } },
                                )
                            "
                            >{{
                                assignment.student_course.enrollment.student
                                    .first_name
                            }}
                            {{
                                assignment.student_course.enrollment.student
                                    .last_name
                            }}</Link
                        >
                        <div class="font-mono text-xs">
                            {{
                                assignment.student_course.enrollment.student
                                    .admission_number
                            }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-muted-foreground">Subject</div>
                        <div class="font-medium">
                            {{ assignment.pace.course.subject.name }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-muted-foreground">Period</div>
                        <div class="font-medium">
                            {{ assignment.academic_year.name }} ·
                            {{ assignment.term.name }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-muted-foreground">
                            Assigned
                        </div>
                        <div>
                            {{
                                new Date(
                                    assignment.assigned_at,
                                ).toLocaleString()
                            }}
                        </div>
                        <div class="text-xs text-muted-foreground">
                            {{ assignment.assigned_by?.name }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-muted-foreground">Issued</div>
                        <div>
                            {{
                                assignment.issued_at
                                    ? new Date(
                                          assignment.issued_at,
                                      ).toLocaleString()
                                    : 'Not issued'
                            }}
                        </div>
                        <div class="text-xs text-muted-foreground">
                            {{ assignment.issued_by?.name }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-muted-foreground">
                            PACE title
                        </div>
                        <div>{{ assignment.pace.title || 'No title' }}</div>
                    </div>
                </div>
                <div
                    v-if="assignment.override_reason"
                    class="border-l-4 border-amber-500 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:bg-amber-950/30 dark:text-amber-100"
                >
                    <div class="font-medium">Administrator override</div>
                    {{ assignment.override_reason }}
                </div>
                <section>
                    <h2 class="mb-4 text-base font-semibold">Status history</h2>
                    <ol class="relative ml-2 border-l">
                        <li
                            v-for="event in assignment.status_events"
                            :key="event.id"
                            class="relative pb-6 pl-6 last:pb-0"
                        >
                            <span
                                class="absolute top-1 -left-1.5 size-3 rounded-full border-2 border-background bg-primary"
                            ></span>
                            <div class="font-medium">
                                {{ statusLabel(event.to_status) }}
                            </div>
                            <div class="text-sm text-muted-foreground">
                                {{
                                    new Date(event.changed_at).toLocaleString()
                                }}
                                · {{ event.changed_by?.name || 'System' }}
                            </div>
                            <p v-if="event.reason" class="mt-1 text-sm">
                                {{ event.reason }}
                            </p>
                        </li>
                    </ol>
                </section>
            </section>
            <aside class="space-y-4">
                <h2 class="text-base font-semibold">Available actions</h2>
                <Form
                    v-if="assignment.status === 'assigned' && canIssue"
                    v-bind="PaceAssignmentStatusController.form(assignment.id)"
                    @submit="
                        (event) =>
                            confirmAction(
                                event,
                                'Confirm that the physical PACE is being handed to this student?',
                            )
                    "
                    v-slot="{ processing }"
                    ><input
                        type="hidden"
                        name="status"
                        value="in_progress"
                    /><Button
                        class="w-full"
                        type="submit"
                        :disabled="processing"
                        ><PackageCheck class="size-4" />Issue and start</Button
                    ></Form
                >
                <Form
                    v-if="assignment.status === 'in_progress' && canAssign"
                    v-bind="PaceAssignmentStatusController.form(assignment.id)"
                    @submit="
                        (event) =>
                            confirmAction(
                                event,
                                'Submit this PACE for its Self Test?',
                            )
                    "
                    v-slot="{ processing }"
                    ><input
                        type="hidden"
                        name="status"
                        value="awaiting_self_test"
                    /><Button
                        class="w-full"
                        type="submit"
                        :disabled="processing"
                        ><Send class="size-4" />Submit for Self Test</Button
                    ></Form
                >
                <Form
                    v-if="assignment.status === 'failed' && canApproveRepeat"
                    v-bind="PaceAssignmentStatusController.form(assignment.id)"
                    class="space-y-2"
                    @submit="
                        (event) =>
                            confirmAction(
                                event,
                                'Approve a full repeat and create assignment cycle ' +
                                    (assignment.attempt_cycle + 1) +
                                    '?',
                            )
                    "
                    v-slot="{ errors, processing }"
                    ><input
                        type="hidden"
                        name="status"
                        value="reassigned"
                    /><label class="text-sm font-medium" for="repeat-reason"
                        >Repeat approval reason</label
                    ><Input id="repeat-reason" name="reason" required />
                    <p class="text-xs text-destructive">{{ errors.reason }}</p>
                    <Button class="w-full" type="submit" :disabled="processing"
                        ><Repeat2 class="size-4" />Approve full repeat</Button
                    ></Form
                >
                <Form
                    v-if="
                        ['assigned', 'in_progress'].includes(
                            assignment.status,
                        ) && canAssign
                    "
                    v-bind="PaceAssignmentStatusController.form(assignment.id)"
                    class="space-y-2 border-t pt-4"
                    @submit="
                        (event) =>
                            confirmAction(
                                event,
                                'Cancel this assignment? Its history will be retained.',
                            )
                    "
                    v-slot="{ errors, processing }"
                    ><input
                        type="hidden"
                        name="status"
                        value="cancelled"
                    /><label class="text-sm font-medium" for="reason"
                        >Cancellation reason</label
                    ><Input id="reason" name="reason" required />
                    <p class="text-xs text-destructive">{{ errors.reason }}</p>
                    <Button
                        class="w-full"
                        variant="destructive"
                        type="submit"
                        :disabled="processing"
                        ><XCircle class="size-4" />Cancel assignment</Button
                    ></Form
                >
                <p
                    v-if="availableTransitions.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    This assignment has no Phase 4 actions available.
                </p>
            </aside>
        </div>
    </div>
</template>
