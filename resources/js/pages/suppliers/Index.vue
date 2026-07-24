<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Building2, Plus, Save } from '@lucide/vue';
import SupplierController from '@/actions/App/Http/Controllers/SupplierController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/suppliers';

type Supplier = {
    id: number;
    name: string;
    code: string;
    contact_person: string | null;
    phone: string | null;
    email: string | null;
    address: string | null;
    notes: string | null;
    is_active: boolean;
    purchase_orders_count: number;
};

defineProps<{ suppliers: Supplier[]; canManage: boolean }>();
defineOptions({
    layout: { breadcrumbs: [{ title: 'Suppliers', href: index() }] },
});
</script>

<template>
    <Head title="Suppliers" />
    <div class="flex max-w-7xl flex-1 flex-col gap-8 p-4 md:p-6">
        <Heading
            title="Suppliers"
            description="Organizations that supply PACE booklets and Score Keys"
        />

        <section v-if="canManage" class="space-y-4 border-b pb-8">
            <div class="flex items-center gap-2">
                <Building2 class="size-5" />
                <h2 class="font-semibold">New supplier</h2>
            </div>
            <Form
                v-bind="SupplierController.store.form()"
                class="space-y-4"
                reset-on-success
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div class="grid gap-2">
                        <Label for="supplier-name">Supplier name</Label>
                        <Input id="supplier-name" name="name" required />
                        <InputError :message="errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="supplier-code">Code</Label>
                        <Input
                            id="supplier-code"
                            name="code"
                            placeholder="ACE-UG"
                            required
                        />
                        <InputError :message="errors.code" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="supplier-contact">Contact person</Label>
                        <Input id="supplier-contact" name="contact_person" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="supplier-phone">Phone</Label>
                        <Input id="supplier-phone" name="phone" />
                    </div>
                    <div class="grid gap-2 md:col-span-2">
                        <Label for="supplier-email">Email</Label>
                        <Input id="supplier-email" name="email" type="email" />
                        <InputError :message="errors.email" />
                    </div>
                    <div class="grid gap-2 md:col-span-2">
                        <Label for="supplier-address">Address</Label>
                        <Input id="supplier-address" name="address" />
                    </div>
                </div>
                <input type="hidden" name="is_active" value="1" />
                <Button type="submit" :disabled="processing">
                    <Plus class="size-4" />Add supplier
                </Button>
            </Form>
        </section>

        <div v-if="suppliers.length" class="divide-y border-y">
            <section
                v-for="supplier in suppliers"
                :key="supplier.id"
                class="space-y-4 py-6"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-base font-semibold">{{ supplier.name }}</h2>
                    <Badge variant="outline">{{ supplier.code }}</Badge>
                    <Badge v-if="!supplier.is_active" variant="secondary"
                        >Inactive</Badge
                    >
                    <span class="text-sm text-muted-foreground">
                        {{ supplier.purchase_orders_count }} orders
                    </span>
                </div>
                <Form
                    v-if="canManage"
                    v-bind="SupplierController.update.form(supplier.id)"
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
                        <Input
                            name="name"
                            :default-value="supplier.name"
                            aria-label="Supplier name"
                            required
                        />
                        <Input
                            name="code"
                            :default-value="supplier.code"
                            aria-label="Supplier code"
                            required
                        />
                        <Input
                            name="contact_person"
                            :default-value="supplier.contact_person ?? ''"
                            aria-label="Contact person"
                            placeholder="Contact person"
                        />
                        <Input
                            name="phone"
                            :default-value="supplier.phone ?? ''"
                            aria-label="Phone"
                            placeholder="Phone"
                        />
                        <Input
                            name="email"
                            type="email"
                            :default-value="supplier.email ?? ''"
                            aria-label="Email"
                            placeholder="Email"
                        />
                        <Input
                            name="address"
                            :default-value="supplier.address ?? ''"
                            aria-label="Address"
                            placeholder="Address"
                        />
                        <Input
                            name="notes"
                            :default-value="supplier.notes ?? ''"
                            aria-label="Notes"
                            placeholder="Notes"
                        />
                        <label class="flex items-center gap-2 text-sm">
                            <input type="hidden" name="is_active" value="0" />
                            <input
                                name="is_active"
                                type="checkbox"
                                value="1"
                                :checked="supplier.is_active"
                                class="size-4 accent-primary"
                            />
                            Active
                        </label>
                    </div>
                    <InputError
                        :message="errors.name || errors.code || errors.email"
                    />
                    <Button
                        type="submit"
                        size="sm"
                        variant="secondary"
                        :disabled="processing"
                    >
                        <Save class="size-4" />Save
                    </Button>
                </Form>
                <div v-else class="text-sm text-muted-foreground">
                    {{ supplier.contact_person || 'No contact person' }} ·
                    {{
                        supplier.phone || supplier.email || 'No contact details'
                    }}
                </div>
            </section>
        </div>
        <div
            v-else
            class="border-y py-16 text-center text-sm text-muted-foreground"
        >
            No suppliers have been added.
        </div>
    </div>
</template>
