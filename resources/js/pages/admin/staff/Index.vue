<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Search, UserPlus } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { create, edit, index } from '@/routes/admin/staff';

type StaffMember = {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    last_login_at: string | null;
    roles: Array<{ name: string; display_name: string }>;
};

const props = defineProps<{
    staff: {
        data: StaffMember[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
        total: number;
    };
    filters: { search: string };
}>();

const search = ref(props.filters.search);

function submitSearch(): void {
    router.get(index().url, { search: search.value }, { preserveState: true });
}

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Staff', href: index() }],
    },
});
</script>

<template>
    <Head title="Staff" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Staff"
                description="Manage staff access, roles, and account status"
            />
            <Button as-child>
                <Link :href="create()">
                    <UserPlus class="size-4" />
                    Add staff
                </Link>
            </Button>
        </div>

        <form class="flex max-w-xl gap-2" @submit.prevent="submitSearch">
            <div class="relative flex-1">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    class="pl-9"
                    placeholder="Search by name or email"
                    aria-label="Search staff"
                />
            </div>
            <Button type="submit" variant="secondary">Search</Button>
        </form>

        <div class="overflow-x-auto rounded-md border">
            <table class="w-full min-w-3xl text-sm">
                <thead class="border-b bg-muted/40 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Staff member</th>
                        <th class="px-4 py-3 font-medium">Roles</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="w-16 px-4 py-3">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="member in staff.data" :key="member.id">
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ member.name }}</div>
                            <div class="text-xs text-muted-foreground">
                                {{ member.email }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1.5">
                                <Badge
                                    v-for="role in member.roles"
                                    :key="role.name"
                                    variant="secondary"
                                >
                                    {{ role.display_name }}
                                </Badge>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <Badge
                                :variant="
                                    member.is_active ? 'default' : 'outline'
                                "
                            >
                                {{ member.is_active ? 'Active' : 'Inactive' }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Button size="icon" variant="ghost" as-child>
                                <Link
                                    :href="edit(member.id)"
                                    :aria-label="`Edit ${member.name}`"
                                >
                                    <Pencil class="size-4" />
                                </Link>
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="staff.data.length === 0">
                        <td
                            colspan="4"
                            class="px-4 py-12 text-center text-muted-foreground"
                        >
                            No staff accounts match this search.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between gap-4 text-sm">
            <span class="text-muted-foreground">
                {{ staff.total }} staff account{{
                    staff.total === 1 ? '' : 's'
                }}
            </span>
            <div class="flex gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!staff.prev_page_url"
                    as-child
                >
                    <Link v-if="staff.prev_page_url" :href="staff.prev_page_url"
                        >Previous</Link
                    >
                    <span v-else>Previous</span>
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!staff.next_page_url"
                    as-child
                >
                    <Link v-if="staff.next_page_url" :href="staff.next_page_url"
                        >Next</Link
                    >
                    <span v-else>Next</span>
                </Button>
            </div>
        </div>
    </div>
</template>
