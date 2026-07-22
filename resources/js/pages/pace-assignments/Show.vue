<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, PackageCheck, Repeat2, Send, XCircle } from '@lucide/vue';
import PaceAssignmentStatusController from '@/actions/App/Http/Controllers/PaceAssignmentStatusController';
import PaceAttemptController from '@/actions/App/Http/Controllers/PaceAttemptController';
import PaceAttemptCorrectionController from '@/actions/App/Http/Controllers/PaceAttemptCorrectionController';
import PaceRetryApprovalController from '@/actions/App/Http/Controllers/PaceRetryApprovalController';
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
type Correction = {
    id: number;
    score: string;
    outcome: string;
    reason: string;
    corrected_at: string;
    corrected_by: { name: string } | null;
};
type Attempt = {
    id: number;
    assessment_type: string;
    attempt_number: number;
    score: string;
    effective_score: string;
    pass_mark_used: string;
    outcome: string;
    effective_outcome: string;
    notes: string | null;
    finalized_at: string;
    recorded_by: { name: string } | null;
    approved_by: { name: string } | null;
    corrections: Correction[];
};
type RetryApproval = {
    id: number;
    assessment_type: string;
    attempt_number: number;
    status: string;
    is_over_limit: boolean;
    request_reason: string;
    decision_reason: string | null;
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
    attempts: Attempt[];
    retry_approvals: RetryApproval[];
};
const props = defineProps<{
    assignment: Assignment;
    availableTransitions: Array<{ value: string; label: string }>;
    canIssue: boolean;
    canAssign: boolean;
    canApproveRepeat: boolean;
    canEnterResults: boolean;
    canCorrectResults: boolean;
    assessmentRules: {
        self_test_pass_mark: string;
        pace_test_pass_mark: string;
        self_test_retry_limit: number;
    };
    nextRecommendation: {
        id: number;
        number: string;
        title: string | null;
    } | null;
}>();
function attemptsFor(type: string): Attempt[] {
    return props.assignment.attempts.filter(
        (attempt) => attempt.assessment_type === type,
    );
}
function latestAttempt(type: string): Attempt | undefined {
    return attemptsFor(type).at(-1);
}
function hasOpenApproval(type: string): boolean {
    return props.assignment.retry_approvals.some(
        (approval) =>
            approval.assessment_type === type &&
            ['pending', 'approved'].includes(approval.status) &&
            approval.attempt_number === attemptsFor(type).length + 1,
    );
}
function hasApprovedRetry(type: string): boolean {
    return props.assignment.retry_approvals.some(
        (approval) =>
            approval.assessment_type === type &&
            approval.status === 'approved' &&
            approval.attempt_number === attemptsFor(type).length + 1,
    );
}
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
                <div
                    v-if="assignment.status === 'passed'"
                    class="border-l-4 border-emerald-600 bg-emerald-50 px-4 py-3 text-sm text-emerald-950 dark:bg-emerald-950/30 dark:text-emerald-100"
                >
                    <div class="font-medium">PACE completed</div>
                    <span v-if="nextRecommendation"
                        >Next recommended PACE:
                        <span class="font-mono font-semibold">{{
                            nextRecommendation.number
                        }}</span></span
                    ><span v-else
                        >There is no later PACE in this course sequence.</span
                    >
                </div>
                <section class="space-y-4">
                    <div>
                        <h2 class="text-base font-semibold">
                            Assessment history
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            Finalized results and append-only corrections
                        </p>
                    </div>
                    <div class="overflow-x-auto rounded-md border">
                        <table class="w-full min-w-4xl text-sm">
                            <thead class="border-b bg-muted/40 text-left">
                                <tr>
                                    <th class="px-3 py-2">Assessment</th>
                                    <th class="px-3 py-2">Attempt</th>
                                    <th class="px-3 py-2">Score</th>
                                    <th class="px-3 py-2">Pass mark</th>
                                    <th class="px-3 py-2">Outcome</th>
                                    <th class="px-3 py-2">Recorded</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <template
                                    v-for="attempt in assignment.attempts"
                                    :key="attempt.id"
                                    ><tr>
                                        <td class="px-3 py-2 font-medium">
                                            {{
                                                statusLabel(
                                                    attempt.assessment_type,
                                                )
                                            }}
                                        </td>
                                        <td class="px-3 py-2">
                                            {{ attempt.attempt_number }}
                                        </td>
                                        <td class="px-3 py-2 font-mono">
                                            <span
                                                :class="
                                                    attempt.corrections.length
                                                        ? 'text-muted-foreground line-through'
                                                        : ''
                                                "
                                                >{{ attempt.score }}%</span
                                            ><span
                                                v-if="
                                                    attempt.corrections.length
                                                "
                                                class="ml-2 font-semibold"
                                                >{{
                                                    attempt.effective_score
                                                }}%</span
                                            >
                                        </td>
                                        <td class="px-3 py-2 font-mono">
                                            {{ attempt.pass_mark_used }}%
                                        </td>
                                        <td class="px-3 py-2">
                                            <Badge
                                                :variant="
                                                    attempt.effective_outcome ===
                                                    'passed'
                                                        ? 'default'
                                                        : 'destructive'
                                                "
                                                >{{
                                                    attempt.effective_outcome
                                                }}</Badge
                                            >
                                        </td>
                                        <td class="px-3 py-2">
                                            {{ attempt.recorded_by?.name }}
                                            <div
                                                class="text-xs text-muted-foreground"
                                            >
                                                {{
                                                    new Date(
                                                        attempt.finalized_at,
                                                    ).toLocaleString()
                                                }}
                                            </div>
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="correction in attempt.corrections"
                                        :key="correction.id"
                                        class="bg-muted/20"
                                    >
                                        <td colspan="6" class="px-4 py-3">
                                            <div class="font-medium">
                                                Correction:
                                                {{ correction.score }}% ·
                                                {{ correction.outcome }}
                                            </div>
                                            <div
                                                class="text-xs text-muted-foreground"
                                            >
                                                {{ correction.reason }} ·
                                                {{
                                                    correction.corrected_by
                                                        ?.name
                                                }}
                                                ·
                                                {{
                                                    new Date(
                                                        correction.corrected_at,
                                                    ).toLocaleString()
                                                }}
                                            </div>
                                        </td>
                                    </tr>
                                    <tr
                                        v-if="canCorrectResults"
                                        class="bg-muted/10"
                                    >
                                        <td colspan="6" class="px-4 py-3">
                                            <Form
                                                v-bind="
                                                    PaceAttemptCorrectionController.store.form(
                                                        attempt.id,
                                                    )
                                                "
                                                class="grid gap-2 sm:grid-cols-[8rem_1fr_auto]"
                                                @submit="
                                                    (event) =>
                                                        confirmAction(
                                                            event,
                                                            'Record this correction? The original result will remain in history.',
                                                        )
                                                "
                                                v-slot="{ errors, processing }"
                                                ><Input
                                                    name="score"
                                                    type="number"
                                                    min="0"
                                                    max="100"
                                                    step="0.01"
                                                    :value="
                                                        attempt.effective_score
                                                    "
                                                    required
                                                /><Input
                                                    name="reason"
                                                    placeholder="Correction reason"
                                                    required
                                                /><Button
                                                    type="submit"
                                                    size="sm"
                                                    variant="outline"
                                                    :disabled="processing"
                                                    >Correct result</Button
                                                ><span
                                                    class="text-xs text-destructive sm:col-span-3"
                                                    >{{
                                                        errors.score ||
                                                        errors.reason
                                                    }}</span
                                                ></Form
                                            >
                                        </td>
                                    </tr>
                                </template>
                                <tr v-if="assignment.attempts.length === 0">
                                    <td
                                        colspan="6"
                                        class="px-4 py-10 text-center text-muted-foreground"
                                    >
                                        No assessment results recorded.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
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
                    v-if="
                        ['awaiting_self_test', 'awaiting_pace_test'].includes(
                            assignment.status,
                        ) && canEnterResults
                    "
                    v-bind="PaceAttemptController.store.form(assignment.id)"
                    class="space-y-3 border-b pb-4"
                    @submit="
                        (event) =>
                            confirmAction(
                                event,
                                'Finalize this result? Finalized attempts cannot be edited.',
                            )
                    "
                    v-slot="{ errors, processing }"
                >
                    <input
                        type="hidden"
                        name="assessment_type"
                        :value="
                            assignment.status === 'awaiting_self_test'
                                ? 'self_test'
                                : 'pace_test'
                        "
                    />
                    <div class="text-sm">
                        <span class="font-medium">{{
                            assignment.status === 'awaiting_self_test'
                                ? 'Self Test'
                                : 'PACE Test'
                        }}</span>
                        <div class="text-muted-foreground">
                            Pass mark:
                            {{
                                assignment.status === 'awaiting_self_test'
                                    ? assessmentRules.self_test_pass_mark
                                    : assessmentRules.pace_test_pass_mark
                            }}%
                        </div>
                    </div>
                    <div class="grid gap-1">
                        <label class="text-sm font-medium" for="score"
                            >Score (%)</label
                        ><Input
                            id="score"
                            name="score"
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            required
                        /><span class="text-xs text-destructive">{{
                            errors.score || errors.assessment_type
                        }}</span>
                    </div>
                    <div class="grid gap-1">
                        <label class="text-sm font-medium" for="notes"
                            >Marker notes</label
                        ><textarea
                            id="notes"
                            name="notes"
                            rows="3"
                            class="rounded-md border bg-transparent px-3 py-2 text-sm"
                        ></textarea>
                    </div>
                    <Button class="w-full" type="submit" :disabled="processing"
                        ><Send class="size-4" />Review and finalize</Button
                    >
                </Form>
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
                    v-if="
                        assignment.status === 'in_progress' &&
                        latestAttempt('self_test')?.effective_outcome ===
                            'failed' &&
                        !hasOpenApproval('self_test') &&
                        canEnterResults
                    "
                    v-bind="
                        PaceRetryApprovalController.store.form(assignment.id)
                    "
                    class="space-y-2 border-t pt-4"
                    v-slot="{ errors, processing }"
                    ><input
                        type="hidden"
                        name="assessment_type"
                        value="self_test"
                    /><label class="text-sm font-medium" for="self-retry-reason"
                        >Self Test retry reason</label
                    ><Input
                        id="self-retry-reason"
                        name="reason"
                        required
                    /><span class="text-xs text-destructive">{{
                        errors.reason
                    }}</span
                    ><Button
                        class="w-full"
                        type="submit"
                        variant="outline"
                        :disabled="processing"
                        ><Repeat2 class="size-4" />Request Self Test
                        retry</Button
                    ></Form
                >
                <Form
                    v-if="
                        assignment.status === 'failed' &&
                        latestAttempt('pace_test')?.effective_outcome ===
                            'failed' &&
                        !hasOpenApproval('pace_test') &&
                        canEnterResults
                    "
                    v-bind="
                        PaceRetryApprovalController.store.form(assignment.id)
                    "
                    class="space-y-2"
                    v-slot="{ errors, processing }"
                    ><input
                        type="hidden"
                        name="assessment_type"
                        value="pace_test"
                    /><label class="text-sm font-medium" for="pace-retry-reason"
                        >Test-only retry reason</label
                    ><Input
                        id="pace-retry-reason"
                        name="reason"
                        required
                    /><span class="text-xs text-destructive">{{
                        errors.reason
                    }}</span
                    ><Button
                        class="w-full"
                        type="submit"
                        variant="outline"
                        :disabled="processing"
                        ><Repeat2 class="size-4" />Request test-only
                        retry</Button
                    ></Form
                >
                <div
                    v-for="approval in assignment.retry_approvals.filter(
                        (item) => item.status === 'pending',
                    )"
                    :key="approval.id"
                    class="border-l-4 border-amber-500 px-3 py-2 text-sm"
                >
                    <div class="font-medium">Retry approval pending</div>
                    {{ statusLabel(approval.assessment_type) }} attempt
                    {{ approval.attempt_number }}
                    <div v-if="approval.is_over_limit" class="text-amber-700">
                        Administrator review required
                    </div>
                </div>
                <Form
                    v-if="
                        assignment.status === 'in_progress' &&
                        canAssign &&
                        (!latestAttempt('self_test') ||
                            hasApprovedRetry('self_test'))
                    "
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
                    This assignment has no further actions available.
                </p>
            </aside>
        </div>
    </div>
</template>
