<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Check,
    PackageCheck,
    Plus,
    Send,
    Trash2,
    X,
} from '@lucide/vue';
import { computed } from 'vue';
import GoodsReceiptController from '@/actions/App/Http/Controllers/GoodsReceiptController';
import PurchaseOrderLineController from '@/actions/App/Http/Controllers/PurchaseOrderLineController';
import PurchaseOrderWorkflowController from '@/actions/App/Http/Controllers/PurchaseOrderWorkflowController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PaceSearchSelect from '@/components/PaceSearchSelect.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDateOnly } from '@/lib/utils';
import { index } from '@/routes/purchase-orders';

type InventoryItem = {
    id: number;
    sku: string;
    item_type: string;
    pace: {
        number: string;
        title: string | null;
        course: { name: string; subject: { name: string } };
    } | null;
};
type OrderLine = {
    id: number;
    inventory_item_id: number;
    quantity_ordered: number;
    received_quantity: string | null;
    notes: string | null;
    inventory_item: InventoryItem;
};
type Receipt = {
    id: number;
    receipt_number: string;
    delivery_reference: string;
    received_at: string;
    notes: string | null;
    received_by: { name: string } | null;
    lines: Array<{
        id: number;
        quantity_received: number;
        purchase_order_line: { inventory_item: { sku: string } };
    }>;
};
type Order = {
    id: number;
    order_number: string;
    source: string;
    status: string;
    expected_on: string | null;
    notes: string | null;
    submitted_at: string | null;
    decided_at: string | null;
    decision_reason: string | null;
    sent_at: string | null;
    cancelled_at: string | null;
    cancellation_reason: string | null;
    supplier: { name: string; code: string };
    created_by: { name: string } | null;
    submitted_by: { name: string } | null;
    decided_by: { name: string } | null;
    sent_by: { name: string } | null;
    cancelled_by: { name: string } | null;
    lines: OrderLine[];
    goods_receipts: Receipt[];
};
const props = defineProps<{
    order: Order;
    inventoryItems: InventoryItem[];
    can: {
        update: boolean;
        submit: boolean;
        decide: boolean;
        send: boolean;
        receive: boolean;
        cancel: boolean;
    };
}>();
const localNow = new Date(Date.now() - new Date().getTimezoneOffset() * 60_000)
    .toISOString()
    .slice(0, 16);
const outstandingLines = computed(() =>
    props.order.lines.filter(
        (line) =>
            line.quantity_ordered - Number(line.received_quantity ?? 0) > 0,
    ),
);
function itemLabel(item: InventoryItem): string {
    const type = item.item_type.replaceAll('_', ' ');

    if (!item.pace) {
        return `${item.sku} · ${type}`;
    }

    return `${item.sku} · ${item.pace.course.subject.name} · ${item.pace.course.name} · PACE ${item.pace.number} · ${type}`;
}
function statusLabel(value: string): string {
    return value.replaceAll('_', ' ');
}
function outstanding(line: OrderLine): number {
    return line.quantity_ordered - Number(line.received_quantity ?? 0);
}
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Purchase orders', href: index() },
            { title: 'Order', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="order.order_number" />
    <div class="flex max-w-[1450px] flex-1 flex-col gap-7 p-4 md:p-6">
        <Button variant="ghost" size="sm" class="w-fit" as-child>
            <Link :href="index()">
                <ArrowLeft class="size-4" />Purchase orders
            </Link>
        </Button>
        <div class="flex flex-wrap items-start justify-between gap-3">
            <Heading
                :title="order.order_number"
                :description="`${order.supplier.name} · ${statusLabel(order.source)}`"
            />
            <Badge variant="outline" class="capitalize">{{
                statusLabel(order.status)
            }}</Badge>
        </div>

        <section class="grid gap-4 border-y py-5 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <div class="text-xs text-muted-foreground">Supplier</div>
                <div class="font-medium">{{ order.supplier.name }}</div>
                <div class="font-mono text-xs">{{ order.supplier.code }}</div>
            </div>
            <div>
                <div class="text-xs text-muted-foreground">Expected</div>
                <div class="font-medium">
                    {{ formatDateOnly(order.expected_on, 'Not set') }}
                </div>
            </div>
            <div>
                <div class="text-xs text-muted-foreground">Created by</div>
                <div class="font-medium">
                    {{ order.created_by?.name || 'Former staff member' }}
                </div>
            </div>
            <div>
                <div class="text-xs text-muted-foreground">Notes</div>
                <div class="font-medium">{{ order.notes || '—' }}</div>
            </div>
        </section>

        <section class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-base font-semibold">Order items</h2>
                <Form
                    v-if="can.submit"
                    v-bind="
                        PurchaseOrderWorkflowController.submit.form(order.id)
                    "
                    v-slot="{ processing }"
                >
                    <Button
                        type="submit"
                        :disabled="processing || order.lines.length === 0"
                    >
                        <Send class="size-4" />Submit for approval
                    </Button>
                </Form>
            </div>

            <Form
                v-if="can.update"
                v-bind="PurchaseOrderLineController.store.form(order.id)"
                class="grid gap-3 border-y py-4 md:grid-cols-[1fr_8rem_2fr_auto]"
                reset-on-success
                v-slot="{ errors, processing }"
            >
                <div>
                    <label
                        class="mb-1 block text-xs font-medium"
                        for="order-item"
                        >Inventory item</label
                    >
                    <PaceSearchSelect
                        id="order-item"
                        name="inventory_item_id"
                        :options="
                            inventoryItems.map((item) => ({
                                id: item.id,
                                label: itemLabel(item),
                            }))
                        "
                        placeholder="Search SKU, course, PACE, or item type"
                        empty-message="No matching inventory item found."
                        clear-label="Clear inventory item"
                        required
                    />
                    <InputError :message="errors.inventory_item_id" />
                </div>
                <div>
                    <label
                        class="mb-1 block text-xs font-medium"
                        for="order-quantity"
                        >Quantity</label
                    >
                    <Input
                        id="order-quantity"
                        name="quantity_ordered"
                        type="number"
                        min="1"
                        required
                    />
                </div>
                <div>
                    <label
                        class="mb-1 block text-xs font-medium"
                        for="line-notes"
                        >Line notes</label
                    >
                    <Input id="line-notes" name="notes" />
                </div>
                <Button type="submit" class="self-end" :disabled="processing">
                    <Plus class="size-4" />Add item
                </Button>
            </Form>

            <div class="overflow-x-auto rounded-md border">
                <table class="w-full min-w-4xl text-sm">
                    <thead class="border-b bg-muted/40 text-left">
                        <tr>
                            <th class="px-4 py-3">Item</th>
                            <th class="px-4 py-3">Course / PACE</th>
                            <th class="px-4 py-3 text-right">Ordered</th>
                            <th class="px-4 py-3 text-right">Received</th>
                            <th class="px-4 py-3 text-right">Outstanding</th>
                            <th class="px-4 py-3">Notes</th>
                            <th v-if="can.update"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="line in order.lines" :key="line.id">
                            <td class="px-4 py-3">
                                <div class="font-mono font-semibold">
                                    {{ line.inventory_item.sku }}
                                </div>
                                <div
                                    class="text-xs text-muted-foreground capitalize"
                                >
                                    {{
                                        line.inventory_item.item_type.replaceAll(
                                            '_',
                                            ' ',
                                        )
                                    }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <template v-if="line.inventory_item.pace">
                                    {{ line.inventory_item.pace.course.name }} ·
                                    PACE {{ line.inventory_item.pace.number }}
                                </template>
                                <template v-else>—</template>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Form
                                    v-if="can.update"
                                    v-bind="
                                        PurchaseOrderLineController.update.form(
                                            line.id,
                                        )
                                    "
                                    class="flex justify-end gap-2"
                                    v-slot="{ processing }"
                                >
                                    <Input
                                        name="quantity_ordered"
                                        type="number"
                                        min="1"
                                        :default-value="line.quantity_ordered"
                                        class="w-24 text-right"
                                        required
                                    />
                                    <input
                                        type="hidden"
                                        name="notes"
                                        :value="line.notes ?? ''"
                                    />
                                    <Button
                                        type="submit"
                                        size="sm"
                                        variant="secondary"
                                        :disabled="processing"
                                        >Save</Button
                                    >
                                </Form>
                                <span v-else>{{ line.quantity_ordered }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                {{ Number(line.received_quantity ?? 0) }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold">
                                {{ outstanding(line) }}
                            </td>
                            <td class="px-4 py-3">{{ line.notes || '—' }}</td>
                            <td v-if="can.update" class="px-4 py-3">
                                <Form
                                    v-bind="
                                        PurchaseOrderLineController.destroy.form(
                                            line.id,
                                        )
                                    "
                                    v-slot="{ processing }"
                                >
                                    <Button
                                        type="submit"
                                        size="icon"
                                        variant="ghost"
                                        :disabled="processing"
                                        :aria-label="`Remove ${line.inventory_item.sku}`"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </Form>
                            </td>
                        </tr>
                        <tr v-if="order.lines.length === 0">
                            <td
                                :colspan="can.update ? 7 : 6"
                                class="px-4 py-14 text-center text-muted-foreground"
                            >
                                Add PACE booklets or Score Keys to this draft.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section
            v-if="can.decide"
            class="grid gap-5 border-y py-5 lg:grid-cols-2"
        >
            <Form
                v-bind="PurchaseOrderWorkflowController.decide.form(order.id)"
                v-slot="{ processing }"
            >
                <input type="hidden" name="decision" value="approve" />
                <Button type="submit" :disabled="processing">
                    <Check class="size-4" />Approve order
                </Button>
            </Form>
            <Form
                v-bind="PurchaseOrderWorkflowController.decide.form(order.id)"
                class="flex gap-2"
                v-slot="{ errors, processing }"
            >
                <input type="hidden" name="decision" value="reject" />
                <div class="flex-1">
                    <Input
                        name="reason"
                        placeholder="Reason for rejection"
                        required
                    />
                    <InputError :message="errors.reason" />
                </div>
                <Button
                    type="submit"
                    variant="destructive"
                    :disabled="processing"
                >
                    <X class="size-4" />Reject
                </Button>
            </Form>
        </section>

        <section v-if="can.send" class="border-y py-5">
            <Form
                v-bind="PurchaseOrderWorkflowController.send.form(order.id)"
                v-slot="{ processing }"
            >
                <Button type="submit" :disabled="processing">
                    <Send class="size-4" />Mark order as sent
                </Button>
            </Form>
        </section>

        <section v-if="can.receive" class="space-y-4 border-y py-5">
            <h2 class="text-base font-semibold">Receive delivery</h2>
            <Form
                v-bind="GoodsReceiptController.store.form(order.id)"
                class="space-y-4"
                reset-on-success
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-3 md:grid-cols-3">
                    <div>
                        <label
                            for="delivery-reference"
                            class="mb-1 block text-xs font-medium"
                            >Delivery reference</label
                        >
                        <Input
                            id="delivery-reference"
                            name="delivery_reference"
                            required
                        />
                    </div>
                    <div>
                        <label
                            for="received-at"
                            class="mb-1 block text-xs font-medium"
                            >Received at</label
                        >
                        <Input
                            id="received-at"
                            name="received_at"
                            type="datetime-local"
                            :default-value="localNow"
                            required
                        />
                    </div>
                    <div>
                        <label
                            for="receipt-notes"
                            class="mb-1 block text-xs font-medium"
                            >Receipt notes</label
                        >
                        <Input id="receipt-notes" name="notes" />
                    </div>
                </div>
                <div class="overflow-x-auto rounded-md border">
                    <table class="w-full min-w-2xl text-sm">
                        <thead class="border-b bg-muted/40 text-left">
                            <tr>
                                <th class="px-4 py-3">Item</th>
                                <th class="px-4 py-3 text-right">
                                    Outstanding
                                </th>
                                <th class="w-44 px-4 py-3 text-right">
                                    Received now
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="(line, position) in outstandingLines"
                                :key="line.id"
                            >
                                <td class="px-4 py-3 font-mono">
                                    {{ line.inventory_item.sku }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    {{ outstanding(line) }}
                                </td>
                                <td class="px-4 py-3">
                                    <input
                                        type="hidden"
                                        :name="`lines[${position}][purchase_order_line_id]`"
                                        :value="line.id"
                                    />
                                    <Input
                                        :name="`lines[${position}][quantity_received]`"
                                        type="number"
                                        min="0"
                                        :max="outstanding(line)"
                                        :default-value="0"
                                        class="text-right"
                                        required
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <InputError
                    :message="
                        errors.lines ||
                        errors.delivery_reference ||
                        errors.received_at
                    "
                />
                <Button type="submit" :disabled="processing">
                    <PackageCheck class="size-4" />Post goods receipt
                </Button>
            </Form>
        </section>

        <section v-if="order.goods_receipts.length" class="space-y-4">
            <h2 class="text-base font-semibold">Goods receipts</h2>
            <div class="overflow-x-auto rounded-md border">
                <table class="w-full min-w-3xl text-sm">
                    <thead class="border-b bg-muted/40 text-left">
                        <tr>
                            <th class="px-4 py-3">Receipt</th>
                            <th class="px-4 py-3">Delivery reference</th>
                            <th class="px-4 py-3">Received</th>
                            <th class="px-4 py-3">By</th>
                            <th class="px-4 py-3">Items</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="receipt in order.goods_receipts"
                            :key="receipt.id"
                        >
                            <td class="px-4 py-3 font-mono font-semibold">
                                {{ receipt.receipt_number }}
                            </td>
                            <td class="px-4 py-3">
                                {{ receipt.delivery_reference }}
                            </td>
                            <td class="px-4 py-3">
                                {{
                                    new Date(
                                        receipt.received_at,
                                    ).toLocaleString()
                                }}
                            </td>
                            <td class="px-4 py-3">
                                {{
                                    receipt.received_by?.name || 'Former staff'
                                }}
                            </td>
                            <td class="px-4 py-3">
                                {{
                                    receipt.lines
                                        .map(
                                            (line) =>
                                                `${line.purchase_order_line.inventory_item.sku} × ${line.quantity_received}`,
                                        )
                                        .join(', ')
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section v-if="can.cancel" class="border-t pt-5">
            <Form
                v-bind="PurchaseOrderWorkflowController.cancel.form(order.id)"
                class="flex max-w-xl gap-2"
                v-slot="{ errors, processing }"
            >
                <div class="flex-1">
                    <Input
                        name="reason"
                        placeholder="Cancellation reason"
                        required
                    />
                    <InputError :message="errors.reason" />
                </div>
                <Button
                    type="submit"
                    variant="destructive"
                    :disabled="processing"
                >
                    <X class="size-4" />Cancel order
                </Button>
            </Form>
        </section>
    </div>
</template>
