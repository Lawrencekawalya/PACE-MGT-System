<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';
import { computed, ref } from 'vue';
import CurriculumController from '@/actions/App/Http/Controllers/Admin/CurriculumController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index } from '@/routes/admin/curriculum';
type Level = { id: number; name: string };
type Pace = { id: number; number: string; sequence_order: number };
type Course = { id: number; name: string; paces: Pace[] };
type Requirement = {
    id: number;
    level_id: number;
    course_id: number;
    is_required: boolean;
    sort_order: number;
    is_active: boolean;
    level: Level;
    course: { name: string };
    paces: Array<{
        id: number;
        number: string;
        pivot: { sequence_order: number };
    }>;
};
const props = defineProps<{
    levels: Level[];
    courses: Course[];
    requirements: Requirement[];
}>();
const selectedCourse = ref<number | ''>('');
const availablePaces = computed(
    () =>
        props.courses.find(
            (course) => course.id === Number(selectedCourse.value),
        )?.paces ?? [],
);
defineOptions({
    layout: { breadcrumbs: [{ title: 'Curriculum', href: index() }] },
});
</script>
<template>
    <Head title="Curriculum" />
    <div class="flex max-w-7xl flex-1 flex-col gap-7 p-4 md:p-6">
        <Heading
            title="Curriculum sequences"
            description="Assign an explicit ordered set of PACEs to each level and course"
        /><Form
            v-bind="CurriculumController.store.form()"
            class="space-y-5 border-b pb-7"
            v-slot="{ processing, errors }"
            ><div class="grid gap-3 md:grid-cols-5">
                <select
                    name="level_id"
                    class="h-9 rounded-md border bg-transparent px-3 text-sm"
                    required
                >
                    <option value="">Level</option>
                    <option
                        v-for="level in levels"
                        :key="level.id"
                        :value="level.id"
                    >
                        {{ level.name }}
                    </option></select
                ><select
                    v-model="selectedCourse"
                    name="course_id"
                    class="h-9 rounded-md border bg-transparent px-3 text-sm"
                    required
                >
                    <option value="">Course</option>
                    <option
                        v-for="course in courses"
                        :key="course.id"
                        :value="course.id"
                    >
                        {{ course.name }}
                    </option></select
                ><Input
                    name="sort_order"
                    type="number"
                    min="1"
                    placeholder="Display order"
                    required
                /><label class="flex items-center gap-2 text-sm"
                    ><input type="hidden" name="is_required" value="0" /><input
                        name="is_required"
                        type="checkbox"
                        value="1"
                        checked
                        class="accent-primary"
                    />Required</label
                ><label class="flex items-center gap-2 text-sm"
                    ><input type="hidden" name="is_active" value="0" /><input
                        name="is_active"
                        type="checkbox"
                        value="1"
                        checked
                        class="accent-primary"
                    />Active</label
                >
            </div>
            <div v-if="selectedCourse" class="space-y-2">
                <div class="text-sm font-medium">PACE sequence</div>
                <div
                    v-if="availablePaces.length"
                    class="grid max-h-64 grid-cols-2 gap-2 overflow-y-auto rounded-md border p-3 sm:grid-cols-4 lg:grid-cols-8"
                >
                    <label
                        v-for="pace in availablePaces"
                        :key="pace.id"
                        class="flex items-center gap-2 text-sm"
                        ><input
                            type="checkbox"
                            name="pace_ids[]"
                            :value="pace.id"
                            checked
                            class="accent-primary"
                        /><span class="font-mono">{{
                            pace.number
                        }}</span></label
                    >
                </div>
                <p v-else class="text-sm text-destructive">
                    This course has no PACEs. Add or import them first.
                </p>
            </div>
            <div class="text-sm text-destructive">
                {{ Object.values(errors)[0] }}
            </div>
            <Button
                type="submit"
                :disabled="processing || availablePaces.length === 0"
                ><Save class="size-4" />Save sequence</Button
            ></Form
        >
        <div class="overflow-x-auto rounded-md border">
            <table class="w-full min-w-4xl text-sm">
                <thead class="border-b bg-muted/40 text-left">
                    <tr>
                        <th class="px-4 py-3">Level</th>
                        <th class="px-4 py-3">Course</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Order</th>
                        <th class="px-4 py-3">PACE sequence</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="requirement in requirements"
                        :key="requirement.id"
                    >
                        <td class="px-4 py-3 font-medium">
                            {{ requirement.level.name }}
                        </td>
                        <td class="px-4 py-3">{{ requirement.course.name }}</td>
                        <td class="px-4 py-3">
                            {{
                                requirement.is_required
                                    ? 'Required'
                                    : 'Elective'
                            }}
                        </td>
                        <td class="px-4 py-3">{{ requirement.sort_order }}</td>
                        <td class="max-w-xl px-4 py-3 font-mono text-xs">
                            {{
                                requirement.paces
                                    .map((pace) => pace.number)
                                    .join(', ')
                            }}
                        </td>
                        <td class="px-4 py-3">
                            <Badge
                                :variant="
                                    requirement.is_active
                                        ? 'default'
                                        : 'outline'
                                "
                                >{{
                                    requirement.is_active
                                        ? 'Active'
                                        : 'Inactive'
                                }}</Badge
                            >
                        </td>
                    </tr>
                    <tr v-if="requirements.length === 0">
                        <td
                            colspan="6"
                            class="px-4 py-12 text-center text-muted-foreground"
                        >
                            No curriculum requirements configured.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
