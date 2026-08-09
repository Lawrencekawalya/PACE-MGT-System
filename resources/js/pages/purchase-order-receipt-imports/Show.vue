<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowLeft,
    CheckCircle2,
    FileSpreadsheet,
    PackageCheck,
    XCircle,
} from '@lucide/vue';
import PurchaseOrderReceiptImportController from '@/actions/App/Http/Controllers/PurchaseOrderReceiptImportController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { sent } from '@/routes/purchase-orders';

type ImportRow = {
    id: number;
    row_number: number;
    status: 'valid' | 'skipped' | 'invalid';
    raw_data: Record<string, unknown>;
    normalized_data: {
        sku: string;
        item_type: string;
        subject: string | null;
        course: string | null;
        pace_number: string | null;
        pace_title: string | null;
        quantity_ordered: number;
        previously_received: number;
        quantity_received: number;
        outstanding_before: number;
        outstanding_after: number;
        excess_quantity: number;
    } | null;
    errors: string[] | null;
};

type ReceiptImport = {
    id: number;
    original_name: string;
    checksum: string;
    status: string;
    valid_rows: number;
    skipped_rows: number;
    invalid_rows: number;
    failure_reason: string | null;
    created_at: string;
    committed_at: string | null;
    purchase_order: {
        id: number;
        order_number: string;
        supplier: { name: string; code: string };
    };
    uploader: { name: string };
    committer: { name: string } | null;
    goods_receipt: { id: number; receipt_number: string } | null;
    rows: ImportRow[];
};

defineProps<{
    receiptImport: ReceiptImport;
    canCommit: boolean;
    canCancel: boolean;
}>();

const localNow = new Date(Date.now() - new Date().getTimezoneOffset() * 60_000)
    .toISOString()
    .slice(0, 16);

function rowValue(row: ImportRow, key: string): string | number {
    const value =
        row.normalized_data?.[
            key as keyof NonNullable<ImportRow['normalized_data']>
        ] ?? row.raw_data[key];

    return typeof value === 'string' || typeof value === 'number' ? value : '—';
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

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Sent orders', href: sent().url },
            { title: 'Delivery import', href: '#' },
        ],
    },
});
</script>

<template>
    <Head
        :title="`Delivery import · ${receiptImport.purchase_order.order_number}`"
    />
    <div class="flex max-w-[1600px] flex-1 flex-col gap-6 p-4 md:p-6">
        <div>
            <Button variant="ghost" as-child class="mb-3">
                <Link :href="sent()"
                    ><ArrowLeft class="size-4" />Sent orders</Link
                >
            </Button>
            <Heading
                title="Delivery import"
                :description="`${receiptImport.purchase_order.order_number} · ${receiptImport.purchase_order.supplier.name}`"
            />
        </div>

        <section class="grid border md:grid-cols-4">
            <div class="border-b p-4 md:border-r md:border-b-0">
                <div class="text-xs text-muted-foreground">File</div>
                <div class="mt-1 flex items-center gap-2 font-medium">
                    <FileSpreadsheet class="size-4" />{{
                        receiptImport.original_name
                    }}
                </div>
            </div>
            <div class="border-b p-4 md:border-r md:border-b-0">
                <div class="text-xs text-muted-foreground">Uploaded</div>
                <div class="mt-1 font-medium">
                    {{ formatDateTime(receiptImport.created_at) }}
                </div>
                <div class="text-xs text-muted-foreground">
                    {{ receiptImport.uploader.name }}
                </div>
            </div>
            <div class="border-b p-4 md:border-r md:border-b-0">
                <div class="text-xs text-muted-foreground">Status</div>
                <Badge
                    class="mt-1 capitalize"
                    :variant="
                        receiptImport.status === 'failed'
                            ? 'destructive'
                            : 'outline'
                    "
                >
                    {{ receiptImport.status }}
                </Badge>
            </div>
            <div class="p-4">
                <div class="text-xs text-muted-foreground">
                    SHA-256 audit checksum
                </div>
                <div
                    class="mt-1 truncate font-mono text-xs"
                    :title="receiptImport.checksum"
                >
                    {{ receiptImport.checksum }}
                </div>
            </div>
        </section>

        <section class="grid border md:grid-cols-3">
            <div
                class="flex items-center gap-3 border-b p-4 md:border-r md:border-b-0"
            >
                <CheckCircle2 class="size-5 text-emerald-500" />
                <div>
                    <div class="text-2xl font-semibold">
                        {{ receiptImport.valid_rows }}
                    </div>
                    <div class="text-sm text-muted-foreground">
                        Delivery lines
                    </div>
                </div>
            </div>
            <div
                class="flex items-center gap-3 border-b p-4 md:border-r md:border-b-0"
            >
                <FileSpreadsheet class="size-5 text-muted-foreground" />
                <div>
                    <div class="text-2xl font-semibold">
                        {{ receiptImport.skipped_rows }}
                    </div>
                    <div class="text-sm text-muted-foreground">
                        Zero-quantity lines
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3 p-4">
                <XCircle class="size-5 text-red-500" />
                <div>
                    <div class="text-2xl font-semibold">
                        {{ receiptImport.invalid_rows }}
                    </div>
                    <div class="text-sm text-muted-foreground">
                        Invalid lines
                    </div>
                </div>
            </div>
        </section>

        <div
            v-if="receiptImport.failure_reason"
            class="flex gap-3 border border-red-500/40 bg-red-500/5 p-4 text-sm"
        >
            <AlertTriangle class="size-5 shrink-0 text-red-500" />
            <div>
                <div class="font-medium">The delivery cannot be posted</div>
                <div class="text-muted-foreground">
                    {{ receiptImport.failure_reason }}
                </div>
            </div>
        </div>

        <section class="space-y-3">
            <h2 class="text-lg font-semibold">Validation preview</h2>
            <div class="overflow-x-auto rounded-md border">
                <table class="w-full min-w-[82rem] text-sm">
                    <thead class="border-b bg-muted/40 text-left">
                        <tr>
                            <th class="px-4 py-3">Row</th>
                            <th class="px-4 py-3">Item</th>
                            <th class="px-4 py-3">Course / PACE</th>
                            <th class="px-4 py-3 text-right">Ordered</th>
                            <th class="px-4 py-3 text-right">
                                Previously received
                            </th>
                            <th class="px-4 py-3 text-right">Delivered now</th>
                            <th class="px-4 py-3 text-right">
                                Outstanding after
                            </th>
                            <th class="px-4 py-3">Result</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="row in receiptImport.rows"
                            :key="row.id"
                            class="align-top"
                        >
                            <td class="px-4 py-3 font-mono">
                                {{ row.row_number }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-mono font-medium">
                                    {{ rowValue(row, 'sku') }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ rowValue(row, 'item_type') }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div>{{ rowValue(row, 'course') }}</div>
                                <div class="text-xs text-muted-foreground">
                                    {{ rowValue(row, 'pace_number') }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                {{ rowValue(row, 'quantity_ordered') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                {{ rowValue(row, 'previously_received') }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold">
                                {{ rowValue(row, 'quantity_received') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                {{ rowValue(row, 'outstanding_after') }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge
                                    :variant="
                                        row.status === 'invalid'
                                            ? 'destructive'
                                            : 'outline'
                                    "
                                    class="capitalize"
                                    >{{ row.status }}</Badge
                                >
                                <div
                                    v-if="
                                        Number(
                                            rowValue(row, 'excess_quantity'),
                                        ) > 0
                                    "
                                    class="mt-2 text-xs font-medium text-amber-600 dark:text-amber-400"
                                >
                                    {{ rowValue(row, 'excess_quantity') }} extra
                                    units accepted into stock
                                </div>
                                <ul
                                    v-if="row.errors?.length"
                                    class="mt-2 space-y-1 text-xs text-red-500"
                                >
                                    <li
                                        v-for="error in row.errors"
                                        :key="error"
                                    >
                                        {{ error }}
                                    </li>
                                </ul>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section v-if="canCommit" class="space-y-4 border-t pt-6">
            <div>
                <h2 class="text-lg font-semibold">Post goods receipt</h2>
                <p class="text-sm text-muted-foreground">
                    Confirm the physical delivery details. This action adds the
                    validated quantities to stock and cannot be repeated.
                </p>
            </div>
            <Form
                v-bind="
                    PurchaseOrderReceiptImportController.commit.form(
                        receiptImport.id,
                    )
                "
                class="grid gap-4 md:grid-cols-2"
                v-slot="{ errors, processing }"
            >
                <div class="space-y-2">
                    <label for="delivery_reference" class="text-sm font-medium"
                        >Delivery reference</label
                    ><Input
                        id="delivery_reference"
                        name="delivery_reference"
                        placeholder="Invoice, delivery note, or waybill number"
                        required
                    /><InputError :message="errors.delivery_reference" />
                </div>
                <div class="space-y-2">
                    <label for="received_at" class="text-sm font-medium"
                        >Received at</label
                    ><Input
                        id="received_at"
                        name="received_at"
                        type="datetime-local"
                        :default-value="localNow"
                        :max="localNow"
                        step="60"
                        required
                    /><InputError :message="errors.received_at" />
                </div>
                <div class="space-y-2 md:col-span-2">
                    <label for="notes" class="text-sm font-medium"
                        >Receipt notes</label
                    ><textarea
                        id="notes"
                        name="notes"
                        rows="3"
                        class="flex w-full border border-input bg-transparent px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        placeholder="Condition, shortages, or receiving notes"
                    /><InputError :message="errors.notes" />
                </div>
                <div class="md:col-span-2">
                    <Button type="submit" :disabled="processing"
                        ><PackageCheck class="size-4" />Post goods
                        receipt</Button
                    >
                </div>
            </Form>
        </section>

        <section
            v-if="receiptImport.status === 'committed'"
            class="flex items-center gap-3 border border-emerald-500/40 bg-emerald-500/5 p-4"
        >
            <CheckCircle2 class="size-5 text-emerald-500" />
            <div>
                <div class="font-medium">
                    {{ receiptImport.goods_receipt?.receipt_number }} posted
                </div>
                <div class="text-sm text-muted-foreground">
                    {{ formatDateTime(receiptImport.committed_at) }} by
                    {{ receiptImport.committer?.name }}
                </div>
            </div>
        </section>

        <Form
            v-if="canCancel"
            v-bind="
                PurchaseOrderReceiptImportController.cancel.form(
                    receiptImport.id,
                )
            "
            v-slot="{ processing }"
        >
            <Button type="submit" variant="outline" :disabled="processing"
                >Cancel this import</Button
            >
        </Form>
    </div>
</template>
