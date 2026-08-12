<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Bell, CheckCheck } from '@lucide/vue';
import { reactive } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

type NotificationItem = {
    id: string;
    category: string;
    priority: string;
    title: string;
    message: string;
    url: string;
    read_at: string | null;
    created_at: string;
};

type Option = { value: string; label: string };
type PageLink = { url: string | null; label: string; active: boolean };

const props = defineProps<{
    notifications: {
        data: NotificationItem[];
        links: PageLink[];
        total: number;
    };
    filters: { status: string; category: string; priority: string };
    categories: Option[];
    priorities: Option[];
}>();

const filters = reactive({ ...props.filters });
const applyFilters = () =>
    router.get('/notifications', filters, {
        preserveState: true,
        replace: true,
    });
const markAllRead = () => router.patch('/notifications/read-all');
const openNotification = (notification: NotificationItem) => {
    if (notification.read_at) {
        router.visit(notification.url);

        return;
    }

    router.patch(
        `/notifications/${notification.id}/read`,
        {},
        { onSuccess: () => router.visit(notification.url) },
    );
};
const formatDate = (value: string) =>
    new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
const priorityClass = (priority: string) =>
    ({
        critical: 'text-destructive',
        warning: 'text-amber-600 dark:text-amber-400',
        action_required: 'text-primary',
    })[priority] ?? 'text-muted-foreground';
</script>

<template>
    <Head title="Notifications" />
    <AppLayout
        :breadcrumbs="[{ title: 'Notifications', href: '/notifications' }]"
    >
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold">Notifications</h1>
                    <p class="text-sm text-muted-foreground">
                        Tasks, decisions, and operational exceptions requiring
                        your attention
                    </p>
                </div>
                <button
                    type="button"
                    class="inline-flex h-10 items-center gap-2 rounded-md border px-3 text-sm font-medium hover:bg-accent"
                    @click="markAllRead"
                >
                    <CheckCheck class="size-4" /> Mark all as read
                </button>
            </div>

            <form
                class="grid gap-3 border-y py-4 sm:grid-cols-3 lg:max-w-3xl"
                @submit.prevent="applyFilters"
            >
                <select
                    v-model="filters.status"
                    class="h-10 rounded-md border bg-background px-3 text-sm"
                    aria-label="Read status"
                    @change="applyFilters"
                >
                    <option value="all">All statuses</option>
                    <option value="unread">Unread</option>
                    <option value="read">Read</option>
                </select>
                <select
                    v-model="filters.category"
                    class="h-10 rounded-md border bg-background px-3 text-sm"
                    aria-label="Category"
                    @change="applyFilters"
                >
                    <option value="">All categories</option>
                    <option
                        v-for="option in categories"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
                <select
                    v-model="filters.priority"
                    class="h-10 rounded-md border bg-background px-3 text-sm"
                    aria-label="Priority"
                    @change="applyFilters"
                >
                    <option value="">All priorities</option>
                    <option
                        v-for="option in priorities"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
            </form>

            <div v-if="notifications.data.length" class="divide-y border-y">
                <button
                    v-for="notification in notifications.data"
                    :key="notification.id"
                    type="button"
                    class="flex w-full gap-4 py-4 text-left hover:bg-accent/40"
                    @click="openNotification(notification)"
                >
                    <span
                        class="mt-1 flex size-9 shrink-0 items-center justify-center rounded-md border"
                        ><Bell
                            class="size-4"
                            :class="priorityClass(notification.priority)"
                    /></span>
                    <span class="min-w-0 flex-1">
                        <span class="flex flex-wrap items-center gap-2">
                            <strong class="text-sm">{{
                                notification.title
                            }}</strong>
                            <span
                                class="rounded border px-1.5 py-0.5 text-[11px] text-muted-foreground capitalize"
                                >{{ notification.category }}</span
                            >
                            <span
                                v-if="!notification.read_at"
                                class="size-2 rounded-full bg-primary"
                                title="Unread"
                            />
                        </span>
                        <span
                            class="mt-1 block text-sm text-muted-foreground"
                            >{{ notification.message }}</span
                        >
                        <span
                            class="mt-1 block text-xs text-muted-foreground"
                            >{{ formatDate(notification.created_at) }}</span
                        >
                    </span>
                </button>
            </div>
            <div v-else class="py-16 text-center text-sm text-muted-foreground">
                No notifications match these filters.
            </div>

            <div
                v-if="notifications.links.length > 3"
                class="flex flex-wrap gap-2"
            >
                <button
                    v-for="link in notifications.links"
                    :key="link.label"
                    type="button"
                    :disabled="!link.url"
                    class="min-w-9 rounded-md border px-3 py-2 text-sm disabled:opacity-40"
                    :class="
                        link.active
                            ? 'bg-primary text-primary-foreground'
                            : 'hover:bg-accent'
                    "
                    @click="link.url && router.visit(link.url)"
                    v-html="link.label"
                />
            </div>
        </div>
    </AppLayout>
</template>
