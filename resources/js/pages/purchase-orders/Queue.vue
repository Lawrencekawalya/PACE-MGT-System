<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { Check, Eye, Search, Send, ShieldCheck, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import PurchaseOrderWorkflowController from '@/actions/App/Http/Controllers/PurchaseOrderWorkflowController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDateOnly } from '@/lib/utils';
import { approved, show, submitted } from '@/routes/purchase-orders';

type QueueName = 'submitted' | 'approved';
type Order = {
    id: number;
    order_number: string;
    source: string;
    status: string;
    expected_on: string | null;
    notes: string | null;
    lines_count: number;
    units_count: number;
    submitted_at: string | null;
    decided_at: string | null;
    supplier: { id: number; name: string; code: string };
    submitted_by: { id: number; name: string } | null;
    decided_by: { id: number; name: string } | null;
    can_decide: boolean;
    can_send: boolean;
};
type Paginator = {
    data: Order[];
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

const props = defineProps<{
    queue: QueueName;
    orders: Paginator;
    filters: { search: string };
}>();

const searchText = ref(props.filters.search);
const isSubmitted = computed(() => props.queue === 'submitted');
const queueRoute = computed(() =>
    isSubmitted.value ? submitted() : approved(),
);
const title = computed(() =>
    isSubmitted.value ? 'Submitted orders' : 'Approved orders',
);
const description = computed(() =>
    isSubmitted.value
        ? 'Review PACE Officer submissions and record the final approval decision'
        : 'Approved orders waiting to be sent to their suppliers',
);
const emptyMessage = computed(() =>
    isSubmitted.value
        ? 'No orders are waiting for administrator approval.'
        : 'No approved orders are waiting to be sent.',
);
const stages = [
    { key: 'draft', label: 'Draft', owner: 'PACE Officer' },
    { key: 'submitted', label: 'Submitted', owner: 'Administrator' },
    { key: 'approved', label: 'Approved', owner: 'PACE Officer' },
    { key: 'sent', label: 'Sent', owner: 'Supplier delivery' },
];

function filter(): void {
    router.get(
        queueRoute.value.url,
        { search: searchText.value },
        { preserveState: true, replace: true },
    );
}

function visitPage(url: string | null): void {
    if (url) {
        router.get(url);
    }
}

function formatDateTime(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('en-UG', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

function label(value: string): string {
    return value.replaceAll('_', ' ');
}

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Purchase-order workflow', href: '#' }],
    },
});
</script>

<template>
    <Head :title="title" />
    <div class="flex max-w-[1600px] flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading :title="title" :description="description" />

        <ol
            class="grid border-y sm:grid-cols-2 lg:grid-cols-4"
            aria-label="Purchase-order workflow"
        >
            <li
                v-for="(stage, index) in stages"
                :key="stage.key"
                class="border-b px-4 py-3 sm:border-r lg:border-b-0"
                :class="
                    stage.key === queue
                        ? 'border-b-2 border-b-foreground bg-muted/30'
                        : ''
                "
            >
                <div class="text-xs text-muted-foreground">
                    {{ index + 1 }} · {{ stage.owner }}
                </div>
                <div class="font-medium">{{ stage.label }}</div>
            </li>
        </ol>

        <form class="flex max-w-2xl gap-2" @submit.prevent="filter">
            <div class="relative flex-1">
                <Search
                    class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="searchText"
                    class="pl-9"
                    placeholder="Order number or supplier"
                />
            </div>
            <Button type="submit" variant="secondary">Filter</Button>
        </form>

        <div class="overflow-x-auto rounded-md border">
            <table class="w-full min-w-[78rem] text-sm">
                <thead class="border-b bg-muted/40 text-left">
                    <tr>
                        <th class="px-4 py-3">Order</th>
                        <th class="px-4 py-3">Supplier</th>
                        <th class="px-4 py-3 text-right">Items</th>
                        <th class="px-4 py-3 text-right">Units</th>
                        <th class="px-4 py-3">Expected</th>
                        <th class="px-4 py-3">
                            {{ isSubmitted ? 'Submitted' : 'Approved' }}
                        </th>
                        <th class="px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="order in orders.data"
                        :key="order.id"
                        class="align-top"
                    >
                        <td class="px-4 py-4">
                            <div class="font-mono font-semibold">
                                {{ order.order_number }}
                            </div>
                            <div
                                class="text-xs text-muted-foreground capitalize"
                            >
                                {{ label(order.source) }}
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="font-medium">
                                {{ order.supplier.name }}
                            </div>
                            <div
                                class="font-mono text-xs text-muted-foreground"
                            >
                                {{ order.supplier.code }}
                            </div>
                        </td>
                        <td class="px-4 py-4 text-right">
                            {{ order.lines_count }}
                        </td>
                        <td class="px-4 py-4 text-right font-medium">
                            {{ order.units_count.toLocaleString() }}
                        </td>
                        <td class="px-4 py-4">
                            {{ formatDateOnly(order.expected_on, 'Not set') }}
                        </td>
                        <td class="px-4 py-4">
                            <div>
                                {{
                                    formatDateTime(
                                        isSubmitted
                                            ? order.submitted_at
                                            : order.decided_at,
                                    )
                                }}
                            </div>
                            <div class="text-xs text-muted-foreground">
                                {{
                                    (isSubmitted
                                        ? order.submitted_by?.name
                                        : order.decided_by?.name) ||
                                    'Former staff member'
                                }}
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex min-w-[25rem] items-start gap-2">
                                <Button size="icon" variant="outline" as-child>
                                    <Link
                                        :href="
                                            show(order.id, {
                                                query: { from: queue },
                                            })
                                        "
                                        :aria-label="`Review ${order.order_number}`"
                                    >
                                        <Eye class="size-4" />
                                    </Link>
                                </Button>

                                <template v-if="order.can_decide">
                                    <Form
                                        v-bind="
                                            PurchaseOrderWorkflowController.decide.form(
                                                order.id,
                                            )
                                        "
                                        :options="{ preserveScroll: true }"
                                        v-slot="{ processing }"
                                    >
                                        <input
                                            type="hidden"
                                            name="decision"
                                            value="approve"
                                        />
                                        <Button
                                            type="submit"
                                            :disabled="processing"
                                        >
                                            <Check class="size-4" />Approve
                                        </Button>
                                    </Form>
                                    <Form
                                        v-bind="
                                            PurchaseOrderWorkflowController.decide.form(
                                                order.id,
                                            )
                                        "
                                        class="flex flex-1 gap-2"
                                        :options="{ preserveScroll: true }"
                                        reset-on-success
                                        v-slot="{ errors, processing }"
                                    >
                                        <input
                                            type="hidden"
                                            name="decision"
                                            value="reject"
                                        />
                                        <div class="min-w-0 flex-1">
                                            <Input
                                                name="reason"
                                                placeholder="Rejection reason"
                                                required
                                            />
                                            <InputError
                                                :message="errors.reason"
                                            />
                                        </div>
                                        <Button
                                            type="submit"
                                            variant="destructive"
                                            :disabled="processing"
                                        >
                                            <X class="size-4" />Reject
                                        </Button>
                                    </Form>
                                </template>

                                <Form
                                    v-else-if="order.can_send"
                                    v-bind="
                                        PurchaseOrderWorkflowController.send.form(
                                            order.id,
                                        )
                                    "
                                    :options="{ preserveScroll: true }"
                                    v-slot="{ processing }"
                                >
                                    <Button
                                        type="submit"
                                        :disabled="processing"
                                    >
                                        <Send class="size-4" />Mark as sent
                                    </Button>
                                </Form>

                                <div
                                    v-else-if="!isSubmitted"
                                    class="flex h-9 items-center gap-2 text-sm text-muted-foreground"
                                >
                                    <ShieldCheck class="size-4" />Awaiting PACE
                                    Officer
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="orders.data.length === 0">
                        <td
                            colspan="7"
                            class="px-4 py-14 text-center text-muted-foreground"
                        >
                            {{ emptyMessage }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between">
            <Button
                variant="outline"
                :disabled="!orders.prev_page_url"
                @click="visitPage(orders.prev_page_url)"
            >
                Previous
            </Button>
            <span class="text-sm text-muted-foreground">
                {{ orders.total }} orders
            </span>
            <Button
                variant="outline"
                :disabled="!orders.next_page_url"
                @click="visitPage(orders.next_page_url)"
            >
                Next
            </Button>
        </div>
    </div>
</template>
