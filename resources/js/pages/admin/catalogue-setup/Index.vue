<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { BookOpen, Layers3, Plus, Save } from '@lucide/vue';
import CourseController from '@/actions/App/Http/Controllers/Admin/CourseController';
import LevelController from '@/actions/App/Http/Controllers/Admin/LevelController';
import SubjectController from '@/actions/App/Http/Controllers/Admin/SubjectController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index } from '@/routes/admin/catalogue-setup';

type Level = {
    id: number;
    name: string;
    code: string;
    sort_order: number;
    is_active: boolean;
    curriculum_requirements_count: number;
};
type Subject = {
    id: number;
    name: string;
    code: string;
    is_active: boolean;
    courses_count: number;
};
type Course = {
    id: number;
    subject_id: number;
    name: string;
    code: string;
    edition: string;
    is_pace_course: boolean;
    is_active: boolean;
    paces_count: number;
    subject: { name: string };
};
defineProps<{ levels: Level[]; subjects: Subject[]; courses: Course[] }>();
defineOptions({
    layout: { breadcrumbs: [{ title: 'Catalogue setup', href: index() }] },
});
</script>

<template>
    <Head title="Catalogue setup" />
    <div class="flex max-w-7xl flex-1 flex-col gap-9 p-4 md:p-6">
        <Heading
            title="Catalogue setup"
            description="Maintain levels, subjects, and courses without deleting historical definitions"
        />

        <section class="space-y-4">
            <div class="flex items-center gap-2">
                <Layers3 class="size-5" />
                <h2 class="text-base font-semibold">Levels</h2>
            </div>
            <Form
                v-bind="LevelController.store.form()"
                class="grid gap-2 md:grid-cols-5"
                reset-on-success
                v-slot="{ processing }"
                ><Input name="name" placeholder="Level name" required /><Input
                    name="code"
                    placeholder="Code"
                    required
                /><Input
                    name="sort_order"
                    type="number"
                    min="1"
                    placeholder="Order"
                    required
                /><label class="flex items-center gap-2 text-sm"
                    ><input type="hidden" name="is_active" value="0" /><input
                        name="is_active"
                        type="checkbox"
                        value="1"
                        checked
                        class="accent-primary"
                    />Active</label
                ><Button type="submit" :disabled="processing"
                    ><Plus class="size-4" />Add level</Button
                ></Form
            >
            <div class="overflow-x-auto rounded-md border">
                <table class="w-full min-w-3xl text-sm">
                    <thead class="border-b bg-muted/40 text-left">
                        <tr>
                            <th class="px-3 py-2">Name</th>
                            <th class="px-3 py-2">Code</th>
                            <th class="px-3 py-2">Order</th>
                            <th class="px-3 py-2">Curricula</th>
                            <th class="px-3 py-2">Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="level in levels" :key="level.id">
                            <td colspan="6" class="p-2">
                                <Form
                                    v-bind="
                                        LevelController.update.form(level.id)
                                    "
                                    class="grid grid-cols-6 items-center gap-2"
                                    v-slot="{ processing }"
                                    ><Input
                                        name="name"
                                        :default-value="level.name"
                                        aria-label="Level name" /><Input
                                        name="code"
                                        :default-value="level.code"
                                        aria-label="Level code" /><Input
                                        name="sort_order"
                                        type="number"
                                        min="1"
                                        :default-value="level.sort_order"
                                        aria-label="Level order" /><span>{{
                                        level.curriculum_requirements_count
                                    }}</span
                                    ><label class="flex gap-1"
                                        ><input
                                            type="hidden"
                                            name="is_active"
                                            value="0"
                                        /><input
                                            name="is_active"
                                            type="checkbox"
                                            value="1"
                                            :checked="level.is_active"
                                            class="accent-primary"
                                        />Active</label
                                    ><Button
                                        size="icon"
                                        variant="ghost"
                                        type="submit"
                                        :disabled="processing"
                                        aria-label="Save level"
                                        ><Save class="size-4" /></Button
                                ></Form>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="space-y-4 border-t pt-8">
            <div class="flex items-center gap-2">
                <BookOpen class="size-5" />
                <h2 class="text-base font-semibold">Subjects</h2>
            </div>
            <Form
                v-bind="SubjectController.store.form()"
                class="grid gap-2 md:grid-cols-4"
                reset-on-success
                v-slot="{ processing }"
                ><Input name="name" placeholder="Subject name" required /><Input
                    name="code"
                    placeholder="Code"
                    required
                /><label class="flex items-center gap-2 text-sm"
                    ><input type="hidden" name="is_active" value="0" /><input
                        name="is_active"
                        type="checkbox"
                        value="1"
                        checked
                        class="accent-primary"
                    />Active</label
                ><Button type="submit" :disabled="processing"
                    ><Plus class="size-4" />Add subject</Button
                ></Form
            >
            <div class="overflow-x-auto rounded-md border">
                <table class="w-full min-w-2xl text-sm">
                    <thead class="border-b bg-muted/40 text-left">
                        <tr>
                            <th class="px-3 py-2">Name</th>
                            <th class="px-3 py-2">Code</th>
                            <th class="px-3 py-2">Courses</th>
                            <th class="px-3 py-2">Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="subject in subjects" :key="subject.id">
                            <td colspan="5" class="p-2">
                                <Form
                                    v-bind="
                                        SubjectController.update.form(
                                            subject.id,
                                        )
                                    "
                                    class="grid grid-cols-5 items-center gap-2"
                                    v-slot="{ processing }"
                                    ><Input
                                        name="name"
                                        :default-value="subject.name"
                                        aria-label="Subject name" /><Input
                                        name="code"
                                        :default-value="subject.code"
                                        aria-label="Subject code" /><span>{{
                                        subject.courses_count
                                    }}</span
                                    ><label class="flex gap-1"
                                        ><input
                                            type="hidden"
                                            name="is_active"
                                            value="0"
                                        /><input
                                            name="is_active"
                                            type="checkbox"
                                            value="1"
                                            :checked="subject.is_active"
                                            class="accent-primary"
                                        />Active</label
                                    ><Button
                                        size="icon"
                                        variant="ghost"
                                        type="submit"
                                        :disabled="processing"
                                        aria-label="Save subject"
                                        ><Save class="size-4" /></Button
                                ></Form>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="space-y-4 border-t pt-8">
            <h2 class="text-base font-semibold">Courses</h2>
            <Form
                v-bind="CourseController.store.form()"
                class="grid gap-2 lg:grid-cols-7"
                reset-on-success
                v-slot="{ processing }"
                ><select
                    name="subject_id"
                    class="h-9 rounded-md border bg-transparent px-3 text-sm"
                    required
                >
                    <option value="">Subject</option>
                    <option
                        v-for="subject in subjects"
                        :key="subject.id"
                        :value="subject.id"
                    >
                        {{ subject.name }}
                    </option></select
                ><Input name="name" placeholder="Course name" required /><Input
                    name="code"
                    placeholder="Code"
                    required
                /><Input
                    name="edition"
                    placeholder="Edition (optional)"
                /><label class="flex items-center gap-1 text-sm"
                    ><input
                        type="hidden"
                        name="is_pace_course"
                        value="0"
                    /><input
                        name="is_pace_course"
                        type="checkbox"
                        value="1"
                        checked
                        class="accent-primary"
                    />PACE course</label
                ><label class="flex items-center gap-1 text-sm"
                    ><input type="hidden" name="is_active" value="0" /><input
                        name="is_active"
                        type="checkbox"
                        value="1"
                        checked
                        class="accent-primary"
                    />Active</label
                ><Button type="submit" :disabled="processing"
                    ><Plus class="size-4" />Add course</Button
                ></Form
            >
            <div class="overflow-x-auto rounded-md border">
                <table class="w-full min-w-5xl text-sm">
                    <thead class="border-b bg-muted/40 text-left">
                        <tr>
                            <th class="px-3 py-2">Subject</th>
                            <th class="px-3 py-2">Course</th>
                            <th class="px-3 py-2">Code</th>
                            <th class="px-3 py-2">Edition</th>
                            <th class="px-3 py-2">PACEs</th>
                            <th class="px-3 py-2">Type</th>
                            <th class="px-3 py-2">Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="course in courses" :key="course.id">
                            <td colspan="8" class="p-2">
                                <Form
                                    v-bind="
                                        CourseController.update.form(course.id)
                                    "
                                    class="grid grid-cols-8 items-center gap-2"
                                    v-slot="{ processing }"
                                >
                                    <select
                                        name="subject_id"
                                        class="h-9 rounded-md border bg-transparent px-2 text-sm"
                                        required
                                    >
                                        <option
                                            v-for="subject in subjects"
                                            :key="subject.id"
                                            :value="subject.id"
                                            :selected="
                                                subject.id === course.subject_id
                                            "
                                        >
                                            {{ subject.name }}
                                        </option>
                                    </select>
                                    <Input
                                        name="name"
                                        :default-value="course.name"
                                        aria-label="Course name"
                                        required
                                    />
                                    <Input
                                        name="code"
                                        :default-value="course.code"
                                        aria-label="Course code"
                                        required
                                    />
                                    <Input
                                        name="edition"
                                        :default-value="course.edition"
                                        aria-label="Course edition"
                                    />
                                    <span>{{ course.paces_count }}</span>
                                    <label class="flex gap-1 text-xs"
                                        ><input
                                            type="hidden"
                                            name="is_pace_course"
                                            value="0"
                                        /><input
                                            name="is_pace_course"
                                            type="checkbox"
                                            value="1"
                                            :checked="course.is_pace_course"
                                            class="accent-primary"
                                        />PACE</label
                                    >
                                    <label class="flex gap-1 text-xs"
                                        ><input
                                            type="hidden"
                                            name="is_active"
                                            value="0"
                                        /><input
                                            name="is_active"
                                            type="checkbox"
                                            value="1"
                                            :checked="course.is_active"
                                            class="accent-primary"
                                        />Active</label
                                    >
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        type="submit"
                                        :disabled="processing"
                                        aria-label="Save course"
                                        ><Save class="size-4"
                                    /></Button>
                                </Form>
                            </td>
                        </tr>
                        <tr v-if="courses.length === 0">
                            <td
                                colspan="6"
                                class="px-3 py-8 text-center text-muted-foreground"
                            >
                                No courses configured.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
