<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { Eye, Plus, Search } from '@lucide/vue';
import { ref } from 'vue';
import PaceController from '@/actions/App/Http/Controllers/Admin/PaceController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index, show } from '@/routes/admin/paces';

type Option = { id: number; name: string };
type Pace = {
    id: number;
    number: string;
    title: string | null;
    edition: string;
    sequence_order: number;
    is_active: boolean;
    course: { id: number; name: string; subject: { name: string } };
};
const props = defineProps<{
    paces: {
        data: Pace[];
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    filters: {
        search: string;
        course_id: number | null;
        subject_id: number | null;
        level_id: number | null;
        status: string;
    };
    courses: Option[];
    subjects: Option[];
    levels: Option[];
    canManage: boolean;
    summary: {
        total: number;
        inactive: number;
        courses_without_paces: number;
        duplicates: number;
    };
}>();
const search = ref(props.filters.search);
const courseId = ref(props.filters.course_id ?? '');
const subjectId = ref(props.filters.subject_id ?? '');
const levelId = ref(props.filters.level_id ?? '');
const status = ref(props.filters.status);
function filter(): void {
    router.get(
        index().url,
        {
            search: search.value,
            course_id: courseId.value,
            subject_id: subjectId.value,
            level_id: levelId.value,
            status: status.value,
        },
        { preserveState: true, replace: true },
    );
}
defineOptions({
    layout: { breadcrumbs: [{ title: 'PACE catalogue', href: index() }] },
});
</script>

<template>
    <Head title="PACE catalogue" />
    <div class="flex max-w-[1500px] flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading
            title="PACE catalogue"
            description="Search individual PACEs and maintain their course identity and sequence"
        />
        <div
            class="grid grid-cols-2 gap-px overflow-hidden rounded-md border bg-border lg:grid-cols-4"
        >
            <div class="bg-background p-4">
                <div class="text-2xl font-semibold">{{ summary.total }}</div>
                <div class="text-xs text-muted-foreground">
                    Individual PACEs
                </div>
            </div>
            <div class="bg-background p-4">
                <div class="text-2xl font-semibold">{{ paces.total }}</div>
                <div class="text-xs text-muted-foreground">
                    Matching filters
                </div>
            </div>
            <div class="bg-background p-4">
                <div class="text-2xl font-semibold">{{ summary.inactive }}</div>
                <div class="text-xs text-muted-foreground">Inactive</div>
            </div>
            <div class="bg-background p-4">
                <div class="text-2xl font-semibold">
                    {{ summary.courses_without_paces }}
                </div>
                <div class="text-xs text-muted-foreground">
                    Courses missing PACEs
                </div>
            </div>
        </div>

        <Form
            v-if="canManage"
            v-bind="PaceController.store.form()"
            class="grid gap-2 border-y py-5 lg:grid-cols-7"
            reset-on-success
            v-slot="{ processing }"
            ><select
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
            ><Input name="number" placeholder="PACE number" required /><Input
                name="title"
                placeholder="Title (optional)"
            /><Input name="edition" placeholder="Edition" /><Input
                name="sequence_order"
                type="number"
                min="1"
                placeholder="Sequence"
                required
            /><label class="flex items-center gap-1 text-sm"
                ><input type="hidden" name="is_active" value="0" /><input
                    name="is_active"
                    type="checkbox"
                    value="1"
                    checked
                    class="accent-primary"
                />Active</label
            ><Button type="submit" :disabled="processing"
                ><Plus class="size-4" />Add PACE</Button
            ></Form
        >

        <form class="grid gap-2 lg:grid-cols-6" @submit.prevent="filter">
            <div class="relative lg:col-span-2">
                <Search
                    class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                /><Input
                    v-model="search"
                    class="pl-9"
                    placeholder="Number, title, or course"
                    aria-label="Search catalogue"
                />
            </div>
            <select
                v-model="subjectId"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">All subjects</option>
                <option
                    v-for="subject in subjects"
                    :key="subject.id"
                    :value="subject.id"
                >
                    {{ subject.name }}
                </option></select
            ><select
                v-model="courseId"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">All courses</option>
                <option
                    v-for="course in courses"
                    :key="course.id"
                    :value="course.id"
                >
                    {{ course.name }}
                </option></select
            ><select
                v-model="levelId"
                class="h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">All levels</option>
                <option
                    v-for="level in levels"
                    :key="level.id"
                    :value="level.id"
                >
                    {{ level.name }}
                </option>
            </select>
            <div class="flex gap-2">
                <select
                    v-model="status"
                    class="h-9 min-w-28 flex-1 rounded-md border bg-transparent px-3 text-sm"
                >
                    <option value="">Any status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option></select
                ><Button type="submit" variant="secondary">Filter</Button>
            </div>
        </form>

        <div class="overflow-x-auto rounded-md border">
            <table class="w-full min-w-4xl text-sm">
                <thead class="border-b bg-muted/40 text-left">
                    <tr>
                        <th class="px-4 py-3">PACE</th>
                        <th class="px-4 py-3">Course</th>
                        <th class="px-4 py-3">Subject</th>
                        <th class="px-4 py-3">Edition</th>
                        <th class="px-4 py-3">Sequence</th>
                        <th class="px-4 py-3">Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="pace in paces.data" :key="pace.id">
                        <td class="px-4 py-3">
                            <div class="font-mono font-semibold">
                                {{ pace.number }}
                            </div>
                            <div
                                v-if="pace.title"
                                class="text-xs text-muted-foreground"
                            >
                                {{ pace.title }}
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ pace.course.name }}</td>
                        <td class="px-4 py-3">
                            {{ pace.course.subject.name }}
                        </td>
                        <td class="px-4 py-3">
                            {{ pace.edition || 'Default' }}
                        </td>
                        <td class="px-4 py-3">{{ pace.sequence_order }}</td>
                        <td class="px-4 py-3">
                            <Badge
                                :variant="
                                    pace.is_active ? 'default' : 'outline'
                                "
                                >{{
                                    pace.is_active ? 'Active' : 'Inactive'
                                }}</Badge
                            >
                        </td>
                        <td class="px-4 py-3">
                            <Button size="icon" variant="ghost" as-child
                                ><Link
                                    :href="show(pace.id)"
                                    :aria-label="`View PACE ${pace.number}`"
                                    ><Eye class="size-4" /></Link
                            ></Button>
                        </td>
                    </tr>
                    <tr v-if="paces.data.length === 0">
                        <td
                            colspan="7"
                            class="px-4 py-12 text-center text-muted-foreground"
                        >
                            No PACEs match these filters.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="flex items-center justify-between text-sm">
            <span class="text-muted-foreground">{{ paces.total }} results</span>
            <div class="flex gap-2">
                <Button
                    size="sm"
                    variant="outline"
                    :disabled="!paces.prev_page_url"
                    as-child
                    ><Link
                        v-if="paces.prev_page_url"
                        :href="paces.prev_page_url"
                        >Previous</Link
                    ><span v-else>Previous</span></Button
                ><Button
                    size="sm"
                    variant="outline"
                    :disabled="!paces.next_page_url"
                    as-child
                    ><Link
                        v-if="paces.next_page_url"
                        :href="paces.next_page_url"
                        >Next</Link
                    ><span v-else>Next</span></Button
                >
            </div>
        </div>
    </div>
</template>
