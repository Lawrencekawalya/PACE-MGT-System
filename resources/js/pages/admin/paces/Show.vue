<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Save } from '@lucide/vue';
import PaceController from '@/actions/App/Http/Controllers/Admin/PaceController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, show } from '@/routes/admin/paces';
type Pace = {
    id: number;
    course_id: number;
    number: string;
    title: string | null;
    edition: string;
    sequence_order: number;
    is_active: boolean;
    course: { name: string; subject: { name: string } };
    curriculum_requirements: Array<{
        level: { name: string };
        pivot: { sequence_order: number };
    }>;
};
defineProps<{ pace: Pace }>();
const page = usePage();
const canManage = page.props.auth.permissions.includes('manage-pace-catalogue');
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'PACE catalogue', href: index() },
            { title: 'PACE detail', href: show(0) },
        ],
    },
});
</script>
<template>
    <Head :title="`PACE ${pace.number}`" />
    <div class="flex max-w-4xl flex-1 flex-col gap-7 p-4 md:p-6">
        <Button variant="ghost" size="sm" class="w-fit" as-child
            ><Link :href="index()"
                ><ArrowLeft class="size-4" />Back to catalogue</Link
            ></Button
        >
        <div class="flex items-start justify-between gap-4">
            <Heading
                :title="`PACE ${pace.number}`"
                :description="`${pace.course.name} · ${pace.course.subject.name}`"
            /><Badge :variant="pace.is_active ? 'default' : 'outline'">{{
                pace.is_active ? 'Active' : 'Inactive'
            }}</Badge>
        </div>
        <Form
            v-if="canManage"
            v-bind="PaceController.update.form(pace.id)"
            class="grid gap-5 border-y py-7 md:grid-cols-2"
            v-slot="{ processing, errors }"
            ><input type="hidden" name="course_id" :value="pace.course_id" />
            <div class="grid gap-2">
                <Label for="number">PACE number</Label
                ><Input
                    id="number"
                    name="number"
                    :default-value="pace.number"
                    required
                />
            </div>
            <div class="grid gap-2">
                <Label for="sequence_order">Sequence order</Label
                ><Input
                    id="sequence_order"
                    name="sequence_order"
                    type="number"
                    min="1"
                    :default-value="pace.sequence_order"
                    required
                />
            </div>
            <div class="grid gap-2">
                <Label for="title">Title</Label
                ><Input
                    id="title"
                    name="title"
                    :default-value="pace.title || ''"
                />
            </div>
            <div class="grid gap-2">
                <Label for="edition">Edition</Label
                ><Input
                    id="edition"
                    name="edition"
                    :default-value="pace.edition"
                />
            </div>
            <label class="flex items-center gap-2 text-sm"
                ><input type="hidden" name="is_active" value="0" /><input
                    name="is_active"
                    type="checkbox"
                    value="1"
                    :checked="pace.is_active"
                    class="size-4 accent-primary"
                />Available for new assignments</label
            >
            <div class="text-sm text-destructive">
                {{ Object.values(errors)[0] }}
            </div>
            <div class="md:col-span-2">
                <Button type="submit" :disabled="processing"
                    ><Save class="size-4" />Save PACE</Button
                >
            </div></Form
        >
        <section class="space-y-3">
            <h2 class="text-base font-semibold">Curriculum placement</h2>
            <div
                v-if="pace.curriculum_requirements.length"
                class="divide-y rounded-md border"
            >
                <div
                    v-for="requirement in pace.curriculum_requirements"
                    :key="requirement.level.name"
                    class="flex justify-between px-4 py-3 text-sm"
                >
                    <span>{{ requirement.level.name }}</span
                    ><span class="text-muted-foreground"
                        >Position {{ requirement.pivot.sequence_order }}</span
                    >
                </div>
            </div>
            <p v-else class="text-sm text-muted-foreground">
                This PACE is not assigned to a curriculum sequence.
            </p>
        </section>
    </div>
</template>
