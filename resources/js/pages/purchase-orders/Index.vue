<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { Eye, Plus, Search } from '@lucide/vue';
import { ref } from 'vue';
import PurchaseOrderController from '@/actions/App/Http/Controllers/PurchaseOrderController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDateOnly } from '@/lib/utils';
import { index, show } from '@/routes/purchase-orders';

type Supplier = { id: number; name: string; code: string };
type Order = {
    id: number;
    order_number: string;
    source: string;
    status: string;
    expected_on: string | null;
    created_at: string;
    lines_count: number;
    supplier: Supplier;
    created_by: { name: string } | null;
};
const props = defineProps<{
    orders: {
        data: Order[];
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    filters: { search: string; status: string };
    statuses: Array<{ value: string; label: string }>;
    suppliers: Supplier[];
    canCreate: boolean;
}>();
const searchText = ref(props.filters.search);
const status = ref(props.filters.status);
function filter(): void {
    router.get(
        index().url,
        { search: searchText.value, status: status.value },
        { preserveState: true, replace: true },
    );
}
function label(value: string): string {
    return value.replaceAll('_', ' ');
}
defineOptions({
    layout: { breadcrumbs: [{ title: 'Purchase orders', href: index() }] },
});
</script>

<template>
    <Head title="Purchase orders" />
    <div class="flex max-w-[1450px] flex-1 flex-col gap-7 p-4 md:p-6">
        <Heading
            title="Purchase orders"
            description="Prepare, approve, send, and receive stock orders"
        />

        <section v-if="canCreate" class="space-y-4 border-y py-5">
            <h2 class="font-semibold">New manual order</h2>
            <Form
                v-bind="PurchaseOrderController.store.form()"
                class="grid gap-3 lg:grid-cols-[1fr_12rem_2fr_auto]"
                v-slot="{ errors, processing }"
            >
                <input type="hidden" name="source" value="manual" />
                <div class="grid gap-1">
                    <Label for="supplier">Supplier</Label>
                    <select
                        id="supplier"
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
                    <Label for="expected-on">Expected date</Label>
                    <Input id="expected-on" name="expected_on" type="date" />
                </div>
                <div class="grid gap-1">
                    <Label for="order-notes">Notes</Label>
                    <Input id="order-notes" name="notes" />
                </div>
                <Button
                    type="submit"
                    class="self-end"
                    :disabled="processing || suppliers.length === 0"
                >
                    <Plus class="size-4" />Create draft
                </Button>
            </Form>
        </section>

        <form
            class="grid gap-2 sm:grid-cols-[minmax(15rem,1fr)_14rem_auto]"
            @submit.prevent="filter"
        >
            <div class="relative">
                <Search
                    class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="searchText"
                    class="pl-9"
                    placeholder="Order number or supplier"
                />
            </div>
            <select
                v-model="status"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">All statuses</option>
                <option
                    v-for="option in statuses"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </option>
            </select>
            <Button type="submit" variant="secondary">Filter</Button>
        </form>

        <div class="overflow-x-auto rounded-md border">
            <table class="w-full min-w-4xl text-sm">
                <thead class="border-b bg-muted/40 text-left">
                    <tr>
                        <th class="px-4 py-3">Order</th>
                        <th class="px-4 py-3">Supplier</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Items</th>
                        <th class="px-4 py-3">Expected</th>
                        <th class="px-4 py-3">Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="order in orders.data" :key="order.id">
                        <td class="px-4 py-3">
                            <div class="font-mono font-semibold">
                                {{ order.order_number }}
                            </div>
                            <div class="text-xs text-muted-foreground">
                                {{ label(order.source) }}
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ order.supplier.name }}</td>
                        <td class="px-4 py-3">
                            <Badge variant="outline">{{
                                label(order.status)
                            }}</Badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            {{ order.lines_count }}
                        </td>
                        <td class="px-4 py-3">
                            {{ formatDateOnly(order.expected_on) }}
                        </td>
                        <td class="px-4 py-3">
                            {{
                                new Date(order.created_at).toLocaleDateString()
                            }}
                        </td>
                        <td class="px-4 py-3">
                            <Button size="icon" variant="ghost" as-child>
                                <Link
                                    :href="show(order.id)"
                                    :aria-label="`View ${order.order_number}`"
                                >
                                    <Eye class="size-4" />
                                </Link>
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="orders.data.length === 0">
                        <td
                            colspan="7"
                            class="px-4 py-14 text-center text-muted-foreground"
                        >
                            No purchase orders match these filters.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="flex items-center justify-between">
            <Button
                variant="outline"
                :disabled="!orders.prev_page_url"
                @click="
                    orders.prev_page_url && router.get(orders.prev_page_url)
                "
                >Previous</Button
            >
            <span class="text-sm text-muted-foreground"
                >{{ orders.total }} orders</span
            >
            <Button
                variant="outline"
                :disabled="!orders.next_page_url"
                @click="
                    orders.next_page_url && router.get(orders.next_page_url)
                "
                >Next</Button
            >
        </div>
    </div>
</template>
