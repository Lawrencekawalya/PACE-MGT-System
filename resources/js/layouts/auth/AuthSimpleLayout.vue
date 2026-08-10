<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
    brandedBackground?: boolean;
}>();

const school = usePage().props.school;
</script>

<template>
    <div
        class="relative flex min-h-svh flex-col items-center justify-center gap-6 bg-cover bg-center bg-no-repeat p-6 md:p-10"
        :class="brandedBackground ? 'bg-white' : 'bg-background'"
        :style="
            brandedBackground
                ? {
                      backgroundImage:
                          'url(/branding/fica-home-background.jpg)',
                  }
                : undefined
        "
    >
        <video
            v-if="brandedBackground"
            class="pointer-events-none absolute inset-0 size-full object-cover motion-reduce:hidden"
            poster="/branding/fica-home-background.jpg"
            autoplay
            muted
            loop
            playsinline
            preload="metadata"
            aria-hidden="true"
        >
            <source src="/branding/fica-home-background.mp4" type="video/mp4" />
        </video>
        <div class="absolute inset-x-0 top-0 h-1 bg-[var(--brand-burgundy)]" />
        <div
            class="relative z-10 w-full max-w-sm"
            :class="
                brandedBackground
                    ? 'rounded-md border border-foreground/15 bg-background/50 p-6 shadow-2xl ring-1 ring-foreground/10 backdrop-blur-xl backdrop-saturate-150 md:p-8'
                    : ''
            "
        >
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
