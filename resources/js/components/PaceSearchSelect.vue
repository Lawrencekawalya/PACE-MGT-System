<script setup lang="ts">
import { Check, Search, X } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

type Option = { id: number; label: string };

const props = withDefaults(
    defineProps<{
        options: Option[];
        id?: string;
        modelValue?: number | null;
        name?: string;
        placeholder?: string;
        required?: boolean;
    }>(),
    {
        modelValue: null,
        name: 'pace_id',
        placeholder: 'Search by subject, course, PACE, or title',
        required: false,
    },
);
const emit = defineEmits<{
    'update:modelValue': [value: number | null];
}>();

const root = ref<HTMLElement | null>(null);
const query = ref('');
const selectedId = ref<number | null>(props.modelValue);
const open = ref(false);
const activeIndex = ref(0);
const listId = `pace-options-${Math.random().toString(36).slice(2)}`;
const inputId = props.id ?? `${listId}-input`;

const filteredOptions = computed(() => {
    const terms = query.value.toLowerCase().trim().split(/\s+/).filter(Boolean);
    const matches =
        terms.length === 0
            ? props.options
            : props.options.filter((option) => {
                  const label = option.label.toLowerCase();

                  return terms.every((term) => label.includes(term));
              });

    return matches.slice(0, 60);
});

watch(
    () => props.modelValue,
    (value) => {
        selectedId.value = value;
        query.value =
            props.options.find((option) => option.id === value)?.label ?? '';
    },
    { immediate: true },
);

function selectOption(option: Option): void {
    selectedId.value = option.id;
    query.value = option.label;
    open.value = false;
    emit('update:modelValue', option.id);
}

function search(): void {
    selectedId.value = null;
    activeIndex.value = 0;
    open.value = true;
    emit('update:modelValue', null);
}

function clear(): void {
    query.value = '';
    search();
}

function handleKeydown(event: KeyboardEvent): void {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        open.value = true;

        if (filteredOptions.value.length === 0) {
            return;
        }

        activeIndex.value = Math.min(
            activeIndex.value + 1,
            filteredOptions.value.length - 1,
        );
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        activeIndex.value = Math.max(activeIndex.value - 1, 0);
    } else if (
        event.key === 'Enter' &&
        open.value &&
        filteredOptions.value[activeIndex.value]
    ) {
        event.preventDefault();
        selectOption(filteredOptions.value[activeIndex.value]);
    } else if (event.key === 'Escape') {
        open.value = false;
    }
}

function closeOnOutsideClick(event: PointerEvent): void {
    if (!root.value?.contains(event.target as Node)) {
        open.value = false;
    }
}

onMounted(() => document.addEventListener('pointerdown', closeOnOutsideClick));
onBeforeUnmount(() =>
    document.removeEventListener('pointerdown', closeOnOutsideClick),
);
</script>

<template>
    <div ref="root" class="relative">
        <input :name="name" type="hidden" :value="selectedId ?? ''" />
        <Search
            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
        />
        <input
            :id="inputId"
            v-model="query"
            type="search"
            role="combobox"
            autocomplete="off"
            class="h-9 w-full rounded-md border bg-transparent pr-9 pl-9 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
            :placeholder="placeholder"
            :required="required"
            :aria-expanded="open"
            :aria-controls="listId"
            :aria-activedescendant="
                open && filteredOptions[activeIndex]
                    ? `${listId}-${filteredOptions[activeIndex].id}`
                    : undefined
            "
            @input="search"
            @focus="
                open = true;
                ($event.target as HTMLInputElement).select();
            "
            @keydown="handleKeydown"
        />
        <button
            v-if="query"
            type="button"
            class="absolute top-1/2 right-2 flex size-6 -translate-y-1/2 items-center justify-center text-muted-foreground hover:text-foreground"
            aria-label="Clear PACE selection"
            title="Clear selection"
            @click="clear"
        >
            <X class="size-4" />
        </button>
        <div
            v-if="open"
            :id="listId"
            role="listbox"
            class="absolute z-50 mt-1 max-h-72 w-full overflow-y-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-md"
        >
            <button
                v-for="(option, index) in filteredOptions"
                :id="`${listId}-${option.id}`"
                :key="option.id"
                type="button"
                role="option"
                :aria-selected="selectedId === option.id"
                class="flex w-full items-start gap-2 rounded-sm px-2 py-2 text-left text-sm"
                :class="
                    index === activeIndex
                        ? 'bg-accent text-accent-foreground'
                        : 'hover:bg-accent/60'
                "
                @mouseenter="activeIndex = index"
                @mousedown.prevent="selectOption(option)"
            >
                <Check
                    class="mt-0.5 size-4 shrink-0"
                    :class="
                        selectedId === option.id ? 'opacity-100' : 'opacity-0'
                    "
                />
                <span>{{ option.label }}</span>
            </button>
            <div
                v-if="filteredOptions.length === 0"
                class="px-3 py-6 text-center text-sm text-muted-foreground"
            >
                No matching PACE found.
            </div>
        </div>
    </div>
</template>
