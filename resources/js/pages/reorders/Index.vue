<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ClipboardList, PackagePlus } from '@lucide/vue';
import { reactive } from 'vue';
import PurchaseOrderController from '@/actions/App/Http/Controllers/PurchaseOrderController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index as purchaseOrdersIndex } from '@/routes/purchase-orders';
import { index } from '@/routes/reorders';

type Supplier = { id: number; name: string; code: string };
type Item = {
    id: number;
    sku: string;
    reorder_level: number;
    target_stock_level: number;
    on_hand: number;
    on_order: number;
    suggested_quantity: number;
    item_type: string;
    pace: {
        number: string;
        title: string | null;
        course: { name: string; subject: { name: string } };
    } | null;
};
const props = defineProps<{ items: Item[]; suppliers: Supplier[] }>();
const selected = reactive<Record<number, boolean>>({});
const quantities = reactive<Record<number, number>>({});

for (const item of props.items) {
    selected[item.id] = true;
    quantities[item.id] = item.suggested_quantity;
}

defineOptions({
    layout: { breadcrumbs: [{ title: 'Reorder queue', href: index() }] },
});
</script>

<template>
    <Head title="Reorder queue" />
    <div class="flex max-w-[1450px] flex-1 flex-col gap-7 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <Heading
                title="Reorder queue"
                description="Items at or below their reorder point, adjusted for quantities already on order"
            />
            <Button variant="outline" as-child>
                <Link :href="purchaseOrdersIndex()">
                    <ClipboardList class="size-4" />Purchase orders
                </Link>
            </Button>
        </div>

        <Form
            v-if="items.length"
            v-bind="PurchaseOrderController.store.form()"
            class="space-y-5"
            v-slot="{ errors, processing }"
        >
            <input type="hidden" name="source" value="reorder" />
            <div class="grid gap-3 border-y py-4 md:grid-cols-[1fr_12rem_2fr]">
                <div class="grid gap-1">
                    <label for="reorder-supplier" class="text-sm font-medium"
                        >Supplier</label
                    >
                    <select
                        id="reorder-supplier"
                        name="supplier_id"
                        class="h-9 rounded-md border bg-transparent px-3 text-sm"
                        required
                    >
                        <option value="">Select supplier</option>
                        <option
                            v-for="supplier in suppliers"
                            :key="supplier.id"
                            :value="supplier.id"
                        >
                            {{ supplier.name }} · {{ supplier.code }}
                        </option>
                    </select>
                    <InputError :message="errors.supplier_id" />
                </div>
                <div class="grid gap-1">
                    <label for="reorder-expected" class="text-sm font-medium"
                        >Expected date</label
                    >
                    <Input
                        id="reorder-expected"
                        name="expected_on"
                        type="date"
                    />
                </div>
                <div class="grid gap-1">
                    <label for="reorder-notes" class="text-sm font-medium"
                        >Notes</label
                    >
                    <Input id="reorder-notes" name="notes" />
                </div>
            </div>

            <div class="overflow-x-auto rounded-md border">
                <table class="w-full min-w-5xl text-sm">
                    <thead class="border-b bg-muted/40 text-left">
                        <tr>
                            <th class="w-12 px-4 py-3"></th>
                            <th class="px-4 py-3">Item</th>
                            <th class="px-4 py-3">Course</th>
                            <th class="px-4 py-3 text-right">On hand</th>
                            <th class="px-4 py-3 text-right">On order</th>
                            <th class="px-4 py-3 text-right">Reorder point</th>
                            <th class="px-4 py-3 text-right">Target</th>
                            <th class="w-36 px-4 py-3 text-right">Order qty</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="item in items" :key="item.id">
                            <td class="px-4 py-3">
                                <input
                                    v-model="selected[item.id]"
                                    type="checkbox"
                                    class="size-4 accent-primary"
                                    :aria-label="`Select ${item.sku}`"
                                />
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-mono font-semibold">
                                    {{ item.sku }}
                                </div>
                                <div
                                    class="text-xs text-muted-foreground capitalize"
                                >
                                    {{ item.item_type.replaceAll('_', ' ') }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                {{ item.pace?.course.name || '—' }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono">
                                {{ item.on_hand }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono">
                                {{ item.on_order }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono">
                                {{ item.reorder_level }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono">
                                {{ item.target_stock_level }}
                            </td>
                            <td class="px-4 py-3">
                                <template v-if="selected[item.id]">
                                    <input
                                        type="hidden"
                                        :name="`lines[${item.id}][inventory_item_id]`"
                                        :value="item.id"
                                    />
                                    <Input
                                        v-model.number="quantities[item.id]"
                                        :name="`lines[${item.id}][quantity_ordered]`"
                                        type="number"
                                        min="1"
                                        max="100000"
                                        class="text-right"
                                        required
                                    />
                                </template>
                                <span
                                    v-else
                                    class="block text-right text-muted-foreground"
                                    >Excluded</span
                                >
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <InputError :message="errors.lines" />
            <Button
                type="submit"
                :disabled="
                    processing ||
                    suppliers.length === 0 ||
                    !Object.values(selected).some(Boolean)
                "
            >
                <PackagePlus class="size-4" />Create draft order
            </Button>
        </Form>
        <div
            v-else
            class="border-y py-20 text-center text-sm text-muted-foreground"
        >
            No items currently require reordering.
        </div>
    </div>
</template>
