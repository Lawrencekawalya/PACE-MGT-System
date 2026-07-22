<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Ban, DatabaseZap } from '@lucide/vue';
import CatalogueImportController from '@/actions/App/Http/Controllers/Admin/CatalogueImportController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index, show } from '@/routes/admin/catalogue-imports';
type ImportRow = {
    id: number;
    row_number: number;
    raw_data: Record<string, string | null>;
    normalized_data: null | {
        level: string;
        subject: string;
        course: string;
        range: string;
        paces: string[];
        is_required: boolean;
    };
    status: string;
    errors: string[] | null;
};
type CatalogueImport = {
    id: number;
    original_name: string;
    checksum: string;
    status: string;
    valid_rows: number;
    warning_rows: number;
    invalid_rows: number;
    created_records: number;
    updated_records: number;
    skipped_records: number;
    failure_reason: string | null;
    uploader: { name: string };
    committer: { name: string } | null;
    rows: ImportRow[];
};
defineProps<{ catalogueImport: CatalogueImport }>();
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Catalogue imports', href: index() },
            { title: 'Review', href: show(0) },
        ],
    },
});
</script>
<template>
    <Head :title="`Import ${catalogueImport.id}`" />
    <div class="flex max-w-[1500px] flex-1 flex-col gap-6 p-4 md:p-6">
        <Button variant="ghost" size="sm" class="w-fit" as-child
            ><Link :href="index()"
                ><ArrowLeft class="size-4" />Back to imports</Link
            ></Button
        >
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                :title="catalogueImport.original_name"
                :description="`Uploaded by ${catalogueImport.uploader.name}`"
            /><Badge variant="outline">{{ catalogueImport.status }}</Badge>
        </div>
        <div
            class="grid grid-cols-3 gap-px overflow-hidden rounded-md border bg-border"
        >
            <div class="bg-background p-4">
                <div
                    class="text-2xl font-semibold text-emerald-700 dark:text-emerald-400"
                >
                    {{ catalogueImport.valid_rows }}
                </div>
                <div class="text-xs text-muted-foreground">Valid rows</div>
            </div>
            <div class="bg-background p-4">
                <div class="text-2xl font-semibold">
                    {{ catalogueImport.warning_rows }}
                </div>
                <div class="text-xs text-muted-foreground">
                    Warnings / skipped
                </div>
            </div>
            <div class="bg-background p-4">
                <div class="text-2xl font-semibold text-destructive">
                    {{ catalogueImport.invalid_rows }}
                </div>
                <div class="text-xs text-muted-foreground">Invalid rows</div>
            </div>
        </div>
        <div
            v-if="catalogueImport.failure_reason"
            class="rounded-md border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive"
        >
            {{ catalogueImport.failure_reason }}
        </div>
        <div
            v-if="catalogueImport.status === 'ready'"
            class="flex flex-wrap justify-end gap-2"
        >
            <Form
                v-bind="
                    CatalogueImportController.cancel.form(catalogueImport.id)
                "
                ><Button type="submit" variant="outline"
                    ><Ban class="size-4" />Cancel staging</Button
                ></Form
            ><Form
                v-bind="
                    CatalogueImportController.commit.form(catalogueImport.id)
                "
                ><Button type="submit"
                    ><DatabaseZap class="size-4" />Commit catalogue</Button
                ></Form
            >
        </div>
        <div
            v-if="catalogueImport.status === 'committed'"
            class="text-sm text-muted-foreground"
        >
            Result: {{ catalogueImport.created_records }} created,
            {{ catalogueImport.updated_records }} updated,
            {{ catalogueImport.skipped_records }} unchanged or skipped.
        </div>
        <div class="overflow-x-auto rounded-md border">
            <table class="w-full min-w-6xl text-sm">
                <thead class="border-b bg-muted/40 text-left">
                    <tr>
                        <th class="px-3 py-2">Row</th>
                        <th class="px-3 py-2">Level</th>
                        <th class="px-3 py-2">Course</th>
                        <th class="px-3 py-2">Subject</th>
                        <th class="px-3 py-2">Range</th>
                        <th class="px-3 py-2">PACEs</th>
                        <th class="px-3 py-2">Review</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="row in catalogueImport.rows" :key="row.id">
                        <td class="px-3 py-2 font-mono">
                            {{ row.row_number }}
                        </td>
                        <td class="px-3 py-2">
                            {{
                                row.normalized_data?.level ||
                                row.raw_data.level ||
                                '—'
                            }}
                        </td>
                        <td class="px-3 py-2">
                            {{
                                row.normalized_data?.course ||
                                row.raw_data.course
                            }}
                        </td>
                        <td class="px-3 py-2">
                            {{ row.normalized_data?.subject || '—' }}
                        </td>
                        <td class="px-3 py-2 font-mono text-xs">
                            {{
                                row.normalized_data?.range ||
                                row.raw_data.course_range ||
                                row.raw_data.default_range ||
                                '—'
                            }}
                        </td>
                        <td class="px-3 py-2">
                            {{ row.normalized_data?.paces.length ?? 0 }}
                        </td>
                        <td class="px-3 py-2">
                            <Badge
                                :variant="
                                    row.status === 'invalid'
                                        ? 'destructive'
                                        : 'outline'
                                "
                                >{{ row.status }}</Badge
                            >
                            <div
                                v-if="row.errors?.length"
                                class="mt-1 max-w-sm text-xs text-muted-foreground"
                            >
                                {{ row.errors.join(' ') }}
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
