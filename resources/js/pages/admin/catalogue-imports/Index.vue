<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Eye, FileSpreadsheet, Upload } from '@lucide/vue';
import CatalogueImportController from '@/actions/App/Http/Controllers/Admin/CatalogueImportController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index, show } from '@/routes/admin/catalogue-imports';
type Import = {
    id: number;
    original_name: string;
    status: string;
    valid_rows: number;
    warning_rows: number;
    invalid_rows: number;
    created_at: string;
    uploader: { name: string };
};
defineProps<{
    imports: {
        data: Import[];
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
}>();
defineOptions({
    layout: { breadcrumbs: [{ title: 'Catalogue imports', href: index() }] },
});
</script>
<template>
    <Head title="Catalogue imports" />
    <div class="flex max-w-6xl flex-1 flex-col gap-7 p-4 md:p-6">
        <Heading
            title="Catalogue imports"
            description="Validate workbook rows in staging before changing the live PACE catalogue"
        /><Form
            v-bind="CatalogueImportController.store.form()"
            enctype="multipart/form-data"
            class="flex flex-wrap items-end gap-3 border-y py-6"
            v-slot="{ errors, processing, progress }"
            ><div class="grid min-w-72 flex-1 gap-2">
                <label for="workbook" class="text-sm font-medium"
                    >Excel workbook</label
                ><Input
                    id="workbook"
                    name="workbook"
                    type="file"
                    accept=".xlsx"
                    required
                /><span class="text-xs text-destructive">{{
                    errors.workbook
                }}</span>
            </div>
            <Button type="submit" :disabled="processing"
                ><Upload class="size-4" />{{
                    processing
                        ? `Uploading ${progress?.percentage ?? 0}%`
                        : 'Validate workbook'
                }}</Button
            ></Form
        >
        <div class="overflow-x-auto rounded-md border">
            <table class="w-full min-w-3xl text-sm">
                <thead class="border-b bg-muted/40 text-left">
                    <tr>
                        <th class="px-4 py-3">Workbook</th>
                        <th class="px-4 py-3">Uploaded by</th>
                        <th class="px-4 py-3">Rows</th>
                        <th class="px-4 py-3">Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="item in imports.data" :key="item.id">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2 font-medium">
                                <FileSpreadsheet
                                    class="size-4 text-muted-foreground"
                                />{{ item.original_name }}
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ item.uploader.name }}</td>
                        <td class="px-4 py-3">
                            <span class="text-emerald-700 dark:text-emerald-400"
                                >{{ item.valid_rows }} valid</span
                            >
                            · <span>{{ item.warning_rows }} warnings</span> ·
                            <span class="text-destructive"
                                >{{ item.invalid_rows }} invalid</span
                            >
                        </td>
                        <td class="px-4 py-3">
                            <Badge variant="outline">{{ item.status }}</Badge>
                        </td>
                        <td class="px-4 py-3">
                            <Button size="icon" variant="ghost" as-child
                                ><Link
                                    :href="show(item.id)"
                                    aria-label="Review import"
                                    ><Eye class="size-4" /></Link
                            ></Button>
                        </td>
                    </tr>
                    <tr v-if="imports.data.length === 0">
                        <td
                            colspan="5"
                            class="px-4 py-12 text-center text-muted-foreground"
                        >
                            No workbook imports yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
