<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type AccessOption = {
    name: string;
    display_name: string;
    description: string | null;
};

withDefaults(
    defineProps<{
        roles: AccessOption[];
        permissions: AccessOption[];
        errors: Record<string, string>;
        initialRoles?: string[];
        initialPermissions?: string[];
        includePassword?: boolean;
        name?: string;
        email?: string;
    }>(),
    {
        initialRoles: () => [],
        initialPermissions: () => [],
        includePassword: false,
        name: '',
        email: '',
    },
);
</script>

<template>
    <div class="grid gap-5 md:grid-cols-2">
        <div class="grid gap-2">
            <Label for="name">Full name</Label>
            <Input
                id="name"
                name="name"
                :default-value="name"
                required
                autocomplete="name"
            />
            <InputError :message="errors.name" />
        </div>
        <div class="grid gap-2">
            <Label for="email">Email address</Label>
            <Input
                id="email"
                name="email"
                type="email"
                :default-value="email"
                required
                autocomplete="email"
            />
            <InputError :message="errors.email" />
        </div>
    </div>

    <div v-if="includePassword" class="grid gap-5 md:grid-cols-2">
        <div class="grid gap-2">
            <Label for="password">Temporary password</Label>
            <Input
                id="password"
                name="password"
                type="password"
                required
                autocomplete="new-password"
            />
            <InputError :message="errors.password" />
        </div>
        <div class="grid gap-2">
            <Label for="password_confirmation">Confirm password</Label>
            <Input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                required
                autocomplete="new-password"
            />
        </div>
    </div>

    <fieldset class="space-y-3">
        <legend class="text-sm font-medium">Roles</legend>
        <div class="grid gap-3 md:grid-cols-3">
            <label
                v-for="role in roles"
                :key="role.name"
                class="flex min-h-16 cursor-pointer items-start gap-3 rounded-md border p-3"
            >
                <input
                    type="checkbox"
                    name="roles[]"
                    :value="role.name"
                    :checked="initialRoles.includes(role.name)"
                    class="mt-0.5 size-4 accent-primary"
                />
                <span class="min-w-0">
                    <span class="block text-sm font-medium">{{
                        role.display_name
                    }}</span>
                    <span class="block text-xs text-muted-foreground">{{
                        role.description || 'Standard role access'
                    }}</span>
                </span>
            </label>
        </div>
        <InputError :message="errors.roles" />
    </fieldset>

    <fieldset v-if="permissions.length" class="space-y-3">
        <legend class="text-sm font-medium">Additional permissions</legend>
        <p class="text-xs text-muted-foreground">
            Optional access granted directly in addition to role permissions.
        </p>
        <div class="grid gap-3 md:grid-cols-2">
            <label
                v-for="permission in permissions"
                :key="permission.name"
                class="flex cursor-pointer items-start gap-3 rounded-md border p-3"
            >
                <input
                    type="checkbox"
                    name="direct_permissions[]"
                    :value="permission.name"
                    :checked="initialPermissions.includes(permission.name)"
                    class="mt-0.5 size-4 accent-primary"
                />
                <span>
                    <span class="block text-sm font-medium">{{
                        permission.display_name
                    }}</span>
                    <span class="block text-xs text-muted-foreground">{{
                        permission.description || 'Optional staff permission'
                    }}</span>
                </span>
            </label>
        </div>
        <InputError :message="errors.direct_permissions" />
    </fieldset>
</template>
