<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { CalendarPlus, Save } from '@lucide/vue';
import AcademicPeriodController from '@/actions/App/Http/Controllers/Admin/AcademicPeriodController';
import TermController from '@/actions/App/Http/Controllers/Admin/TermController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/admin/academic-periods';

type Term = {
    id: number;
    name: string;
    sort_order: number;
    starts_on: string;
    ends_on: string;
    is_active: boolean;
    is_closed: boolean;
};
type AcademicYear = {
    id: number;
    name: string;
    starts_on: string;
    ends_on: string;
    is_active: boolean;
    is_closed: boolean;
    terms: Term[];
};

defineProps<{ academicYears: AcademicYear[] }>();
defineOptions({
    layout: { breadcrumbs: [{ title: 'Academic periods', href: index() }] },
});
</script>

<template>
    <Head title="Academic periods" />
    <div class="flex max-w-7xl flex-1 flex-col gap-7 p-4 md:p-6">
        <Heading
            title="Academic periods"
            description="Set school years and terms; only one year and one term can be active"
        />

        <section class="space-y-4 border-b pb-7">
            <h2 class="text-base font-semibold">New academic year</h2>
            <Form
                v-bind="AcademicPeriodController.store.form()"
                class="grid gap-4 md:grid-cols-6"
                reset-on-success
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2 md:col-span-2">
                    <Label for="year-name">Name</Label
                    ><Input
                        id="year-name"
                        name="name"
                        placeholder="2026"
                        required
                    /><InputError :message="errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="year-start">Starts</Label
                    ><Input
                        id="year-start"
                        name="starts_on"
                        type="date"
                        required
                    /><InputError :message="errors.starts_on" />
                </div>
                <div class="grid gap-2">
                    <Label for="year-end">Ends</Label
                    ><Input
                        id="year-end"
                        name="ends_on"
                        type="date"
                        required
                    /><InputError :message="errors.ends_on" />
                </div>
                <label class="flex items-end gap-2 pb-2 text-sm"
                    ><input type="hidden" name="is_active" value="0" /><input
                        name="is_active"
                        type="checkbox"
                        value="1"
                        class="size-4 accent-primary"
                    />Active</label
                >
                <input type="hidden" name="is_closed" value="0" />
                <div class="flex items-end">
                    <Button type="submit" :disabled="processing"
                        ><CalendarPlus class="size-4" />Add year</Button
                    >
                </div>
            </Form>
        </section>

        <section
            v-for="year in academicYears"
            :key="year.id"
            class="space-y-5 border-b pb-7 last:border-0"
        >
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-lg font-semibold">{{ year.name }}</h2>
                <Badge v-if="year.is_active">Active year</Badge
                ><Badge v-if="year.is_closed" variant="outline">Closed</Badge>
            </div>
            <Form
                v-bind="AcademicPeriodController.update.form(year.id)"
                class="grid gap-3 md:grid-cols-7"
                v-slot="{ errors, processing }"
            >
                <Input
                    name="name"
                    :default-value="year.name"
                    aria-label="Year name"
                    required
                />
                <Input
                    name="starts_on"
                    type="date"
                    :default-value="year.starts_on"
                    aria-label="Year start"
                    required
                />
                <Input
                    name="ends_on"
                    type="date"
                    :default-value="year.ends_on"
                    aria-label="Year end"
                    required
                />
                <label class="flex items-center gap-2 text-sm"
                    ><input type="hidden" name="is_active" value="0" /><input
                        name="is_active"
                        type="checkbox"
                        value="1"
                        :checked="year.is_active"
                        class="size-4 accent-primary"
                    />Active</label
                >
                <label class="flex items-center gap-2 text-sm"
                    ><input type="hidden" name="is_closed" value="0" /><input
                        name="is_closed"
                        type="checkbox"
                        value="1"
                        :checked="year.is_closed"
                        class="size-4 accent-primary"
                    />Closed</label
                >
                <div class="text-xs text-destructive md:col-span-7">
                    {{
                        errors.name ||
                        errors.starts_on ||
                        errors.ends_on ||
                        errors.is_active
                    }}
                </div>
                <Button
                    size="sm"
                    variant="outline"
                    type="submit"
                    :disabled="processing"
                    ><Save class="size-4" />Save year</Button
                >
            </Form>

            <div class="overflow-x-auto rounded-md border">
                <table class="w-full min-w-4xl text-sm">
                    <thead class="border-b bg-muted/40 text-left">
                        <tr>
                            <th class="px-3 py-2">Term</th>
                            <th class="px-3 py-2">Order</th>
                            <th class="px-3 py-2">Starts</th>
                            <th class="px-3 py-2">Ends</th>
                            <th class="px-3 py-2">State</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="term in year.terms" :key="term.id">
                            <td colspan="6" class="p-2">
                                <Form
                                    v-bind="
                                        TermController.update.form({
                                            academic_year: year.id,
                                            term: term.id,
                                        })
                                    "
                                    class="grid grid-cols-6 items-center gap-2"
                                    v-slot="{ processing }"
                                >
                                    <Input
                                        name="name"
                                        :default-value="term.name"
                                        aria-label="Term name"
                                        required
                                    /><Input
                                        name="sort_order"
                                        type="number"
                                        min="1"
                                        :default-value="term.sort_order"
                                        aria-label="Term order"
                                        required
                                    /><Input
                                        name="starts_on"
                                        type="date"
                                        :default-value="term.starts_on"
                                        aria-label="Term start"
                                        required
                                    /><Input
                                        name="ends_on"
                                        type="date"
                                        :default-value="term.ends_on"
                                        aria-label="Term end"
                                        required
                                    />
                                    <div class="flex gap-3">
                                        <label class="flex gap-1"
                                            ><input
                                                type="hidden"
                                                name="is_active"
                                                value="0"
                                            /><input
                                                name="is_active"
                                                type="checkbox"
                                                value="1"
                                                :checked="term.is_active"
                                                class="accent-primary"
                                            />Active</label
                                        ><label class="flex gap-1"
                                            ><input
                                                type="hidden"
                                                name="is_closed"
                                                value="0"
                                            /><input
                                                name="is_closed"
                                                type="checkbox"
                                                value="1"
                                                :checked="term.is_closed"
                                                class="accent-primary"
                                            />Closed</label
                                        >
                                    </div>
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        type="submit"
                                        :disabled="processing"
                                        :aria-label="`Save ${term.name}`"
                                        ><Save class="size-4"
                                    /></Button>
                                </Form>
                            </td>
                        </tr>
                        <tr v-if="year.terms.length === 0">
                            <td
                                colspan="6"
                                class="px-3 py-6 text-center text-muted-foreground"
                            >
                                No terms configured.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Form
                v-bind="TermController.store.form(year.id)"
                class="grid gap-2 md:grid-cols-7"
                reset-on-success
                v-slot="{ processing }"
            >
                <Input name="name" placeholder="Term name" required /><Input
                    name="sort_order"
                    type="number"
                    min="1"
                    placeholder="Order"
                    required
                /><Input name="starts_on" type="date" required /><Input
                    name="ends_on"
                    type="date"
                    required
                />
                <label class="flex items-center gap-1 text-sm"
                    ><input type="hidden" name="is_active" value="0" /><input
                        name="is_active"
                        type="checkbox"
                        value="1"
                        class="accent-primary"
                    />Active</label
                ><input type="hidden" name="is_closed" value="0" />
                <Button
                    size="sm"
                    variant="secondary"
                    type="submit"
                    :disabled="processing"
                    ><CalendarPlus class="size-4" />Add term</Button
                >
            </Form>
        </section>
        <p
            v-if="academicYears.length === 0"
            class="py-10 text-center text-muted-foreground"
        >
            Create the first academic year to begin.
        </p>
    </div>
</template>
