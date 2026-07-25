<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    BookOpenCheck,
    Check,
    GraduationCap,
    ReceiptText,
    ShieldCheck,
    UserCog,
    Warehouse,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { documentation } from '@/routes';

type Workflow = {
    title: string;
    outcome: string;
    steps: string[];
};

type Guide = {
    role: string;
    label: string;
    summary: string;
    workflows: Workflow[];
    boundaries: string[];
};

const props = defineProps<{ guides: Guide[] }>();
const selectedRole = ref(props.guides[0]?.role ?? '');
const activeGuide = computed(
    () =>
        props.guides.find((guide) => guide.role === selectedRole.value) ??
        props.guides[0],
);

function roleIcon(role: string): Component {
    if (role === 'administrator') {
        return UserCog;
    }

    if (role === 'teacher') {
        return GraduationCap;
    }

    return role === 'accountant' ? ReceiptText : Warehouse;
}

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'System guide', href: documentation() }],
    },
});
</script>

<template>
    <Head title="System guide" />
    <div class="flex max-w-6xl flex-1 flex-col gap-7 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="System guide"
                description="Role-specific operating procedures for the PACE Management System"
            />
            <Badge v-if="activeGuide" variant="outline">
                <component :is="roleIcon(activeGuide.role)" class="size-3.5" />
                {{ activeGuide.label }}
            </Badge>
        </div>

        <div
            v-if="guides.length > 1"
            class="flex w-fit max-w-full flex-wrap gap-1 rounded-md border p-1"
            role="tablist"
            aria-label="Role guides"
        >
            <Button
                v-for="guide in guides"
                :key="guide.role"
                size="sm"
                :variant="selectedRole === guide.role ? 'secondary' : 'ghost'"
                role="tab"
                :aria-selected="selectedRole === guide.role"
                @click="selectedRole = guide.role"
            >
                <component :is="roleIcon(guide.role)" class="size-4" />
                {{ guide.label }}
            </Button>
        </div>

        <template v-if="activeGuide">
            <section class="border-y py-5">
                <div class="flex items-start gap-3">
                    <BookOpenCheck
                        class="mt-0.5 size-5 shrink-0 text-muted-foreground"
                    />
                    <div>
                        <h2 class="font-semibold">
                            {{ activeGuide.label }} workflow
                        </h2>
                        <p
                            class="mt-1 max-w-3xl text-sm leading-6 text-muted-foreground"
                        >
                            {{ activeGuide.summary }}
                        </p>
                    </div>
                </div>
            </section>

            <section aria-label="Ordered workflow">
                <div
                    v-for="(workflow, index) in activeGuide.workflows"
                    :key="workflow.title"
                    class="grid gap-4 border-b py-6 md:grid-cols-[3rem_minmax(0,1fr)]"
                >
                    <div
                        class="flex size-9 items-center justify-center rounded-md border font-mono text-sm font-semibold"
                        aria-hidden="true"
                    >
                        {{ index + 1 }}
                    </div>
                    <div class="min-w-0">
                        <div
                            class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1"
                        >
                            <h3 class="font-semibold">{{ workflow.title }}</h3>
                            <span class="text-xs text-muted-foreground">
                                {{ workflow.outcome }}
                            </span>
                        </div>
                        <ol class="mt-4 space-y-3">
                            <li
                                v-for="(step, stepIndex) in workflow.steps"
                                :key="step"
                                class="grid grid-cols-[1.5rem_minmax(0,1fr)] gap-2 text-sm leading-6"
                            >
                                <span
                                    class="font-mono text-xs text-muted-foreground"
                                >
                                    {{ index + 1 }}.{{ stepIndex + 1 }}
                                </span>
                                <span>{{ step }}</span>
                            </li>
                        </ol>
                    </div>
                </div>
            </section>

            <section class="space-y-4 border-y py-5">
                <div class="flex items-center gap-2">
                    <ShieldCheck class="size-5 text-muted-foreground" />
                    <h2 class="font-semibold">Access boundaries</h2>
                </div>
                <ul class="grid gap-3 md:grid-cols-2">
                    <li
                        v-for="boundary in activeGuide.boundaries"
                        :key="boundary"
                        class="flex items-start gap-2 text-sm leading-6"
                    >
                        <Check
                            class="mt-1 size-4 shrink-0 text-muted-foreground"
                        />
                        <span>{{ boundary }}</span>
                    </li>
                </ul>
            </section>
        </template>

        <div
            v-else
            class="border-y py-16 text-center text-sm text-muted-foreground"
        >
            No guide is available for this account. Contact an administrator to
            assign a staff role.
        </div>
    </div>
</template>
