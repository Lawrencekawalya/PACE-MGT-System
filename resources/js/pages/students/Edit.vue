<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Save } from '@lucide/vue';
import StudentController from '@/actions/App/Http/Controllers/StudentController';
import Heading from '@/components/Heading.vue';
import StudentFields from '@/components/students/StudentFields.vue';
import { Button } from '@/components/ui/button';
import { show } from '@/routes/students';
type Student = {
    id: number;
    teacher_id: number | null;
    admission_number: string;
    first_name: string;
    last_name: string;
    other_names: string | null;
    date_of_birth: string | null;
    gender: string | null;
    guardian_name: string;
    guardian_phone: string;
    guardian_email: string | null;
    notes: string | null;
};
type Teacher = { id: number; name: string };
defineProps<{
    student: Student;
    teachers: Teacher[];
    canAssignTeacher: boolean;
}>();
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Students', href: '/students' },
            { title: 'Edit student', href: '#' },
        ],
    },
});
</script>
<template>
    <Head :title="`Edit ${student.admission_number}`" />
    <div class="flex max-w-5xl flex-1 flex-col gap-6 p-4 md:p-6">
        <Button variant="ghost" size="sm" class="w-fit" as-child
            ><Link :href="show(student.id)"
                ><ArrowLeft class="size-4" />Back to profile</Link
            ></Button
        ><Heading
            :title="`Edit ${student.admission_number}`"
            description="Update student identity, guardian contact, and internal notes"
        /><Form
            v-bind="StudentController.update.form(student.id)"
            class="space-y-8"
            v-slot="{ errors, processing }"
            ><StudentFields
                :student="student"
                :errors="errors"
                :teachers="teachers"
                :can-assign-teacher="canAssignTeacher"
            />
            <div class="flex justify-end border-t pt-5">
                <Button type="submit" :disabled="processing"
                    ><Save class="size-4" />Save profile</Button
                >
            </div></Form
        >
    </div>
</template>
