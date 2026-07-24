<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Save, Upload } from '@lucide/vue';
import SchoolSettingController from '@/actions/App/Http/Controllers/Admin/SchoolSettingController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/admin/school-settings';

type SchoolSettings = {
    official_name: string;
    short_name: string;
    slogan: string | null;
    country_code: string;
    timezone: string;
    date_format: string;
    time_format: string;
    logo_url: string | null;
    self_test_pass_mark: string;
    pace_test_pass_mark: string;
    self_test_retry_limit: number;
    term_pace_target: number;
};

defineProps<{ settings: SchoolSettings }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'School settings', href: edit() }],
    },
});
</script>

<template>
    <Head title="School settings" />

    <div class="flex max-w-5xl flex-1 flex-col gap-7 p-4 md:p-6">
        <Heading
            title="School settings"
            description="Manage FICA identity, regional formats, assessment rules, and academic targets"
        />

        <Form
            v-bind="SchoolSettingController.update.form()"
            class="space-y-8"
            enctype="multipart/form-data"
            v-slot="{ errors, processing }"
        >
            <section class="space-y-5">
                <div>
                    <h2 class="text-base font-semibold">School identity</h2>
                    <p class="text-sm text-muted-foreground">
                        Used throughout navigation, reports, and exported
                        documents.
                    </p>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div class="grid gap-2 md:col-span-2">
                        <Label for="official_name">Official school name</Label>
                        <Input
                            id="official_name"
                            name="official_name"
                            :default-value="settings.official_name"
                            required
                        />
                        <InputError :message="errors.official_name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="short_name">Short name</Label>
                        <Input
                            id="short_name"
                            name="short_name"
                            :default-value="settings.short_name"
                            required
                            maxlength="30"
                        />
                        <InputError :message="errors.short_name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="country_code">Country code</Label>
                        <Input
                            id="country_code"
                            name="country_code"
                            :default-value="settings.country_code"
                            required
                            maxlength="2"
                        />
                        <InputError :message="errors.country_code" />
                    </div>
                    <div class="grid gap-2 md:col-span-2">
                        <Label for="slogan">Slogan</Label>
                        <Input
                            id="slogan"
                            name="slogan"
                            :default-value="settings.slogan || ''"
                            maxlength="255"
                        />
                        <InputError :message="errors.slogan" />
                    </div>
                </div>

                <div class="grid gap-3">
                    <Label for="logo">School logo</Label>
                    <div class="flex flex-wrap items-center gap-4">
                        <div
                            class="flex size-20 items-center justify-center overflow-hidden rounded-md border bg-muted/30"
                        >
                            <img
                                v-if="settings.logo_url"
                                :src="settings.logo_url"
                                alt="Current school logo"
                                class="size-full object-contain p-2"
                            />
                            <Upload
                                v-else
                                class="size-5 text-muted-foreground"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Input
                                id="logo"
                                name="logo"
                                type="file"
                                accept="image/png,image/jpeg,image/webp"
                            />
                            <label
                                v-if="settings.logo_url"
                                class="flex items-center gap-2 text-sm"
                            >
                                <input
                                    type="hidden"
                                    name="remove_logo"
                                    value="0"
                                />
                                <input
                                    type="checkbox"
                                    name="remove_logo"
                                    value="1"
                                    class="size-4 accent-primary"
                                />
                                Remove current logo
                            </label>
                        </div>
                    </div>
                    <InputError :message="errors.logo" />
                </div>
            </section>

            <section class="space-y-5 border-t pt-7">
                <div>
                    <h2 class="text-base font-semibold">
                        Regional preferences
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Controls how school dates and times are presented.
                    </p>
                </div>
                <div class="grid gap-5 md:grid-cols-3">
                    <div class="grid gap-2">
                        <Label for="timezone">Timezone</Label>
                        <Input
                            id="timezone"
                            name="timezone"
                            :default-value="settings.timezone"
                            required
                        />
                        <InputError :message="errors.timezone" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="date_format">Date format</Label>
                        <select
                            id="date_format"
                            name="date_format"
                            class="h-9 rounded-md border bg-transparent px-3 text-sm"
                            required
                        >
                            <option
                                value="DD/MM/YYYY"
                                :selected="
                                    settings.date_format === 'DD/MM/YYYY'
                                "
                            >
                                DD/MM/YYYY
                            </option>
                        </select>
                        <InputError :message="errors.date_format" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="time_format">Time format</Label>
                        <select
                            id="time_format"
                            name="time_format"
                            class="h-9 rounded-md border bg-transparent px-3 text-sm"
                            required
                        >
                            <option
                                value="12-hour"
                                :selected="settings.time_format === '12-hour'"
                            >
                                12-hour
                            </option>
                            <option
                                value="24-hour"
                                :selected="settings.time_format === '24-hour'"
                            >
                                24-hour
                            </option>
                        </select>
                        <InputError :message="errors.time_format" />
                    </div>
                </div>
            </section>

            <section class="space-y-5 border-t pt-7">
                <div>
                    <h2 class="text-base font-semibold">Academic defaults</h2>
                    <p class="text-sm text-muted-foreground">
                        New attempts use these assessment values. The term
                        target is a minimum and does not limit additional PACE
                        work.
                    </p>
                </div>
                <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                    <div class="grid gap-2">
                        <Label for="self_test_pass_mark">Pass mark (%)</Label>
                        <Input
                            id="self_test_pass_mark"
                            name="self_test_pass_mark"
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            :default-value="settings.self_test_pass_mark"
                            required
                        />
                        <InputError :message="errors.self_test_pass_mark" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="pace_test_pass_mark"
                            >PACE Test pass mark (%)</Label
                        >
                        <Input
                            id="pace_test_pass_mark"
                            name="pace_test_pass_mark"
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            :default-value="settings.pace_test_pass_mark"
                            required
                        />
                        <InputError :message="errors.pace_test_pass_mark" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="self_test_retry_limit"
                            >Maximum attempts</Label
                        >
                        <Input
                            id="self_test_retry_limit"
                            name="self_test_retry_limit"
                            type="number"
                            min="1"
                            max="10"
                            step="1"
                            :default-value="settings.self_test_retry_limit"
                            required
                        />
                        <InputError :message="errors.self_test_retry_limit" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="term_pace_target"
                            >PACEs per subject per term</Label
                        >
                        <Input
                            id="term_pace_target"
                            name="term_pace_target"
                            type="number"
                            min="1"
                            max="100"
                            step="1"
                            :default-value="settings.term_pace_target"
                            required
                        />
                        <InputError :message="errors.term_pace_target" />
                    </div>
                </div>
            </section>

            <div class="flex justify-end border-t pt-5">
                <Button type="submit" :disabled="processing">
                    <Save class="size-4" />
                    Save settings
                </Button>
            </div>
        </Form>
    </div>
</template>
