<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Save } from '@lucide/vue';
import StaffController from '@/actions/App/Http/Controllers/Admin/StaffController';
import StaffAccessFields from '@/components/admin/StaffAccessFields.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { create, index } from '@/routes/admin/staff';

type AccessOption = {
    name: string;
    display_name: string;
    description: string | null;
};

defineProps<{ roles: AccessOption[]; permissions: AccessOption[] }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Staff', href: index() },
            { title: 'Add staff', href: create() },
        ],
    },
});
</script>

<template>
    <Head title="Add staff" />

    <div class="flex max-w-5xl flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex items-start gap-3">
            <Button variant="ghost" size="icon" as-child>
                <Link :href="index()" aria-label="Back to staff">
                    <ArrowLeft class="size-4" />
                </Link>
            </Button>
            <Heading
                title="Add staff account"
                description="Create an internal account and assign its operational access"
            />
        </div>

        <Form
            v-bind="StaffController.store.form()"
            class="space-y-7"
            v-slot="{ errors, processing }"
        >
            <StaffAccessFields
                :roles="roles"
                :permissions="permissions"
                :errors="errors"
                include-password
            />

            <div class="flex justify-end border-t pt-5">
                <Button type="submit" :disabled="processing">
                    <Save class="size-4" />
                    Create account
                </Button>
            </div>
        </Form>
    </div>
</template>
