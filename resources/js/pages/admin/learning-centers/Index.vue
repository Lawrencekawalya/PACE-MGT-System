<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Building2, Plus, Save } from '@lucide/vue';
import LearningCenterController from '@/actions/App/Http/Controllers/Admin/LearningCenterController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/admin/learning-centers';

type Level = {
    id: number;
    learning_center_id: number | null;
    name: string;
    code: string;
    learning_center: { id: number; name: string } | null;
};

type Teacher = {
    id: number;
    name: string;
    email: string;
};

type LearningCenter = {
    id: number;
    name: string;
    code: string;
    description: string | null;
    is_active: boolean;
    active_students_count: number;
    levels: Level[];
    teachers: Teacher[];
};

defineProps<{
    learningCenters: LearningCenter[];
    levels: Level[];
    teachers: Teacher[];
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Learning centers', href: index() }] },
});
</script>

<template>
    <Head title="Learning centers" />
    <div class="flex max-w-7xl flex-1 flex-col gap-8 p-4 md:p-6">
        <Heading
            title="Learning centers"
            description="Group exclusive grades and assign the teachers who manage their students"
        />

        <section class="space-y-4 border-b pb-8">
            <div class="flex items-center gap-2">
                <Building2 class="size-5" />
                <h2 class="text-base font-semibold">New learning center</h2>
            </div>
            <Form
                v-bind="LearningCenterController.store.form()"
                class="space-y-5"
                reset-on-success
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="grid gap-2">
                        <Label for="center-name">Name</Label>
                        <Input
                            id="center-name"
                            name="name"
                            placeholder="Lower Learning Center"
                            required
                        />
                        <InputError :message="errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="center-code">Code</Label>
                        <Input
                            id="center-code"
                            name="code"
                            placeholder="LOWER"
                            required
                        />
                        <InputError :message="errors.code" />
                    </div>
                    <label class="flex items-end gap-2 pb-2 text-sm">
                        <input type="hidden" name="is_active" value="0" />
                        <input
                            name="is_active"
                            type="checkbox"
                            value="1"
                            checked
                            class="size-4 accent-primary"
                        />
                        Active
                    </label>
                </div>
                <div class="grid gap-2">
                    <Label for="center-description">Description</Label>
                    <textarea
                        id="center-description"
                        name="description"
                        rows="2"
                        class="w-full rounded-md border bg-transparent px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    />
                    <InputError :message="errors.description" />
                </div>
                <div class="grid gap-5 md:grid-cols-2">
                    <fieldset class="space-y-2">
                        <legend class="text-sm font-medium">Grades</legend>
                        <div class="grid grid-cols-2 gap-2 lg:grid-cols-3">
                            <label
                                v-for="level in levels"
                                :key="level.id"
                                class="flex gap-2 text-sm"
                                :class="{
                                    'text-muted-foreground':
                                        level.learning_center_id !== null,
                                }"
                            >
                                <input
                                    name="level_ids[]"
                                    type="checkbox"
                                    :value="level.id"
                                    :disabled="
                                        level.learning_center_id !== null
                                    "
                                    class="mt-0.5 size-4 accent-primary"
                                />
                                <span>
                                    {{ level.name }}
                                    <span
                                        v-if="level.learning_center"
                                        class="block text-xs"
                                    >
                                        {{ level.learning_center.name }}
                                    </span>
                                </span>
                            </label>
                        </div>
                        <InputError :message="errors.level_ids" />
                    </fieldset>
                    <fieldset class="space-y-2">
                        <legend class="text-sm font-medium">Teachers</legend>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <label
                                v-for="teacher in teachers"
                                :key="teacher.id"
                                class="flex gap-2 text-sm"
                            >
                                <input
                                    name="teacher_ids[]"
                                    type="checkbox"
                                    :value="teacher.id"
                                    class="mt-0.5 size-4 accent-primary"
                                />
                                <span>
                                    {{ teacher.name }}
                                    <span
                                        class="block text-xs text-muted-foreground"
                                    >
                                        {{ teacher.email }}
                                    </span>
                                </span>
                            </label>
                        </div>
                        <InputError :message="errors.teacher_ids" />
                    </fieldset>
                </div>
                <Button type="submit" :disabled="processing">
                    <Plus class="size-4" />
                    Add center
                </Button>
            </Form>
        </section>

        <section
            v-for="center in learningCenters"
            :key="center.id"
            class="space-y-5 border-b pb-8 last:border-0"
        >
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-lg font-semibold">{{ center.name }}</h2>
                <Badge v-if="center.is_active">Active</Badge>
                <Badge v-else variant="outline">Inactive</Badge>
                <span class="text-sm text-muted-foreground">
                    {{ center.active_students_count }} current students
                </span>
            </div>

            <Form
                v-bind="LearningCenterController.update.form(center.id)"
                class="space-y-5"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-3 md:grid-cols-[2fr_1fr_auto]">
                    <div class="grid gap-2">
                        <Label :for="`center-name-${center.id}`">Name</Label>
                        <Input
                            :id="`center-name-${center.id}`"
                            name="name"
                            :default-value="center.name"
                            required
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label :for="`center-code-${center.id}`">Code</Label>
                        <Input
                            :id="`center-code-${center.id}`"
                            name="code"
                            :default-value="center.code"
                            required
                        />
                    </div>
                    <label class="flex items-end gap-2 pb-2 text-sm">
                        <input type="hidden" name="is_active" value="0" />
                        <input
                            name="is_active"
                            type="checkbox"
                            value="1"
                            :checked="center.is_active"
                            class="size-4 accent-primary"
                        />
                        Active
                    </label>
                </div>
                <textarea
                    name="description"
                    rows="2"
                    :value="center.description ?? ''"
                    :aria-label="`${center.name} description`"
                    class="w-full rounded-md border bg-transparent px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
                />
                <div class="grid gap-5 md:grid-cols-2">
                    <fieldset class="space-y-2">
                        <legend class="text-sm font-medium">Grades</legend>
                        <div class="grid grid-cols-2 gap-2 lg:grid-cols-3">
                            <label
                                v-for="level in levels"
                                :key="level.id"
                                class="flex gap-2 text-sm"
                                :class="{
                                    'text-muted-foreground':
                                        level.learning_center_id !== null &&
                                        level.learning_center_id !== center.id,
                                }"
                            >
                                <input
                                    name="level_ids[]"
                                    type="checkbox"
                                    :value="level.id"
                                    :checked="
                                        level.learning_center_id === center.id
                                    "
                                    :disabled="
                                        level.learning_center_id !== null &&
                                        level.learning_center_id !== center.id
                                    "
                                    class="mt-0.5 size-4 accent-primary"
                                />
                                <span>{{ level.name }}</span>
                            </label>
                        </div>
                        <InputError :message="errors.level_ids" />
                    </fieldset>
                    <fieldset class="space-y-2">
                        <legend class="text-sm font-medium">Teachers</legend>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <label
                                v-for="teacher in teachers"
                                :key="teacher.id"
                                class="flex gap-2 text-sm"
                            >
                                <input
                                    name="teacher_ids[]"
                                    type="checkbox"
                                    :value="teacher.id"
                                    :checked="
                                        center.teachers.some(
                                            (item) => item.id === teacher.id,
                                        )
                                    "
                                    class="mt-0.5 size-4 accent-primary"
                                />
                                <span>{{ teacher.name }}</span>
                            </label>
                        </div>
                        <InputError :message="errors.teacher_ids" />
                    </fieldset>
                </div>
                <div class="flex items-center gap-3">
                    <Button type="submit" size="sm" :disabled="processing">
                        <Save class="size-4" />
                        Save center
                    </Button>
                    <InputError
                        :message="
                            errors.name ||
                            errors.code ||
                            errors.description ||
                            errors.is_active
                        "
                    />
                </div>
            </Form>
        </section>

        <p
            v-if="learningCenters.length === 0"
            class="py-10 text-center text-muted-foreground"
        >
            Create the first learning center to assign grades and teachers.
        </p>
    </div>
</template>
