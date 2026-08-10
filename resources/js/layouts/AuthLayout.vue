<script setup lang="ts">
import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';

const {
    title = '',
    description = '',
    brandedBackground = false,
} = defineProps<{
    title?: string;
    description?: string;
    brandedBackground?: boolean;
}>();
</script>

<template>
    <AuthLayout
        :title="title"
        :description="description"
        :branded-background="brandedBackground"
    >
        <slot />
    </AuthLayout>
</template>
