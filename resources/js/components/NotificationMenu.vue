<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Bell } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

const page = usePage();
const feed = computed(() => page.props.notificationFeed);
const isOpen = ref(false);

const openNotification = (notification: (typeof feed.value.recent)[number]) => {
    isOpen.value = false;

    if (notification.read_at) {
        router.visit(notification.url);

        return;
    }

    router.patch(
        `/notifications/${notification.id}/read`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => router.visit(notification.url),
        },
    );
};

const markAllRead = () => {
    router.patch('/notifications/read-all', {}, { preserveScroll: true });
};

const viewAllNotifications = () => {
    isOpen.value = false;
    router.visit('/notifications');
};

const formatTime = (value: string) =>
    new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
</script>

<template>
    <DropdownMenu v-model:open="isOpen">
        <DropdownMenuTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                class="relative"
                aria-label="Notifications"
                title="Notifications"
            >
                <Bell class="size-5" />
                <span
                    v-if="feed.unread_count > 0"
                    class="absolute -top-1 -right-1 flex min-w-4 items-center justify-center rounded-full bg-destructive px-1 text-[10px] leading-4 font-semibold text-destructive-foreground"
                >
                    {{ feed.unread_count > 99 ? '99+' : feed.unread_count }}
                </span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent
            align="end"
            class="w-[min(24rem,calc(100vw-2rem))] p-0"
        >
            <div class="flex items-center justify-between px-3 py-2">
                <DropdownMenuLabel class="p-0">Notifications</DropdownMenuLabel>
                <button
                    v-if="feed.unread_count > 0"
                    type="button"
                    class="text-xs text-muted-foreground hover:text-foreground"
                    @click.prevent="markAllRead"
                >
                    Mark all as read
                </button>
            </div>
            <DropdownMenuSeparator class="m-0" />
            <div
                v-if="feed.recent.length"
                class="max-h-96 overflow-y-auto py-1"
            >
                <button
                    v-for="notification in feed.recent"
                    :key="notification.id"
                    type="button"
                    class="flex w-full gap-3 px-3 py-3 text-left hover:bg-accent"
                    @click="openNotification(notification)"
                >
                    <span
                        class="mt-1.5 size-2 shrink-0 rounded-full"
                        :class="
                            notification.read_at
                                ? 'bg-muted-foreground/30'
                                : 'bg-primary'
                        "
                    />
                    <span class="min-w-0">
                        <span class="block text-sm font-medium">{{
                            notification.title
                        }}</span>
                        <span
                            class="mt-0.5 line-clamp-2 block text-xs text-muted-foreground"
                            >{{ notification.message }}</span
                        >
                        <span
                            class="mt-1 block text-[11px] text-muted-foreground"
                            >{{ formatTime(notification.created_at) }}</span
                        >
                    </span>
                </button>
            </div>
            <p
                v-else
                class="px-3 py-8 text-center text-sm text-muted-foreground"
            >
                No notifications yet.
            </p>
            <DropdownMenuSeparator class="m-0" />
            <button
                type="button"
                class="w-full px-3 py-2 text-center text-sm font-medium hover:bg-accent"
                @click="viewAllNotifications"
            >
                View all notifications
            </button>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
