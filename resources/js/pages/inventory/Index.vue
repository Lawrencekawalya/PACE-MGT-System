<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { AlertTriangle, Eye, Package, Plus, Search } from '@lucide/vue';
import { ref } from 'vue';
import InventoryItemController from '@/actions/App/Http/Controllers/InventoryItemController';
import Heading from '@/components/Heading.vue';
import PaceSearchSelect from '@/components/PaceSearchSelect.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index, ledger } from '@/routes/inventory';
import { show } from '@/routes/inventory-items';

type Item = {
    id: number;
    sku: string;
    item_type: string;
    reorder_level: number;
    is_consumable: boolean;
    is_active: boolean;
    on_hand: string | null;
    issued_quantity: string | null;
    pace: {
        number: string;
        title: string | null;
        course: { name: string; subject: { name: string } };
    } | null;
};
type PaceOption = {
    id: number;
    number: string;
    title: string | null;
    course: { name: string; subject: { name: string } };
};
const props = defineProps<{
    items: {
        data: Item[];
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    filters: {
        search: string;
        item_type: string;
        stock: string;
        active: string;
    };
    itemTypes: Array<{ value: string; label: string }>;
    scoreKeyPaces: PaceOption[];
    summary: {
        items: number;
        on_hand: number;
        out_of_stock: number;
        low_stock: number;
    };
    canAdjust: boolean;
}>();
const search = ref(props.filters.search);
const itemType = ref(props.filters.item_type);
const stock = ref(props.filters.stock);
const active = ref(props.filters.active);
function filter(): void {
    router.get(
        index().url,
        {
            search: search.value,
            item_type: itemType.value,
            stock: stock.value,
            active: active.value,
        },
        { preserveState: true, replace: true },
    );
}
function setStock(value: string): void {
    stock.value = value;
    filter();
}
function onHand(item: Item): number {
    return Number(item.on_hand ?? 0);
}
function paceLabel(pace: PaceOption): string {
    return `${pace.course.subject.name} · ${pace.course.name} · PACE ${pace.number}${pace.title ? ` · ${pace.title}` : ''}`;
}
defineOptions({
    layout: { breadcrumbs: [{ title: 'Inventory', href: index() }] },
});
</script>

<template>
    <Head title="Inventory" />
    <div class="flex max-w-[1500px] flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <Heading
                title="Inventory"
                description="PACE booklets, Score Keys, stock levels, and reorder exceptions"
            /><Button variant="outline" as-child
                ><Link :href="ledger()"
                    ><Package class="size-4" />Movement ledger</Link
                ></Button
            >
        </div>
        <div
            class="grid grid-cols-2 gap-px overflow-hidden rounded-md border bg-border lg:grid-cols-4"
        >
            <button
                type="button"
                class="bg-background p-4 text-left"
                @click="setStock('')"
            >
                <div class="text-2xl font-semibold">{{ summary.items }}</div>
                <div class="text-xs text-muted-foreground">
                    Active items
                </div></button
            ><button
                type="button"
                class="bg-background p-4 text-left"
                @click="setStock('available')"
            >
                <div class="text-2xl font-semibold">{{ summary.on_hand }}</div>
                <div class="text-xs text-muted-foreground">
                    Units on hand
                </div></button
            ><button
                type="button"
                class="bg-background p-4 text-left"
                @click="setStock('low')"
            >
                <div class="flex items-center gap-2 text-2xl font-semibold">
                    <AlertTriangle class="size-5 text-amber-600" />{{
                        summary.low_stock
                    }}
                </div>
                <div class="text-xs text-muted-foreground">
                    Low stock
                </div></button
            ><button
                type="button"
                class="bg-background p-4 text-left"
                @click="setStock('out')"
            >
                <div class="text-2xl font-semibold text-destructive">
                    {{ summary.out_of_stock }}
                </div>
                <div class="text-xs text-muted-foreground">Out of stock</div>
            </button>
        </div>
        <Form
            v-if="canAdjust"
            v-bind="InventoryItemController.store.form()"
            class="grid gap-3 border-y py-4 lg:grid-cols-[minmax(16rem,2fr)_minmax(12rem,1fr)_10rem_auto]"
            reset-on-success
            v-slot="{ errors, processing }"
            ><input type="hidden" name="item_type" value="score_key" /><input
                type="hidden"
                name="is_consumable"
                value="0"
            /><input type="hidden" name="is_active" value="1" />
            <div>
                <label
                    class="mb-1 block text-xs font-medium"
                    for="score-key-pace"
                    >Course and PACE</label
                ><PaceSearchSelect
                    id="score-key-pace"
                    :options="
                        scoreKeyPaces.map((pace) => ({
                            id: pace.id,
                            label: paceLabel(pace),
                        }))
                    "
                    placeholder="Search subject, course, or PACE"
                    required
                />
                <span class="text-xs text-destructive">{{
                    errors.pace_id
                }}</span>
            </div>
            <div>
                <label
                    class="mb-1 block text-xs font-medium"
                    for="score-key-sku"
                    >Score Key SKU</label
                >
                <Input
                    id="score-key-sku"
                    name="sku"
                    placeholder="e.g. SK-MATH-1008"
                    required
                /><span class="text-xs text-destructive">{{ errors.sku }}</span>
            </div>
            <div>
                <label
                    class="mb-1 block text-xs font-medium"
                    for="score-key-reorder"
                    >Reorder level</label
                ><Input
                    id="score-key-reorder"
                    name="reorder_level"
                    type="number"
                    min="0"
                    :default-value="0"
                    required
                /><span class="text-xs text-destructive">{{
                    errors.reorder_level
                }}</span>
            </div>
            <Button class="self-end" type="submit" :disabled="processing"
                ><Plus class="size-4" />Add item</Button
            ></Form
        >
        <form
            class="grid gap-2 lg:grid-cols-[2fr_1fr_1fr_1fr_auto]"
            @submit.prevent="filter"
        >
            <div class="relative">
                <Search
                    class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                /><Input
                    v-model="search"
                    class="pl-9"
                    placeholder="SKU, PACE, title, or course"
                />
            </div>
            <select
                v-model="itemType"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">All item types</option>
                <option
                    v-for="type in itemTypes"
                    :key="type.value"
                    :value="type.value"
                >
                    {{ type.label }}
                </option></select
            ><select
                v-model="stock"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">Any stock level</option>
                <option value="available">Available</option>
                <option value="low">Low stock</option>
                <option value="out">Out of stock</option></select
            ><select
                v-model="active"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">Any status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option></select
            ><Button type="submit" variant="secondary">Filter</Button>
        </form>
        <div class="overflow-x-auto rounded-md border">
            <table class="w-full min-w-5xl text-sm">
                <thead class="border-b bg-muted/40 text-left">
                    <tr>
                        <th class="px-4 py-3">Item</th>
                        <th class="px-4 py-3">Course</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3 text-right">On hand</th>
                        <th class="px-4 py-3 text-right">Issued</th>
                        <th class="px-4 py-3 text-right">Reorder</th>
                        <th class="px-4 py-3">Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="item in items.data" :key="item.id">
                        <td class="px-4 py-3">
                            <div class="font-mono font-semibold">
                                {{ item.sku }}
                            </div>
                            <div class="text-xs text-muted-foreground">
                                {{
                                    item.pace
                                        ? `PACE ${item.pace.number}`
                                        : 'General inventory'
                                }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            {{ item.pace?.course.name || '—' }}
                        </td>
                        <td class="px-4 py-3">
                            {{ item.item_type.replaceAll('_', ' ') }}
                        </td>
                        <td
                            class="px-4 py-3 text-right font-mono font-semibold"
                            :class="
                                onHand(item) <= item.reorder_level
                                    ? 'text-amber-700'
                                    : ''
                            "
                        >
                            {{ onHand(item) }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono">
                            {{ Math.abs(Number(item.issued_quantity ?? 0)) }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono">
                            {{ item.reorder_level }}
                        </td>
                        <td class="px-4 py-3">
                            <Badge
                                :variant="
                                    item.is_active ? 'outline' : 'secondary'
                                "
                                >{{
                                    item.is_active ? 'Active' : 'Inactive'
                                }}</Badge
                            >
                        </td>
                        <td class="px-4 py-3">
                            <Button size="icon" variant="ghost" as-child
                                ><Link
                                    :href="show(item.id)"
                                    :aria-label="`View ${item.sku}`"
                                    ><Eye class="size-4" /></Link
                            ></Button>
                        </td>
                    </tr>
                    <tr v-if="items.data.length === 0">
                        <td
                            colspan="8"
                            class="px-4 py-14 text-center text-muted-foreground"
                        >
                            No inventory items match these filters.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="flex justify-between">
            <Button
                variant="outline"
                :disabled="!items.prev_page_url"
                @click="items.prev_page_url && router.get(items.prev_page_url)"
                >Previous</Button
            ><span class="text-sm text-muted-foreground"
                >{{ items.total }} items</span
            ><Button
                variant="outline"
                :disabled="!items.next_page_url"
                @click="items.next_page_url && router.get(items.next_page_url)"
                >Next</Button
            >
        </div>
    </div>
</template>
