<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();

const school = usePage().props.school;
</script>

<template>
    <div
        class="relative flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10"
    >
        <div class="absolute inset-x-0 top-0 h-1 bg-[var(--brand-burgundy)]" />
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-3 font-medium"
                    >
                        <div class="flex h-28 w-36 items-center justify-center">
                            <AppLogoIcon class="h-28 w-36" />
                        </div>
                        <span
                            class="text-center text-sm font-semibold text-[var(--brand-burgundy)] dark:text-[var(--brand-gold)]"
                        >
                            {{ school.official_name }}
                        </span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-xl font-medium">{{ title }}</h1>
                        <p class="text-center text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                        <p
                            v-if="school.slogan"
                            class="text-xs font-medium text-[var(--brand-blue)] dark:text-[var(--brand-gold)]"
                        >
                            {{ school.slogan }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
