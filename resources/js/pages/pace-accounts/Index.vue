<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import {
    ChevronDown,
    ChevronRight,
    CircleDollarSign,
    Search,
    WalletCards,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index } from '@/routes/pace-accounts';
import { update as updateCost } from '@/routes/pace-accounts/cost';
import { store as storePayment } from '@/routes/pace-accounts/payments';

type Transaction = {
    id: number;
    type: string;
    type_label: string;
    amount: string;
    balance_after: string;
    reference: string | null;
    notes: string | null;
    pace: string | null;
    recorded_by: string | null;
    recorded_at: string;
};
type AccountRow = {
    id: number;
    student: { id: number; admission_number: string; name: string };
    level: string;
    learning_center: string;
    balance: string;
    can_issue: boolean;
    paces_available: number;
    transactions: Transaction[];
};
type Paginator = {
    data: AccountRow[];
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

const props = defineProps<{
    enrollments: Paginator;
    summary: {
        students: number;
        total_balance: string;
        funded: number;
        insufficient: number;
        zero: number;
    };
    filters: {
        academic_year_id: number | null;
        learning_center_id?: number | null;
        level_id?: number | null;
        balance_status?: string | null;
        search?: string | null;
    };
    paceCost: string;
    canSetPaceCost: boolean;
    today: string;
    options: {
        academicYears: Array<{ id: number; name: string }>;
        learningCenters: Array<{ id: number; name: string }>;
        levels: Array<{ id: number; name: string }>;
    };
}>();

const filters = ref({
    academic_year_id: props.filters.academic_year_id?.toString() ?? '',
    learning_center_id: props.filters.learning_center_id?.toString() ?? '',
    level_id: props.filters.level_id?.toString() ?? '',
    balance_status: props.filters.balance_status ?? '',
    search: props.filters.search ?? '',
});
const expandedId = ref<number | null>(null);
const formatMoney = (amount: string | number): string =>
    new Intl.NumberFormat('en-UG', {
        style: 'currency',
        currency: 'UGX',
        maximumFractionDigits: 0,
    }).format(Number(amount));
const costConfigured = computed(() => Number(props.paceCost) > 0);

function applyFilters(): void {
    router.get(
        index().url,
        Object.fromEntries(
            Object.entries(filters.value).filter(([, value]) => value !== ''),
        ),
        { preserveState: true, replace: true },
    );
}

function resetFilters(): void {
    router.get(index());
}

function visitPage(url: string | null): void {
    if (url) {
        router.get(url);
    }
}

defineOptions({
    layout: { breadcrumbs: [{ title: 'PACE accounts', href: index() }] },
});
</script>

<template>
    <Head title="PACE accounts" />

    <div class="flex max-w-[1600px] flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading
            title="PACE accounts"
            description="Student PACE credit, payments, and issue charges"
        />

        <section class="grid gap-4 border-y py-4 lg:grid-cols-[1fr_auto]">
            <div>
                <div class="text-sm text-muted-foreground">
                    Uniform PACE cost
                </div>
                <div class="mt-1 text-2xl font-semibold">
                    {{
                        costConfigured
                            ? formatMoney(paceCost)
                            : 'Not configured'
                    }}
                </div>
            </div>
            <Form
                v-if="canSetPaceCost"
                v-bind="updateCost.form()"
                class="flex items-end gap-2"
                v-slot="{ errors, processing }"
                :options="{ preserveScroll: true }"
            >
                <div>
                    <label for="pace_cost" class="text-sm font-medium"
                        >New PACE cost (UGX)</label
                    >
                    <Input
                        id="pace_cost"
                        name="pace_cost"
                        type="number"
                        min="1"
                        step="1"
                        :default-value="paceCost"
                        class="mt-1 w-52"
                    />
                    <InputError :message="errors.pace_cost" />
                </div>
                <Button :disabled="processing">
                    <CircleDollarSign class="size-4" />
                    Update cost
                </Button>
            </Form>
        </section>

        <div
            class="grid grid-cols-2 gap-px overflow-hidden rounded-md border bg-border lg:grid-cols-5"
        >
            <div class="bg-background p-4">
                <div class="text-2xl font-semibold">{{ summary.students }}</div>
                <div class="text-xs text-muted-foreground">Students</div>
            </div>
            <div class="bg-background p-4">
                <div class="text-2xl font-semibold">
                    {{ formatMoney(summary.total_balance) }}
                </div>
                <div class="text-xs text-muted-foreground">Credit held</div>
            </div>
            <div class="bg-background p-4">
                <div class="text-2xl font-semibold text-emerald-600">
                    {{ summary.funded }}
                </div>
                <div class="text-xs text-muted-foreground">Can issue</div>
            </div>
            <div class="bg-background p-4">
                <div class="text-2xl font-semibold text-amber-600">
                    {{ summary.insufficient }}
                </div>
                <div class="text-xs text-muted-foreground">Insufficient</div>
            </div>
            <div class="bg-background p-4">
                <div class="text-2xl font-semibold text-destructive">
                    {{ summary.zero }}
                </div>
                <div class="text-xs text-muted-foreground">Zero balance</div>
            </div>
        </div>

        <form
            class="grid gap-2 border-b pb-4 sm:grid-cols-2 lg:grid-cols-6"
            @submit.prevent="applyFilters"
        >
            <select
                v-model="filters.academic_year_id"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">Academic year</option>
                <option
                    v-for="year in options.academicYears"
                    :key="year.id"
                    :value="year.id"
                >
                    {{ year.name }}
                </option>
            </select>
            <select
                v-model="filters.learning_center_id"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">All learning centers</option>
                <option
                    v-for="center in options.learningCenters"
                    :key="center.id"
                    :value="center.id"
                >
                    {{ center.name }}
                </option>
            </select>
            <select
                v-model="filters.level_id"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">All grades</option>
                <option
                    v-for="level in options.levels"
                    :key="level.id"
                    :value="level.id"
                >
                    {{ level.name }}
                </option>
            </select>
            <select
                v-model="filters.balance_status"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">Any balance</option>
                <option value="funded">Can issue</option>
                <option value="insufficient">Insufficient</option>
                <option value="zero">Zero balance</option>
            </select>
            <div class="relative">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="filters.search"
                    class="pl-9"
                    placeholder="Student or admission no."
                />
            </div>
            <div class="flex gap-2">
                <Button class="flex-1" type="submit" variant="secondary"
                    >Filter</Button
                >
                <Button type="button" variant="ghost" @click="resetFilters"
                    >Reset</Button
                >
            </div>
        </form>

        <div class="overflow-x-auto rounded-md border">
            <table class="w-full min-w-[1200px] text-sm">
                <thead class="border-b bg-muted/40 text-left">
                    <tr>
                        <th class="w-12 px-3 py-3">
                            <span class="sr-only">Details</span>
                        </th>
                        <th class="px-3 py-3">Student</th>
                        <th class="px-3 py-3">Learning center / grade</th>
                        <th class="px-3 py-3">Available balance</th>
                        <th class="px-3 py-3">Issue capacity</th>
                        <th class="w-[38rem] px-3 py-3">Record payment</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <template v-for="row in enrollments.data" :key="row.id">
                        <tr>
                            <td class="px-3 py-3">
                                <Button
                                    type="button"
                                    size="icon"
                                    variant="ghost"
                                    :aria-label="`View ${row.student.name} account history`"
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
                                <div class="font-medium">
                                    {{ row.student.name }}
                                </div>
                                <div
                                    class="font-mono text-xs text-muted-foreground"
                                >
                                    {{ row.student.admission_number }}
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                {{ row.learning_center }}
                                <div class="text-xs text-muted-foreground">
                                    {{ row.level }}
                                </div>
                            </td>
                            <td class="px-3 py-3 font-mono font-semibold">
                                {{ formatMoney(row.balance) }}
                            </td>
                            <td class="px-3 py-3">
                                <Badge
                                    :variant="
                                        row.can_issue
                                            ? 'outline'
                                            : 'destructive'
                                    "
                                >
                                    {{
                                        row.can_issue
                                            ? `${row.paces_available} PACE(s)`
                                            : 'Cannot issue'
                                    }}
                                </Badge>
                            </td>
                            <td class="px-3 py-3">
                                <Form
                                    v-bind="storePayment.form(row.student.id)"
                                    class="grid grid-cols-[8rem_9rem_1fr_auto] gap-2"
                                    v-slot="{ errors, processing }"
                                    reset-on-success
                                    :options="{ preserveScroll: true }"
                                >
                                    <Input
                                        name="amount"
                                        type="number"
                                        min="1"
                                        step="1"
                                        placeholder="Amount"
                                    />
                                    <Input
                                        name="paid_on"
                                        type="date"
                                        :default-value="today"
                                    />
                                    <Input
                                        name="reference"
                                        maxlength="100"
                                        placeholder="Receipt/reference"
                                    />
                                    <Button :disabled="processing">
                                        <WalletCards class="size-4" />
                                        Add credit
                                    </Button>
                                    <InputError
                                        class="col-span-full"
                                        :message="
                                            errors.amount ??
                                            errors.paid_on ??
                                            errors.reference
                                        "
                                    />
                                    <Input
                                        name="notes"
                                        maxlength="1000"
                                        placeholder="Internal note (optional)"
                                        class="col-span-full"
                                    />
                                </Form>
                            </td>
                        </tr>
                        <tr v-if="expandedId === row.id" class="bg-muted/10">
                            <td colspan="6" class="px-6 py-4">
                                <div class="mb-3 text-sm font-semibold">
                                    Recent account activity
                                </div>
                                <div
                                    v-if="row.transactions.length"
                                    class="overflow-x-auto rounded-md border"
                                >
                                    <table class="w-full min-w-3xl text-xs">
                                        <thead
                                            class="border-b bg-muted/40 text-left"
                                        >
                                            <tr>
                                                <th class="px-3 py-2">Date</th>
                                                <th class="px-3 py-2">
                                                    Transaction
                                                </th>
                                                <th class="px-3 py-2">
                                                    Reference / PACE
                                                </th>
                                                <th
                                                    class="px-3 py-2 text-right"
                                                >
                                                    Amount
                                                </th>
                                                <th
                                                    class="px-3 py-2 text-right"
                                                >
                                                    Balance
                                                </th>
                                                <th class="px-3 py-2">
                                                    Recorded by
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y">
                                            <tr
                                                v-for="transaction in row.transactions"
                                                :key="transaction.id"
                                            >
                                                <td class="px-3 py-2">
                                                    {{
                                                        new Date(
                                                            transaction.recorded_at,
                                                        ).toLocaleDateString()
                                                    }}
                                                </td>
                                                <td class="px-3 py-2">
                                                    {{ transaction.type_label }}
                                                </td>
                                                <td class="px-3 py-2 font-mono">
                                                    {{
                                                        transaction.pace ??
                                                        transaction.reference ??
                                                        '—'
                                                    }}
                                                </td>
                                                <td
                                                    class="px-3 py-2 text-right font-mono"
                                                    :class="
                                                        Number(
                                                            transaction.amount,
                                                        ) < 0
                                                            ? 'text-destructive'
                                                            : 'text-emerald-600'
                                                    "
                                                >
                                                    {{
                                                        formatMoney(
                                                            transaction.amount,
                                                        )
                                                    }}
                                                </td>
                                                <td
                                                    class="px-3 py-2 text-right font-mono"
                                                >
                                                    {{
                                                        formatMoney(
                                                            transaction.balance_after,
                                                        )
                                                    }}
                                                </td>
                                                <td class="px-3 py-2">
                                                    {{
                                                        transaction.recorded_by ??
                                                        'System'
                                                    }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <p v-else class="text-sm text-muted-foreground">
                                    No account transactions recorded.
                                </p>
                            </td>
                        </tr>
                    </template>
                    <tr v-if="enrollments.data.length === 0">
                        <td
                            colspan="6"
                            class="px-4 py-14 text-center text-muted-foreground"
                        >
                            No students match these filters.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between gap-4">
            <Button
                variant="outline"
                :disabled="!enrollments.prev_page_url"
                @click="visitPage(enrollments.prev_page_url)"
                >Previous</Button
            >
            <span class="text-sm text-muted-foreground"
                >{{ enrollments.total }} student account(s)</span
            >
            <Button
                variant="outline"
                :disabled="!enrollments.next_page_url"
                @click="visitPage(enrollments.next_page_url)"
                >Next</Button
            >
        </div>
    </div>
</template>
