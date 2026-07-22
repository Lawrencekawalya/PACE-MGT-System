<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Save } from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import StudentEnrollmentController from '@/actions/App/Http/Controllers/StudentEnrollmentController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { show } from '@/routes/students';
type Pace = { id: number; number: string; sequence_order: number };
type Course = {
    id: number;
    name: string;
    subject: { name: string };
    paces: Pace[];
};
type Requirement = {
    course_id: number;
    is_required: boolean;
    course: Course;
    paces: Pace[];
};
type Level = {
    id: number;
    name: string;
    curriculum_requirements: Requirement[];
};
type Term = {
    id: number;
    name: string;
    is_active: boolean;
    starts_on: string;
    ends_on: string;
};
type AcademicYear = {
    id: number;
    name: string;
    is_active: boolean;
    terms: Term[];
};
type Enrollment = {
    id: number;
    academic_year_id: number;
    term_id: number;
    level_id: number;
    enrolled_on: string;
    courses: Array<{
        course_id: number;
        starting_pace_id: number | null;
        placement_reason: string | null;
        status: string;
    }>;
};
type Student = {
    id: number;
    admission_number: string;
    first_name: string;
    last_name: string;
    other_names: string | null;
};
const props = defineProps<{
    student: Student;
    enrollment: Enrollment | null;
    academicYears: AcademicYear[];
    levels: Level[];
    courses: Course[];
    today: string;
}>();
const activeYear = props.academicYears.find((year) => year.is_active);
const selectedYearId = ref<number | ''>(
    props.enrollment?.academic_year_id ?? activeYear?.id ?? '',
);
const selectedTermId = ref<number | ''>(
    props.enrollment?.term_id ??
        activeYear?.terms.find((term) => term.is_active)?.id ??
        '',
);
const selectedLevelId = ref<number | ''>(props.enrollment?.level_id ?? '');
const selectedCourseIds = ref<number[]>(
    props.enrollment?.courses
        .filter((item) => item.status === 'active')
        .map((item) => item.course_id) ?? [],
);
const startingPaces = reactive<Record<number, number | ''>>({});
const placementReasons = reactive<Record<number, string>>({});

for (const placement of props.enrollment?.courses ?? []) {
    if (placement.status === 'active') {
        startingPaces[placement.course_id] = placement.starting_pace_id ?? '';
        placementReasons[placement.course_id] =
            placement.placement_reason ?? '';
    }
}

const selectedYear = computed(() =>
    props.academicYears.find(
        (year) => year.id === Number(selectedYearId.value),
    ),
);
const selectedLevel = computed(() =>
    props.levels.find((level) => level.id === Number(selectedLevelId.value)),
);
const prescribedIds = computed(
    () =>
        selectedLevel.value?.curriculum_requirements.map(
            (item) => item.course_id,
        ) ?? [],
);
const selectedCourses = computed(() =>
    selectedCourseIds.value
        .map((id) => props.courses.find((course) => course.id === id))
        .filter((course): course is Course => Boolean(course)),
);
const isOverride = computed(
    () =>
        [...selectedCourseIds.value].sort((a, b) => a - b).join(',') !==
        [...prescribedIds.value].sort((a, b) => a - b).join(','),
);
const formDefinition = computed(() =>
    props.enrollment
        ? StudentEnrollmentController.update.form({
              student: props.student.id,
              enrollment: props.enrollment.id,
          })
        : StudentEnrollmentController.store.form(props.student.id),
);
function coursePaces(courseId: number): Pace[] {
    return (
        selectedLevel.value?.curriculum_requirements.find(
            (item) => item.course_id === courseId,
        )?.paces ??
        props.courses.find((course) => course.id === courseId)?.paces ??
        []
    );
}
function isPrescribed(courseId: number): boolean {
    return prescribedIds.value.includes(courseId);
}
function applyCurriculum(): void {
    selectedCourseIds.value = [...prescribedIds.value];

    for (const courseId of selectedCourseIds.value) {
        const first = coursePaces(courseId)[0];
        startingPaces[courseId] = first?.id ?? '';
    }
}
function toggleCourse(courseId: number, checked: boolean): void {
    if (checked) {
        if (!selectedCourseIds.value.includes(courseId)) {
            selectedCourseIds.value.push(courseId);
        }

        if (startingPaces[courseId] === undefined) {
            startingPaces[courseId] = coursePaces(courseId)[0]?.id ?? '';
        }
    } else {
        selectedCourseIds.value = selectedCourseIds.value.filter(
            (id) => id !== courseId,
        );
    }
}
function studentName(): string {
    return [
        props.student.first_name,
        props.student.other_names,
        props.student.last_name,
    ]
        .filter(Boolean)
        .join(' ');
}
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Students', href: '/students' },
            { title: 'Enrolment and placement', href: '#' },
        ],
    },
});
</script>
<template>
    <Head :title="enrollment ? 'Edit enrolment' : 'New enrolment'" />
    <div class="flex max-w-[1400px] flex-1 flex-col gap-6 p-4 md:p-6">
        <Button variant="ghost" size="sm" class="w-fit" as-child
            ><Link :href="show(student.id)"
                ><ArrowLeft class="size-4" />Back to profile</Link
            ></Button
        ><Heading
            :title="
                enrollment ? 'Edit enrolment and placement' : 'Enrol student'
            "
            :description="`${studentName()} · ${student.admission_number}`"
        /><Form
            v-bind="formDefinition"
            class="space-y-8"
            v-slot="{ errors, processing }"
            ><section class="space-y-5">
                <div>
                    <h2 class="text-base font-semibold">Academic placement</h2>
                    <p class="text-sm text-muted-foreground">
                        Choose the period and level before reviewing the
                        prescribed curriculum.
                    </p>
                </div>
                <div class="grid gap-5 md:grid-cols-4">
                    <div class="grid gap-2">
                        <Label for="academic_year_id">Academic year</Label
                        ><select
                            id="academic_year_id"
                            v-model.number="selectedYearId"
                            name="academic_year_id"
                            class="h-9 rounded-md border bg-transparent px-3 text-sm"
                            required
                            @change="selectedTermId = ''"
                        >
                            <option value="">Select year</option>
                            <option
                                v-for="year in academicYears"
                                :key="year.id"
                                :value="year.id"
                            >
                                {{ year.name
                                }}{{ year.is_active ? ' · Active' : '' }}
                            </option></select
                        ><span class="text-xs text-destructive">{{
                            errors.academic_year_id
                        }}</span>
                    </div>
                    <div class="grid gap-2">
                        <Label for="term_id">Term</Label
                        ><select
                            id="term_id"
                            v-model.number="selectedTermId"
                            name="term_id"
                            class="h-9 rounded-md border bg-transparent px-3 text-sm"
                            required
                        >
                            <option value="">Select term</option>
                            <option
                                v-for="term in selectedYear?.terms ?? []"
                                :key="term.id"
                                :value="term.id"
                            >
                                {{ term.name
                                }}{{ term.is_active ? ' · Active' : '' }}
                            </option></select
                        ><span class="text-xs text-destructive">{{
                            errors.term_id
                        }}</span>
                    </div>
                    <div class="grid gap-2">
                        <Label for="level_id">Level</Label
                        ><select
                            id="level_id"
                            v-model.number="selectedLevelId"
                            name="level_id"
                            class="h-9 rounded-md border bg-transparent px-3 text-sm"
                            required
                            @change="applyCurriculum"
                        >
                            <option value="">Select level</option>
                            <option
                                v-for="level in levels"
                                :key="level.id"
                                :value="level.id"
                            >
                                {{ level.name }}
                            </option></select
                        ><span class="text-xs text-destructive">{{
                            errors.level_id
                        }}</span>
                    </div>
                    <div class="grid gap-2">
                        <Label for="enrolled_on">Enrolment date</Label
                        ><Input
                            id="enrolled_on"
                            name="enrolled_on"
                            type="date"
                            :default-value="enrollment?.enrolled_on ?? today"
                            required
                        /><span class="text-xs text-destructive">{{
                            errors.enrolled_on
                        }}</span>
                    </div>
                </div>
            </section>
            <section v-if="selectedLevelId" class="space-y-5 border-t pt-7">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold">
                            Course selection
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            Prescribed courses are selected automatically.
                            Additions and removals require an override reason.
                        </p>
                    </div>
                    <Badge variant="outline"
                        >{{ selectedCourseIds.length }} selected</Badge
                    >
                </div>
                <div
                    class="grid max-h-72 gap-2 overflow-y-auto rounded-md border p-3 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <label
                        v-for="course in courses"
                        :key="course.id"
                        class="flex min-h-10 items-center gap-2 border-b px-2 py-2 text-sm last:border-0"
                        ><input
                            type="checkbox"
                            :checked="selectedCourseIds.includes(course.id)"
                            class="size-4 accent-primary"
                            @change="
                                toggleCourse(
                                    course.id,
                                    ($event.target as HTMLInputElement).checked,
                                )
                            "
                        /><span class="min-w-0 flex-1"
                            ><span class="block truncate font-medium">{{
                                course.name
                            }}</span
                            ><span class="text-xs text-muted-foreground">{{
                                course.subject.name
                            }}</span></span
                        ><Badge
                            v-if="isPrescribed(course.id)"
                            variant="secondary"
                            >Prescribed</Badge
                        ></label
                    >
                </div>
                <div v-if="isOverride" class="grid gap-2">
                    <Label for="curriculum_override_reason"
                        >Curriculum override reason</Label
                    ><textarea
                        id="curriculum_override_reason"
                        name="curriculum_override_reason"
                        rows="3"
                        class="rounded-md border bg-transparent px-3 py-2 text-sm"
                        placeholder="Explain the approved addition or removal"
                        required
                    ></textarea
                    ><span class="text-xs text-destructive">{{
                        errors.curriculum_override_reason
                    }}</span>
                </div>
            </section>
            <section
                v-if="selectedCourses.length"
                class="space-y-5 border-t pt-7"
            >
                <div>
                    <h2 class="text-base font-semibold">
                        Starting PACE by course
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Each course is placed independently. Leave a course
                        pending when diagnostic placement is not complete.
                    </p>
                </div>
                <div class="overflow-x-auto rounded-md border">
                    <table class="w-full min-w-4xl text-sm">
                        <thead class="border-b bg-muted/40 text-left">
                            <tr>
                                <th class="px-4 py-3">Course</th>
                                <th class="px-4 py-3">Curriculum</th>
                                <th class="px-4 py-3">Starting PACE</th>
                                <th class="px-4 py-3">Placement reason</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="(course, index) in selectedCourses"
                                :key="course.id"
                            >
                                <td class="px-4 py-3">
                                    <div class="font-medium">
                                        {{ course.name }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ course.subject.name }}
                                    </div>
                                    <input
                                        type="hidden"
                                        :name="`courses[${index}][course_id]`"
                                        :value="course.id"
                                    />
                                </td>
                                <td class="px-4 py-3">
                                    {{
                                        isPrescribed(course.id)
                                            ? 'Prescribed'
                                            : 'Manual addition'
                                    }}
                                </td>
                                <td class="px-4 py-3">
                                    <select
                                        v-model.number="
                                            startingPaces[course.id]
                                        "
                                        :name="`courses[${index}][starting_pace_id]`"
                                        class="h-9 min-w-40 rounded-md border bg-transparent px-3 font-mono text-sm"
                                    >
                                        <option value="">
                                            Placement pending
                                        </option>
                                        <option
                                            v-for="pace in coursePaces(
                                                course.id,
                                            )"
                                            :key="pace.id"
                                            :value="pace.id"
                                        >
                                            {{ pace.number }}
                                        </option>
                                    </select>
                                </td>
                                <td class="px-4 py-3">
                                    <Input
                                        v-model="placementReasons[course.id]"
                                        :name="`courses[${index}][placement_reason]`"
                                        :placeholder="
                                            isPrescribed(course.id)
                                                ? 'Optional note'
                                                : 'Reason for addition'
                                        "
                                        :required="!isPrescribed(course.id)"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <span class="text-sm text-destructive">{{
                    errors.courses
                }}</span>
            </section>
            <div class="flex justify-end border-t pt-5">
                <Button
                    type="submit"
                    :disabled="processing || selectedCourses.length === 0"
                    ><Save class="size-4" />{{
                        enrollment ? 'Save enrolment' : 'Complete enrolment'
                    }}</Button
                >
            </div></Form
        >
    </div>
</template>
