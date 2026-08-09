<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, RotateCcw, Search } from '@lucide/vue';
import { ref } from 'vue';
import StockMovementCorrectionController from '@/actions/App/Http/Controllers/StockMovementCorrectionController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index, ledger } from '@/routes/inventory';
import { show as showItem } from '@/routes/inventory-items';
import { show as showAssignment } from '@/routes/pace-assignments';

type Movement = {
    id: number;
    type: string;
    quantity: number;
    balance_after: number;
    reference: string | null;
    reason: string | null;
    recorded_at: string;
    corrects_movement_id: number | null;
    inventory_item: {
        id: number;
        sku: string;
        pace: { number: string; title: string | null } | null;
    };
    student: {
        admission_number: string;
        first_name: string;
        last_name: string;
    } | null;
    pace_assignment: { id: number } | null;
    score_key_request: { id: number } | null;
    issued_to: { id: number; name: string } | null;
    recorded_by: { name: string } | null;
    corrects_movement: { id: number; type: string; quantity: number } | null;
};
const props = defineProps<{
    movements: {
        data: Movement[];
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    filters: {
        search: string;
        type: string;
        inventory_item_id: number | null;
        date_from: string | null;
        date_to: string | null;
    };
    movementTypes: Array<{ value: string; label: string }>;
    canCorrect: boolean;
}>();
const search = ref(props.filters.search);
const type = ref(props.filters.type);
const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');
function filter(): void {
    router.get(
        ledger().url,
        {
            search: search.value,
            type: type.value,
            inventory_item_id: props.filters.inventory_item_id,
            date_from: dateFrom.value,
            date_to: dateTo.value,
        },
        { preserveState: true, replace: true },
    );
}
function confirmCorrection(event: Event): void {
    if (
        !window.confirm(
            'Post a reversing correction while retaining the original movement?',
        )
    ) {
        event.preventDefault();
    }
}
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inventory', href: index() },
            { title: 'Movement ledger', href: ledger() },
        ],
    },
});
</script>
<template>
    <Head title="Stock movement ledger" />
    <div class="flex max-w-[1550px] flex-1 flex-col gap-6 p-4 md:p-6">
        <Button variant="ghost" size="sm" class="w-fit" as-child
            ><Link :href="index()"
                ><ArrowLeft class="size-4" />Inventory</Link
            ></Button
        ><Heading
            title="Stock movement ledger"
            description="Immutable receipts, issues, losses, adjustments, and linked corrections"
        />
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
                    placeholder="Reference, SKU, student, or admission number"
                />
            </div>
            <select
                v-model="type"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">All movement types</option>
                <option
                    v-for="item in movementTypes"
                    :key="item.value"
                    :value="item.value"
                >
                    {{ item.label }}
                </option></select
            ><Input
                v-model="dateFrom"
                type="date"
                aria-label="From date"
            /><Input v-model="dateTo" type="date" aria-label="To date" /><Button
                type="submit"
                variant="secondary"
                >Filter</Button
            >
        </form>
        <div class="overflow-x-auto rounded-md border">
            <table class="w-full min-w-6xl text-sm">
                <thead class="border-b bg-muted/40 text-left">
                    <tr>
                        <th class="px-3 py-2">Date</th>
                        <th class="px-3 py-2">Item</th>
                        <th class="px-3 py-2">Type</th>
                        <th class="px-3 py-2 text-right">Quantity</th>
                        <th class="px-3 py-2 text-right">Balance</th>
                        <th class="px-3 py-2">Reference</th>
                        <th class="px-3 py-2">Issued to / assignment</th>
                        <th class="px-3 py-2">Recorded by</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="movement in movements.data" :key="movement.id">
                        <td class="px-3 py-2">
                            {{
                                new Date(movement.recorded_at).toLocaleString()
                            }}
                        </td>
                        <td class="px-3 py-2">
                            <Link
                                class="font-mono font-semibold hover:underline"
                                :href="showItem(movement.inventory_item.id)"
                                >{{ movement.inventory_item.sku }}</Link
                            >
                            <div class="text-xs text-muted-foreground">
                                {{
                                    movement.inventory_item.pace
                                        ? `PACE ${movement.inventory_item.pace.number}`
                                        : ''
                                }}
                            </div>
                        </td>
                        <td class="px-3 py-2">
                            <Badge variant="outline">{{ movement.type }}</Badge>
                            <div
                                v-if="movement.corrects_movement"
                                class="text-xs text-muted-foreground"
                            >
                                Reverses #{{ movement.corrects_movement.id }}
                            </div>
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
                        <td class="px-3 py-2">
                            <div class="font-mono text-xs">
                                {{ movement.reference || '—' }}
                            </div>
                            <div class="text-xs text-muted-foreground">
                                {{ movement.reason }}
                            </div>
                        </td>
                        <td class="px-3 py-2">
                            <div v-if="movement.student">
                                {{ movement.student.first_name }}
                                {{ movement.student.last_name }}
                                <div class="font-mono text-xs">
                                    {{ movement.student.admission_number }}
                                </div>
                            </div>
                            <Link
                                v-if="movement.pace_assignment"
                                class="text-xs hover:underline"
                                :href="
                                    showAssignment(movement.pace_assignment.id)
                                "
                                >Assignment #{{
                                    movement.pace_assignment.id
                                }}</Link
                            >
                            <div v-if="movement.issued_to">
                                {{ movement.issued_to.name }}
                                <div class="text-xs text-muted-foreground">
                                    Score Key request #{{
                                        movement.score_key_request?.id
                                    }}
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-2">
                            {{ movement.recorded_by?.name || 'System' }}
                        </td>
                        <td class="px-3 py-2">
                            <Form
                                v-if="
                                    canCorrect && movement.type !== 'correction'
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
                                    size="icon"
                                    type="submit"
                                    variant="ghost"
                                    :disabled="processing"
                                    aria-label="Reverse movement"
                                    ><RotateCcw class="size-4" /></Button
                            ></Form>
                        </td>
                    </tr>
                    <tr v-if="movements.data.length === 0">
                        <td
                            colspan="9"
                            class="px-4 py-14 text-center text-muted-foreground"
                        >
                            No movements match these filters.
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
    </div>
</template>
