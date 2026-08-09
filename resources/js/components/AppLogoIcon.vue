<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();

const page = usePage();
const logoUrl = computed(
    () => page.props.school.logo_url ?? '/branding/fica-logo.jpg',
);
</script>

<template>
    <img
        :src="logoUrl"
        :alt="`${page.props.school.short_name} logo`"
        :class="className"
        class="object-contain"
        v-bind="$attrs"
    />
</template>
