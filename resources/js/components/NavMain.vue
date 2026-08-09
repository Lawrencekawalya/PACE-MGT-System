<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import { ref, watch } from 'vue';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavGroup, NavItem } from '@/types';

const props = defineProps<{
    items: NavItem[];
    groups: NavGroup[];
}>();

const { currentUrl, isCurrentOrParentUrl, isCurrentUrl } = useCurrentUrl();
const openGroup = ref<string | null>(null);

function isGroupActive(group: NavGroup): boolean {
    return group.items.some(
        (item) => item.isActive ?? isCurrentOrParentUrl(item.href),
    );
}

function setGroupOpen(title: string, open: boolean): void {
    openGroup.value = open
        ? title
        : openGroup.value === title
          ? null
          : openGroup.value;
}

watch(
    [currentUrl, () => props.groups],
    () => {
        openGroup.value =
            props.groups.find((group) => isGroupActive(group))?.title ?? null;
    },
    { immediate: true },
);
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Platform</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="isCurrentUrl(item.href)"
                    :tooltip="item.title"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>

            <Collapsible
                v-for="group in groups"
                :key="group.title"
                as-child
                class="group/collapsible"
                :open="openGroup === group.title"
                @update:open="setGroupOpen(group.title, $event)"
            >
                <SidebarMenuItem>
                    <CollapsibleTrigger as-child>
                        <SidebarMenuButton
                            :is-active="isGroupActive(group)"
                            :tooltip="group.title"
                        >
                            <component :is="group.icon" />
                            <span>{{ group.title }}</span>
                            <ChevronRight
                                class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
                            />
                        </SidebarMenuButton>
                    </CollapsibleTrigger>
                    <CollapsibleContent>
                        <SidebarMenuSub>
                            <SidebarMenuSubItem
                                v-for="item in group.items"
                                :key="item.title"
                            >
                                <SidebarMenuSubButton
                                    as-child
                                    :is-active="
                                        item.isActive ??
                                        isCurrentOrParentUrl(item.href)
                                    "
                                >
                                    <Link :href="item.href">
                                        <component :is="item.icon" />
                                        <span>{{ item.title }}</span>
                                    </Link>
                                </SidebarMenuSubButton>
                            </SidebarMenuSubItem>
                        </SidebarMenuSub>
                    </CollapsibleContent>
                </SidebarMenuItem>
            </Collapsible>
        </SidebarMenu>
    </SidebarGroup>
</template>
