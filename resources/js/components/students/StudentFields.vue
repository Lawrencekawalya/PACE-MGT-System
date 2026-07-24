<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type StudentValues = {
    first_name?: string;
    last_name?: string;
    other_names?: string | null;
    date_of_birth?: string | null;
    gender?: string | null;
    notes?: string | null;
};
type Grade = {
    id: number;
    name: string;
    learning_center: { id: number; name: string };
};
defineProps<{
    student?: StudentValues;
    errors: Record<string, string>;
    grades?: Grade[];
    showGrade?: boolean;
}>();
</script>

<template>
    <section class="space-y-5">
        <div>
            <h2 class="text-base font-semibold">Student identity</h2>
            <p class="text-sm text-muted-foreground">
                The admission number is generated after registration.
            </p>
        </div>
        <div class="grid gap-5 md:grid-cols-2">
            <div v-if="showGrade" class="grid gap-2 md:col-span-2">
                <Label for="level_id">Grade and learning center</Label>
                <select
                    id="level_id"
                    name="level_id"
                    class="h-9 rounded-md border bg-transparent px-3 text-sm"
                    required
                >
                    <option value="" disabled selected>Select a grade</option>
                    <option
                        v-for="grade in grades"
                        :key="grade.id"
                        :value="grade.id"
                    >
                        {{ grade.name }} — {{ grade.learning_center.name }}
                    </option>
                </select>
                <InputError :message="errors.level_id" />
            </div>
            <div class="grid gap-2">
                <Label for="first_name">First name</Label
                ><Input
                    id="first_name"
                    name="first_name"
                    :default-value="student?.first_name"
                    required
                /><InputError :message="errors.first_name" />
            </div>
            <div class="grid gap-2">
                <Label for="last_name">Last name</Label
                ><Input
                    id="last_name"
                    name="last_name"
                    :default-value="student?.last_name"
                    required
                /><InputError :message="errors.last_name" />
            </div>
            <div class="grid gap-2">
                <Label for="other_names">Other names</Label
                ><Input
                    id="other_names"
                    name="other_names"
                    :default-value="student?.other_names || ''"
                /><InputError :message="errors.other_names" />
            </div>
            <div class="grid gap-2">
                <Label for="date_of_birth">Date of birth</Label
                ><Input
                    id="date_of_birth"
                    name="date_of_birth"
                    type="date"
                    :default-value="student?.date_of_birth || ''"
                /><InputError :message="errors.date_of_birth" />
            </div>
            <div class="grid gap-2">
                <Label for="gender">Gender</Label
                ><select
                    id="gender"
                    name="gender"
                    class="h-9 rounded-md border bg-transparent px-3 text-sm"
                >
                    <option value="">Not specified</option>
                    <option value="male" :selected="student?.gender === 'male'">
                        Male
                    </option>
                    <option
                        value="female"
                        :selected="student?.gender === 'female'"
                    >
                        Female
                    </option></select
                ><InputError :message="errors.gender" />
            </div>
        </div>
    </section>

    <section class="grid gap-2 border-t pt-7">
        <Label for="notes">Internal notes</Label
        ><textarea
            id="notes"
            name="notes"
            rows="4"
            class="min-h-24 rounded-md border bg-transparent px-3 py-2 text-sm"
            :value="student?.notes || ''"
        ></textarea
        ><InputError :message="errors.notes" />
    </section>
</template>
