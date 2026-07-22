<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { CheckCircle2, Circle, School, ShieldCheck, Users } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { edit as editSchoolSettings } from '@/routes/admin/school-settings';
import { index as staffIndex } from '@/routes/admin/staff';

const props = defineProps<{
    setup: {
        school_settings: boolean;
        roles: boolean;
        administrator: boolean;
    } | null;
}>();

const page = usePage();
const setupItems = computed(() => [
    {
        label: 'School profile configured',
        complete: props.setup?.school_settings ?? false,
        icon: School,
        href: editSchoolSettings(),
    },
    {
        label: 'Access roles installed',
        complete: props.setup?.roles ?? false,
        icon: ShieldCheck,
        href: staffIndex(),
    },
    {
        label: 'Active administrator available',
        complete: props.setup?.administrator ?? false,
        icon: Users,
        href: staffIndex(),
    },
]);

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-1 flex-col gap-8 p-4 md:p-6">
        <Heading
            :title="`Welcome, ${page.props.auth.user.name}`"
            description="PACE operations and school setup status"
        />

        <section v-if="setup" class="max-w-4xl">
            <div class="mb-3 flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold">System readiness</h2>
                    <p class="text-sm text-muted-foreground">
                        Foundation checks required before catalogue setup.
                    </p>
                </div>
                <span class="text-sm font-medium">
                    {{ setupItems.filter((item) => item.complete).length }} / 3
                    complete
                </span>
            </div>

            <div class="divide-y rounded-md border">
                <div
                    v-for="item in setupItems"
                    :key="item.label"
                    class="flex min-h-16 items-center justify-between gap-4 px-4 py-3"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <CheckCircle2
                            v-if="item.complete"
                            class="size-5 shrink-0 text-emerald-600"
                        />
                        <Circle
                            v-else
                            class="size-5 shrink-0 text-muted-foreground"
                        />
                        <component
                            :is="item.icon"
                            class="size-4 shrink-0 text-muted-foreground"
                        />
                        <span class="truncate text-sm font-medium">{{
                            item.label
                        }}</span>
                    </div>
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="item.href">Open</Link>
                    </Button>
                </div>
            </div>
        </section>

        <section v-else class="max-w-4xl border-t pt-6">
            <h2 class="text-base font-semibold">Your workspace</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Operational modules available to your role will appear in the
                navigation as they are completed.
            </p>
        </section>
    </div>
</template>
