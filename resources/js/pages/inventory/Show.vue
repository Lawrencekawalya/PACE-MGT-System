<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, RotateCcw, Save } from '@lucide/vue';
import InventoryItemController from '@/actions/App/Http/Controllers/InventoryItemController';
import StockMovementController from '@/actions/App/Http/Controllers/StockMovementController';
import StockMovementCorrectionController from '@/actions/App/Http/Controllers/StockMovementCorrectionController';
import Heading from '@/components/Heading.vue';
import PaceSearchSelect from '@/components/PaceSearchSelect.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index, ledger } from '@/routes/inventory';

type Movement = {
    id: number;
    type: string;
    quantity: number;
    balance_after: number;
    reference: string | null;
    reason: string | null;
    recorded_at: string;
    corrects_movement_id: number | null;
    student: {
        admission_number: string;
        first_name: string;
        last_name: string;
    } | null;
    pace_assignment: { id: number } | null;
    recorded_by: { name: string } | null;
};
type Item = {
    id: number;
    sku: string;
    item_type: string;
    reorder_level: number;
    target_stock_level: number;
    is_consumable: boolean;
    is_active: boolean;
    on_hand: number;
    pace: {
        id: number;
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
defineProps<{
    item: Item;
    movements: {
        data: Movement[];
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    movementTypes: Array<{ value: string; label: string }>;
    scoreKeyPaces: PaceOption[];
    canAdjust: boolean;
}>();
function confirmCorrection(event: Event): void {
    if (
        !window.confirm(
            'Post a reversing correction while retaining the original entry?',
        )
    ) {
        event.preventDefault();
    }
}
function paceLabel(pace: PaceOption): string {
    return `${pace.course.subject.name} · ${pace.course.name} · PACE ${pace.number}${pace.title ? ` · ${pace.title}` : ''}`;
}
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inventory', href: index() },
            { title: 'Inventory item', href: '#' },
        ],
    },
});
</script>
<template>
    <Head :title="item.sku" />
    <div class="flex max-w-[1300px] flex-1 flex-col gap-6 p-4 md:p-6">
        <Button variant="ghost" size="sm" class="w-fit" as-child
            ><Link :href="index()"
                ><ArrowLeft class="size-4" />Inventory</Link
            ></Button
        >
        <div class="flex flex-wrap items-start justify-between gap-3">
            <Heading
                :title="item.sku"
                :description="
                    item.pace
                        ? `PACE ${item.pace.number} · ${item.pace.course.name}`
                        : item.item_type.replaceAll('_', ' ')
                "
            />
            <div class="text-right">
                <div class="text-3xl font-semibold">{{ item.on_hand }}</div>
                <div class="text-xs text-muted-foreground">On hand</div>
            </div>
        </div>
        <div
            v-if="item.on_hand <= item.reorder_level"
            class="border-l-4 border-amber-500 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:bg-amber-950/30 dark:text-amber-100"
        >
            <div class="font-medium">Reorder attention required</div>
            On hand is at or below the reorder level of
            {{ item.reorder_level }}.
        </div>
        <div class="grid gap-8 lg:grid-cols-[1fr_22rem]">
            <section class="space-y-5">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold">Movement history</h2>
                    <Button size="sm" variant="outline" as-child
                        ><Link
                            :href="
                                ledger({
                                    query: { inventory_item_id: item.id },
                                })
                            "
                            >Full ledger</Link
                        ></Button
                    >
                </div>
                <div class="overflow-x-auto rounded-md border">
                    <table class="w-full min-w-4xl text-sm">
                        <thead class="border-b bg-muted/40 text-left">
                            <tr>
                                <th class="px-3 py-2">Date</th>
                                <th class="px-3 py-2">Type</th>
                                <th class="px-3 py-2 text-right">Quantity</th>
                                <th class="px-3 py-2 text-right">Balance</th>
                                <th class="px-3 py-2">Reference</th>
                                <th class="px-3 py-2">Student / reason</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="movement in movements.data"
                                :key="movement.id"
                            >
                                <td class="px-3 py-2">
                                    {{
                                        new Date(
                                            movement.recorded_at,
                                        ).toLocaleString()
                                    }}
                                </td>
                                <td class="px-3 py-2">
                                    <Badge variant="outline">{{
                                        movement.type
                                    }}</Badge>
                                </td>
                                <td
                                    class="px-3 py-2 text-right font-mono"
                                    :class="
                                        movement.quantity > 0
                                            ? 'text-emerald-700'
                                            : 'text-destructive'
                                    "
                                >
                                    {{ movement.quantity > 0 ? '+' : ''
                                    }}{{ movement.quantity }}
                                </td>
                                <td class="px-3 py-2 text-right font-mono">
                                    {{ movement.balance_after }}
                                </td>
                                <td class="px-3 py-2 font-mono text-xs">
                                    {{ movement.reference || '—' }}
                                </td>
                                <td class="px-3 py-2">
                                    <span v-if="movement.student"
                                        >{{ movement.student.first_name }}
                                        {{ movement.student.last_name }}</span
                                    ><span v-else>{{
                                        movement.reason || '—'
                                    }}</span>
                                </td>
                                <td class="px-3 py-2">
                                    <Form
                                        v-if="
                                            canAdjust &&
                                            movement.type !== 'correction'
                                        "
                                        v-bind="
                                            StockMovementCorrectionController.store.form(
                                                movement.id,
                                            )
                                        "
                                        class="flex gap-1"
                                        @submit="confirmCorrection"
                                        v-slot="{ processing }"
                                        ><Input
                                            name="reason"
                                            class="w-40"
                                            placeholder="Reversal reason"
                                            required /><Button
                                            type="submit"
                                            size="icon"
                                            variant="ghost"
                                            :disabled="processing"
                                            aria-label="Reverse movement"
                                            ><RotateCcw
                                                class="size-4" /></Button
                                    ></Form>
                                </td>
                            </tr>
                            <tr v-if="movements.data.length === 0">
                                <td
                                    colspan="7"
                                    class="px-4 py-12 text-center text-muted-foreground"
                                >
                                    No stock movements have been posted.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-between">
                    <Button
                        variant="outline"
                        :disabled="!movements.prev_page_url"
                        @click="
                            movements.prev_page_url &&
                            router.get(movements.prev_page_url)
                        "
                        >Previous</Button
                    ><span class="text-sm text-muted-foreground"
                        >{{ movements.total }} movements</span
                    ><Button
                        variant="outline"
                        :disabled="!movements.next_page_url"
                        @click="
                            movements.next_page_url &&
                            router.get(movements.next_page_url)
                        "
                        >Next</Button
                    >
                </div>
            </section>
            <aside class="space-y-6">
                <Form
                    v-if="canAdjust"
                    v-bind="StockMovementController.store.form(item.id)"
                    class="space-y-3 border-b pb-6"
                    v-slot="{ errors, processing }"
                    ><h2 class="font-semibold">Post movement</h2>
                    <select
                        name="type"
                        class="h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                        required
                    >
                        <option value="">Select movement</option>
                        <option
                            v-for="type in movementTypes"
                            :key="type.value"
                            :value="type.value"
                        >
                            {{ type.label }}
                        </option></select
                    ><Input
                        name="quantity"
                        type="number"
                        step="1"
                        placeholder="Quantity"
                        required
                    /><Input
                        name="reference"
                        placeholder="Delivery/reference number"
                    /><textarea
                        name="reason"
                        rows="3"
                        class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                        placeholder="Reason for damage, loss, or adjustment"
                    ></textarea>
                    <p class="text-xs text-destructive">
                        {{
                            errors.type ||
                            errors.quantity ||
                            errors.reference ||
                            errors.reason
                        }}
                    </p>
                    <Button class="w-full" type="submit" :disabled="processing"
                        >Post movement</Button
                    ></Form
                >
                <Form
                    v-if="canAdjust"
                    v-bind="InventoryItemController.update.form(item.id)"
                    class="space-y-3"
                    v-slot="{ errors, processing }"
                    ><h2 class="font-semibold">Item settings</h2>
                    <template v-if="item.item_type === 'score_key'">
                        <label class="text-sm font-medium" for="pace-id"
                            >Course and PACE</label
                        ><PaceSearchSelect
                            id="pace-id"
                            :options="
                                scoreKeyPaces.map((pace) => ({
                                    id: pace.id,
                                    label: paceLabel(pace),
                                }))
                            "
                            :model-value="item.pace?.id"
                            placeholder="Search subject, course, or PACE"
                        />
                    </template>
                    <label class="text-sm font-medium" for="sku">SKU</label
                    ><Input
                        id="sku"
                        name="sku"
                        :default-value="item.sku"
                        required
                    /><label class="text-sm font-medium" for="reorder"
                        >Reorder level</label
                    ><Input
                        id="reorder"
                        name="reorder_level"
                        type="number"
                        min="0"
                        :default-value="item.reorder_level"
                        required
                    /><label class="text-sm font-medium" for="target-stock"
                        >Target stock level</label
                    ><Input
                        id="target-stock"
                        name="target_stock_level"
                        type="number"
                        min="0"
                        :default-value="item.target_stock_level"
                        required
                    /><label class="flex items-center gap-2 text-sm"
                        ><input
                            type="hidden"
                            name="is_active"
                            value="0"
                        /><input
                            name="is_active"
                            type="checkbox"
                            value="1"
                            :checked="item.is_active"
                            class="accent-primary"
                        />Active item</label
                    >
                    <p class="text-xs text-destructive">
                        {{
                            errors.pace_id ||
                            errors.sku ||
                            errors.reorder_level ||
                            errors.target_stock_level
                        }}
                    </p>
                    <Button
                        class="w-full"
                        type="submit"
                        variant="secondary"
                        :disabled="processing"
                        ><Save class="size-4" />Save settings</Button
                    ></Form
                >
            </aside>
        </div>
    </div>
</template>
