<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, UserPlus } from '@lucide/vue';
import StudentController from '@/actions/App/Http/Controllers/StudentController';
import Heading from '@/components/Heading.vue';
import StudentFields from '@/components/students/StudentFields.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/students';
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Students', href: index() },
            { title: 'Register student', href: '#' },
        ],
    },
});
type Grade = {
    id: number;
    name: string;
    learning_center: { id: number; name: string };
};
defineProps<{ grades: Grade[] }>();
</script>
<template>
    <Head title="Register student" />
    <div class="flex max-w-5xl flex-1 flex-col gap-6 p-4 md:p-6">
        <Button variant="ghost" size="sm" class="w-fit" as-child
            ><Link :href="index()"
                ><ArrowLeft class="size-4" />Back to students</Link
            ></Button
        ><Heading
            title="Register student"
            description="Create the student record, then continue to academic enrolment and course placement"
        /><Form
            v-bind="StudentController.store.form()"
            class="space-y-8"
            v-slot="{ errors, processing }"
            ><StudentFields :errors="errors" :grades="grades" show-grade />
            <div class="flex justify-end border-t pt-5">
                <Button type="submit" :disabled="processing"
                    ><UserPlus class="size-4" />Register and continue</Button
                >
            </div></Form
        >
    </div>
</template>
