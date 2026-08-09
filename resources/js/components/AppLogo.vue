<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const school = usePage().props.school;
</script>

<template>
    <div
        class="flex h-10 w-12 shrink-0 items-center justify-center overflow-hidden rounded-md border border-sidebar-border bg-white"
    >
        <AppLogoIcon class="h-9 w-11" />
    </div>
    <div class="ml-1 grid min-w-0 flex-1 text-left">
        <span class="truncate text-sm leading-tight font-semibold">
            {{ school.short_name }}
        </span>
        <span
            class="truncate text-[11px] leading-tight text-sidebar-foreground/60"
        >
            PACE Management
        </span>
    </div>
</template>
