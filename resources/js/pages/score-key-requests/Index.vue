<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import {
    BookKey,
    ChevronDown,
    ChevronRight,
    PackageCheck,
    Search,
    Send,
    XCircle,
} from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PaceSearchSelect from '@/components/PaceSearchSelect.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cancel, index, reject, store } from '@/routes/score-key-requests';
import { store as issue } from '@/routes/score-key-requests/issues';

type Issue = {
    id: number;
    quantity: number;
    issued_by: string | null;
    issued_at: string;
    period: string | null;
    notes: string | null;
};
type RequestRow = {
    id: number;
    teacher: string;
    learning_center: string;
    inventory_item: {
        id: number;
        sku: string;
        course: string;
        pace: string;
        title: string | null;
    };
    request_type: string;
    request_type_label: string;
    quantity_requested: number;
    quantity_issued: number;
    quantity_outstanding: number;
    status: string;
    status_label: string;
    request_reason: string | null;
    notes: string | null;
    rejection_reason: string | null;
    requested_at: string;
    can_cancel: boolean;
    can_issue: boolean;
    can_reject: boolean;
    issues: Issue[];
};
type Paginator = {
    data: RequestRow[];
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

const props = defineProps<{
    requests: Paginator;
    filters: {
        search: string;
        status: string;
        learning_center_id: number | null;
    };
    summary: { pending: number; partially_issued: number; issued: number };
    scoreKeys: Array<{ id: number; label: string; on_hand: number }>;
    learningCenters: Array<{ id: number; name: string; code: string }>;
    requestTypes: Array<{ value: string; label: string }>;
    statuses: Array<{ value: string; label: string }>;
    myScoreKeys: Array<{
        inventory_item_id: number;
        sku: string;
        course: string;
        pace: string;
        title: string | null;
        quantity: number;
        last_issued_at: string;
    }>;
    canRequest: boolean;
    canIssue: boolean;
}>();

const selectedScoreKey = ref<number | null>(null);
const requestType = ref('new_issue');
const expandedId = ref<number | null>(null);
const filters = ref({
    search: props.filters.search,
    status: props.filters.status,
    learning_center_id: props.filters.learning_center_id?.toString() ?? '',
});
const formatDate = (value: string): string =>
    new Intl.DateTimeFormat('en-UG', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));

function applyFilters(): void {
    router.get(index().url, filters.value, {
        preserveState: true,
        replace: true,
    });
}

function resetFilters(): void {
    router.get(index());
}

function visitPage(url: string | null): void {
    if (url) {
        router.get(url);
    }
}

function badgeVariant(status: string): 'default' | 'secondary' | 'outline' {
    if (status === 'issued') {
        return 'default';
    }

    return status === 'pending' || status === 'partially_issued'
        ? 'secondary'
        : 'outline';
}

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Score Key requests', href: index() }],
    },
});
</script>

<template>
    <Head title="Score Key requests" />
    <div class="flex max-w-[1600px] flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading
            title="Score Key requests"
            description="Permanent Score Key requests, issues, and Teacher custody"
        />

        <Form
            v-if="canRequest"
            v-bind="store.form()"
            class="grid gap-4 border-y py-5"
            reset-on-success
            v-slot="{ errors, processing }"
            :options="{ preserveScroll: true }"
            @success="selectedScoreKey = null"
        >
            <div>
                <h2 class="font-semibold">Request a Score Key</h2>
                <p class="text-sm text-muted-foreground">
                    Issued copies remain with you across academic terms.
                </p>
            </div>
            <div class="grid gap-3 lg:grid-cols-[0.8fr_1.6fr_0.7fr_0.5fr]">
                <div>
                    <label class="text-sm font-medium" for="learning_center_id">
                        Learning center
                    </label>
                    <select
                        id="learning_center_id"
                        name="learning_center_id"
                        required
                        class="mt-1 h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                    >
                        <option value="">Select center</option>
                        <option
                            v-for="center in learningCenters"
                            :key="center.id"
                            :value="center.id"
                        >
                            {{ center.name }}
                        </option>
                    </select>
                    <InputError :message="errors.learning_center_id" />
                </div>
                <div>
                    <label class="text-sm font-medium"
                        >Matching Score Key</label
                    >
                    <PaceSearchSelect
                        v-model="selectedScoreKey"
                        name="inventory_item_id"
                        :options="scoreKeys"
                        placeholder="Search course, PACE, title, or SKU"
                        empty-message="No matching Score Key inventory item found."
                        required
                        class="mt-1"
                    />
                    <InputError :message="errors.inventory_item_id" />
                </div>
                <div>
                    <label class="text-sm font-medium" for="request_type">
                        Request type
                    </label>
                    <select
                        id="request_type"
                        v-model="requestType"
                        name="request_type"
                        class="mt-1 h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                    >
                        <option
                            v-for="type in requestTypes"
                            :key="type.value"
                            :value="type.value"
                        >
                            {{ type.label }}
                        </option>
                    </select>
                    <InputError :message="errors.request_type" />
                </div>
                <div>
                    <label class="text-sm font-medium" for="quantity_requested">
                        Quantity
                    </label>
                    <Input
                        id="quantity_requested"
                        name="quantity_requested"
                        type="number"
                        min="1"
                        max="100"
                        value="1"
                        required
                        class="mt-1"
                    />
                    <InputError :message="errors.quantity_requested" />
                </div>
            </div>
            <div class="grid gap-3 lg:grid-cols-2">
                <div>
                    <label class="text-sm font-medium" for="request_reason">
                        {{
                            requestType === 'new_issue'
                                ? 'Reason (optional)'
                                : 'Reason'
                        }}
                    </label>
                    <textarea
                        id="request_reason"
                        name="request_reason"
                        rows="2"
                        :required="requestType !== 'new_issue'"
                        class="mt-1 w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                    />
                    <InputError :message="errors.request_reason" />
                </div>
                <div>
                    <label class="text-sm font-medium" for="notes">Notes</label>
                    <textarea
                        id="notes"
                        name="notes"
                        rows="2"
                        class="mt-1 w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                    />
                    <InputError :message="errors.notes" />
                </div>
            </div>
            <div>
                <Button :disabled="processing || learningCenters.length === 0">
                    <Send class="size-4" />
                    Submit request
                </Button>
            </div>
        </Form>

        <section v-if="canRequest" class="space-y-3">
            <div>
                <h2 class="font-semibold">My Score Keys</h2>
                <p class="text-sm text-muted-foreground">
                    Permanently issued copies currently recorded under your
                    custody
                </p>
            </div>
            <div class="overflow-x-auto rounded-md border">
                <table class="w-full min-w-[760px] text-sm">
                    <thead class="bg-muted/50 text-left">
                        <tr>
                            <th class="px-4 py-3">Score Key</th>
                            <th class="px-4 py-3">Course</th>
                            <th class="px-4 py-3">Quantity</th>
                            <th class="px-4 py-3">Last issued</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="key in myScoreKeys"
                            :key="key.inventory_item_id"
                        >
                            <td class="px-4 py-3">
                                <div class="font-medium">
                                    PACE {{ key.pace }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ key.title || key.sku }}
                                </div>
                            </td>
                            <td class="px-4 py-3">{{ key.course }}</td>
                            <td class="px-4 py-3">{{ key.quantity }}</td>
                            <td class="px-4 py-3">
                                {{ formatDate(key.last_issued_at) }}
                            </td>
                        </tr>
                        <tr v-if="myScoreKeys.length === 0">
                            <td
                                colspan="4"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                No Score Keys have been issued to you.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <div
            class="grid grid-cols-3 gap-px overflow-hidden rounded-md border bg-border"
        >
            <div class="bg-background p-4">
                <div class="text-2xl font-semibold">{{ summary.pending }}</div>
                <div class="text-xs text-muted-foreground">Pending</div>
            </div>
            <div class="bg-background p-4">
                <div class="text-2xl font-semibold">
                    {{ summary.partially_issued }}
                </div>
                <div class="text-xs text-muted-foreground">
                    Partially issued
                </div>
            </div>
            <div class="bg-background p-4">
                <div class="text-2xl font-semibold">{{ summary.issued }}</div>
                <div class="text-xs text-muted-foreground">Issued</div>
            </div>
        </div>

        <form
            class="grid gap-2 border-b pb-4 md:grid-cols-[1fr_240px_240px_auto_auto]"
            @submit.prevent="applyFilters"
        >
            <div class="relative">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="filters.search"
                    class="pl-9"
                    placeholder="Teacher, PACE, course, or SKU"
                />
            </div>
            <select
                v-model="filters.status"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">All statuses</option>
                <option
                    v-for="status in statuses"
                    :key="status.value"
                    :value="status.value"
                >
                    {{ status.label }}
                </option>
            </select>
            <select
                v-model="filters.learning_center_id"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">All learning centers</option>
                <option
                    v-for="center in learningCenters"
                    :key="center.id"
                    :value="center.id"
                >
                    {{ center.name }}
                </option>
            </select>
            <Button type="submit" variant="secondary">Filter</Button>
            <Button type="button" variant="ghost" @click="resetFilters"
                >Reset</Button
            >
        </form>

        <div class="overflow-x-auto rounded-md border">
            <table class="w-full min-w-[1050px] text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="w-12 px-3 py-3"></th>
                        <th class="px-3 py-3">Teacher / center</th>
                        <th class="px-3 py-3">Score Key</th>
                        <th class="px-3 py-3">Type</th>
                        <th class="px-3 py-3">Requested</th>
                        <th class="px-3 py-3">Issued</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-3 py-3">Requested at</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <template v-for="row in requests.data" :key="row.id">
                        <tr>
                            <td class="px-3 py-3">
                                <Button
                                    type="button"
                                    size="icon"
                                    variant="ghost"
                                    :title="
                                        expandedId === row.id
                                            ? 'Hide request details'
                                            : 'Show request details'
                                    "
                                    @click="
                                        expandedId =
                                            expandedId === row.id
                                                ? null
                                                : row.id
                                    "
                                >
                                    <ChevronDown
                                        v-if="expandedId === row.id"
                                        class="size-4"
                                    />
                                    <ChevronRight v-else class="size-4" />
                                </Button>
                            </td>
                            <td class="px-3 py-3">
                                <div class="font-medium">{{ row.teacher }}</div>
                                <div class="text-xs text-muted-foreground">
                                    {{ row.learning_center }}
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                <div class="font-medium">
                                    {{ row.inventory_item.course }} · PACE
                                    {{ row.inventory_item.pace }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{
                                        row.inventory_item.title ||
                                        row.inventory_item.sku
                                    }}
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                {{ row.request_type_label }}
                            </td>
                            <td class="px-3 py-3">
                                {{ row.quantity_requested }}
                            </td>
                            <td class="px-3 py-3">{{ row.quantity_issued }}</td>
                            <td class="px-3 py-3">
                                <Badge :variant="badgeVariant(row.status)">{{
                                    row.status_label
                                }}</Badge>
                            </td>
                            <td class="px-3 py-3">
                                {{ formatDate(row.requested_at) }}
                            </td>
                        </tr>
                        <tr v-if="expandedId === row.id">
                            <td colspan="8" class="bg-muted/20 px-5 py-5">
                                <div
                                    class="grid gap-6 lg:grid-cols-[1fr_420px]"
                                >
                                    <div class="space-y-4">
                                        <div class="grid gap-3 sm:grid-cols-3">
                                            <div>
                                                <div
                                                    class="text-xs text-muted-foreground"
                                                >
                                                    Outstanding
                                                </div>
                                                <div class="font-medium">
                                                    {{
                                                        row.quantity_outstanding
                                                    }}
                                                </div>
                                            </div>
                                            <div>
                                                <div
                                                    class="text-xs text-muted-foreground"
                                                >
                                                    Reason
                                                </div>
                                                <div>
                                                    {{
                                                        row.request_reason ||
                                                        '—'
                                                    }}
                                                </div>
                                            </div>
                                            <div>
                                                <div
                                                    class="text-xs text-muted-foreground"
                                                >
                                                    Notes
                                                </div>
                                                <div>
                                                    {{ row.notes || '—' }}
                                                </div>
                                            </div>
                                        </div>
                                        <div
                                            v-if="row.rejection_reason"
                                            class="border-l-2 border-destructive pl-3 text-sm"
                                        >
                                            {{ row.rejection_reason }}
                                        </div>
                                        <div>
                                            <h3 class="mb-2 font-medium">
                                                Issue history
                                            </h3>
                                            <div
                                                v-for="movement in row.issues"
                                                :key="movement.id"
                                                class="grid gap-1 border-t py-3 sm:grid-cols-4"
                                            >
                                                <span
                                                    >{{
                                                        movement.quantity
                                                    }}
                                                    copy or copies</span
                                                >
                                                <span>{{
                                                    movement.issued_by ||
                                                    'System user'
                                                }}</span>
                                                <span>{{
                                                    movement.period ||
                                                    'No active period'
                                                }}</span>
                                                <span>{{
                                                    formatDate(
                                                        movement.issued_at,
                                                    )
                                                }}</span>
                                            </div>
                                            <div
                                                v-if="row.issues.length === 0"
                                                class="text-sm text-muted-foreground"
                                            >
                                                No copies issued yet.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="space-y-3">
                                        <Form
                                            v-if="canIssue && row.can_issue"
                                            v-bind="issue.form(row.id)"
                                            class="grid gap-2 rounded-md border p-3"
                                            v-slot="{ errors, processing }"
                                            :options="{ preserveScroll: true }"
                                        >
                                            <div class="font-medium">
                                                Issue permanently
                                            </div>
                                            <div
                                                class="grid grid-cols-[120px_1fr] gap-2"
                                            >
                                                <Input
                                                    name="quantity"
                                                    type="number"
                                                    min="1"
                                                    :max="
                                                        row.quantity_outstanding
                                                    "
                                                    :value="
                                                        row.quantity_outstanding
                                                    "
                                                    required
                                                />
                                                <Input
                                                    name="notes"
                                                    placeholder="Handover note (optional)"
                                                />
                                            </div>
                                            <InputError
                                                :message="
                                                    errors.quantity ||
                                                    errors.stock
                                                "
                                            />
                                            <Button :disabled="processing">
                                                <PackageCheck class="size-4" />
                                                Issue Score Key
                                            </Button>
                                        </Form>

                                        <Form
                                            v-if="canIssue && row.can_reject"
                                            v-bind="reject.form(row.id)"
                                            class="grid gap-2 rounded-md border p-3"
                                            v-slot="{ errors, processing }"
                                            :options="{ preserveScroll: true }"
                                        >
                                            <Input
                                                name="reason"
                                                placeholder="Rejection reason"
                                                required
                                            />
                                            <InputError
                                                :message="errors.reason"
                                            />
                                            <Button
                                                variant="destructive"
                                                :disabled="processing"
                                            >
                                                <XCircle class="size-4" />
                                                Reject request
                                            </Button>
                                        </Form>

                                        <Form
                                            v-if="row.can_cancel"
                                            v-bind="cancel.form(row.id)"
                                            v-slot="{ processing }"
                                            :options="{ preserveScroll: true }"
                                        >
                                            <Button
                                                variant="outline"
                                                :disabled="processing"
                                                >Cancel request</Button
                                            >
                                        </Form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr v-if="requests.data.length === 0">
                        <td
                            colspan="8"
                            class="px-4 py-12 text-center text-muted-foreground"
                        >
                            <BookKey class="mx-auto mb-3 size-7" />
                            No Score Key requests match these filters.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between gap-3">
            <div class="text-sm text-muted-foreground">
                {{ requests.total }} request(s)
            </div>
            <div class="flex gap-2">
                <Button
                    variant="outline"
                    :disabled="!requests.prev_page_url"
                    @click="visitPage(requests.prev_page_url)"
                    >Previous</Button
                >
                <Button
                    variant="outline"
                    :disabled="!requests.next_page_url"
                    @click="visitPage(requests.next_page_url)"
                    >Next</Button
                >
            </div>
        </div>
    </div>
</template>
