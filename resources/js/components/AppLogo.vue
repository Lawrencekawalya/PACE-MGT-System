<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const school = usePage().props.school;
</script>

<template>
    <div
        v-if="!school.logo_url"
        class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground"
    >
        <AppLogoIcon class="size-5 fill-current text-white dark:text-black" />
    </div>
    <img
        v-else
        :src="school.logo_url"
        :alt="`${school.short_name} logo`"
        class="size-8 object-contain"
    />
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold">{{
            school.short_name
        }}</span>
    </div>
</template>
