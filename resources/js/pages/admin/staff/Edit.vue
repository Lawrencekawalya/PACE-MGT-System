<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, KeyRound, Save } from '@lucide/vue';
import StaffController from '@/actions/App/Http/Controllers/Admin/StaffController';
import StaffPasswordController from '@/actions/App/Http/Controllers/Admin/StaffPasswordController';
import StaffAccessFields from '@/components/admin/StaffAccessFields.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/admin/staff';

type AccessOption = {
    name: string;
    display_name: string;
    description: string | null;
};

type StaffMember = {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    last_login_at: string | null;
    created_at: string;
    roles: string[];
    direct_permissions: string[];
};

defineProps<{
    staffMember: StaffMember;
    roles: AccessOption[];
    permissions: AccessOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Staff', href: index() },
            { title: 'Edit staff', href: index() },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit ${staffMember.name}`" />

    <div class="flex max-w-5xl flex-1 flex-col gap-8 p-4 md:p-6">
        <div class="flex items-start gap-3">
            <Button variant="ghost" size="icon" as-child>
                <Link :href="index()" aria-label="Back to staff">
                    <ArrowLeft class="size-4" />
                </Link>
            </Button>
            <Heading
                :title="staffMember.name"
                description="Update account status, roles, and direct permissions"
            />
        </div>

        <Form
            v-bind="StaffController.update.form(staffMember.id)"
            class="space-y-7"
            v-slot="{ errors, processing }"
        >
            <StaffAccessFields
                :roles="roles"
                :permissions="permissions"
                :errors="errors"
                :name="staffMember.name"
                :email="staffMember.email"
                :initial-roles="staffMember.roles"
                :initial-permissions="staffMember.direct_permissions"
            />

            <div class="space-y-2 border-t pt-5">
                <Label class="flex cursor-pointer items-center gap-3">
                    <input type="hidden" name="is_active" value="0" />
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        :checked="staffMember.is_active"
                        class="size-4 accent-primary"
                    />
                    Active account
                </Label>
                <p class="text-xs text-muted-foreground">
                    Inactive staff are signed out and cannot authenticate.
                </p>
                <InputError :message="errors.is_active" />
            </div>

            <div class="flex justify-end border-t pt-5">
                <Button type="submit" :disabled="processing">
                    <Save class="size-4" />
                    Save changes
                </Button>
            </div>
        </Form>

        <section class="space-y-5 border-t pt-7">
            <Heading
                variant="small"
                title="Reset password"
                description="Set a temporary password and retain an audited reason"
            />
            <Form
                v-bind="StaffPasswordController.form(staffMember.id)"
                class="grid max-w-3xl gap-5 md:grid-cols-2"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="password">New password</Label>
                    <PasswordInput
                        id="password"
                        name="password"
                        required
                        autocomplete="new-password"
                    />
                    <InputError :message="errors.password" />
                </div>
                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <PasswordInput
                        id="password_confirmation"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                    />
                </div>
                <div class="grid gap-2 md:col-span-2">
                    <Label for="reason">Reason</Label>
                    <Input id="reason" name="reason" required maxlength="500" />
                    <InputError :message="errors.reason" />
                </div>
                <div class="md:col-span-2">
                    <Button
                        type="submit"
                        variant="secondary"
                        :disabled="processing"
                    >
                        <KeyRound class="size-4" />
                        Reset password
                    </Button>
                </div>
            </Form>
        </section>
    </div>
</template>
