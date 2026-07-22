<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { RefreshCw } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { systemStatus } from '@/routes/admin';

type Check = {
    key: string;
    label: string;
    status: 'passed' | 'warning' | 'failed';
    detail: string;
};

defineProps<{
    infrastructure: {
        status: 'healthy' | 'degraded' | 'unhealthy';
        checked_at: string;
        checks: Check[];
    };
    releaseChecks: Check[];
    metrics: Record<string, number>;
}>();

const metricLabels: Record<string, string> = {
    staff: 'Active staff',
    students: 'Students',
    paces: 'Active PACEs',
    inventory_items: 'Inventory items',
    catalogue_imports: 'Committed imports',
};

function statusVariant(status: Check['status']) {
    return status === 'failed'
        ? 'destructive'
        : status === 'passed'
          ? 'default'
          : 'outline';
}

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'System status', href: systemStatus() }],
    },
});
</script>

<template>
    <Head title="System status" />
    <div class="flex max-w-7xl flex-1 flex-col gap-7 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="System status"
                description="Infrastructure health and MVP release readiness"
            />
            <Button variant="outline" @click="router.reload()">
                <RefreshCw class="size-4" />Refresh
            </Button>
        </div>

        <section class="space-y-4">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-base font-semibold">Infrastructure</h2>
                <Badge
                    :variant="
                        infrastructure.status === 'unhealthy'
                            ? 'destructive'
                            : infrastructure.status === 'healthy'
                              ? 'default'
                              : 'outline'
                    "
                >
                    {{ infrastructure.status }}
                </Badge>
                <span class="text-xs text-muted-foreground">
                    {{ new Date(infrastructure.checked_at).toLocaleString() }}
                </span>
            </div>
            <div class="overflow-x-auto rounded-md border">
                <table class="w-full min-w-2xl text-sm">
                    <thead class="border-b bg-muted/40 text-left">
                        <tr>
                            <th class="px-4 py-3">Check</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="check in infrastructure.checks"
                            :key="check.key"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ check.label }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge :variant="statusVariant(check.status)">
                                    {{ check.status }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ check.detail }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="space-y-4">
            <h2 class="text-base font-semibold">Release data</h2>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <div
                    v-for="(value, key) in metrics"
                    :key="key"
                    class="rounded-md border p-4"
                >
                    <div class="text-xs text-muted-foreground">
                        {{ metricLabels[key] || key }}
                    </div>
                    <div class="mt-1 text-2xl font-semibold">{{ value }}</div>
                </div>
            </div>
            <div class="grid gap-3 md:grid-cols-2">
                <div
                    v-for="check in releaseChecks"
                    :key="check.key"
                    class="flex min-w-0 items-start justify-between gap-4 rounded-md border p-4"
                >
                    <div class="min-w-0">
                        <div class="font-medium">{{ check.label }}</div>
                        <div class="mt-1 text-sm text-muted-foreground">
                            {{ check.detail }}
                        </div>
                    </div>
                    <Badge
                        class="shrink-0"
                        :variant="statusVariant(check.status)"
                    >
                        {{ check.status }}
                    </Badge>
                </div>
            </div>
        </section>
    </div>
</template>
