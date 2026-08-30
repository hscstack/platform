<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Atom,
    FlaskConical,
    Dna,
    Sigma,
    Laptop,
    BookOpen,
    PenTool,
    BarChart3,
    Search,
    Check,
    ChevronDown,
    ChevronRight,
    X,
    FolderPlus,
    Loader2,
    AlertCircle,
} from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';

const props = defineProps<{
    isOpen: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const icons: Record<string, any> = {
    Atom,
    FlaskConical,
    Dna,
    Sigma,
    Laptop,
    BookOpen,
    PenTool,
    BarChart3,
    Search,
};

const showAdvanced = ref(false);
const isSaving = ref(false);
const errorMessage = ref('');

const tailwindPresets = [
    'bg-red-50 text-red-600',
    'bg-red-100 text-red-700',
    'bg-blue-50 text-blue-600',
    'bg-blue-100 text-blue-700',
    'bg-purple-50 text-purple-600',
    'bg-purple-100 text-purple-700',
    'bg-emerald-50 text-emerald-600',
    'bg-emerald-100 text-emerald-700',
    'bg-amber-50 text-amber-600',
    'bg-amber-100 text-amber-700',
    'bg-green-50 text-green-600',
    'bg-green-100 text-green-700',
];

const name = ref('');
const englishName = ref('');
const slug = ref('');
const course = ref('hsc');
const tailwindFormat = ref('bg-indigo-50 text-indigo-600');
const icon = ref('BookOpen');
const sortOrder = ref(0);

const resetForm = () => {
    name.value = '';
    englishName.value = '';
    slug.value = '';
    course.value = 'hsc';
    tailwindFormat.value = 'bg-indigo-50 text-indigo-600';
    icon.value = 'BookOpen';
    sortOrder.value = 0;
    errorMessage.value = '';
    showAdvanced.value = false;
};

watch(
    () => props.isOpen,
    (open) => {
        if (open) {
            resetForm();
        }
    },
);

const activeIconComponent = computed(() => icons[icon.value] || BookOpen);

const handleClose = () => {
    if (isSaving.value) {
        return;
    }

    emit('close');
};

const submitForm = () => {
    if (!name.value.trim() || isSaving.value) {
        return;
    }

    isSaving.value = true;
    errorMessage.value = '';

    router.post(
        '/admin/subjects',
        {
            name: name.value,
            english_name: englishName.value || null,
            slug: slug.value || null,
            course: course.value,
            tailwind_format: tailwindFormat.value,
            icon: icon.value,
            sort_order: sortOrder.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isSaving.value = false;
                resetForm();
                emit('close');
            },
            onError: (errors) => {
                isSaving.value = false;
                errorMessage.value =
                    Object.values(errors).flat().join(', ') ||
                    'Failed to create subject.';
            },
        },
    );
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="isOpen"
                class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
            >
                <div
                    class="fixed inset-0 bg-slate-950/40 backdrop-blur-xs dark:bg-black/60"
                    @click="handleClose"
                />

                <div
                    class="relative flex max-h-[92vh] w-full max-w-xl flex-col rounded-t-2xl border border-slate-200 bg-white shadow-xl sm:rounded-2xl dark:border-gray-800 dark:bg-gray-900"
                >
                    <!-- Header -->
                    <div
                        class="flex items-center justify-between border-b border-slate-100 px-4 py-3.5 sm:px-6 dark:border-gray-800"
                    >
                        <div class="min-w-0 flex-1 pr-3">
                            <h3
                                class="truncate text-base font-bold text-slate-900 dark:text-gray-100"
                            >
                                Create Subject
                            </h3>
                            <p
                                class="truncate text-xs text-slate-500 dark:text-gray-400"
                            >
                                Add a new subject to the platform
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="handleClose"
                            class="cursor-pointer rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- Body / Form -->
                    <form
                        @submit.prevent="submitForm"
                        class="flex flex-1 flex-col overflow-hidden"
                    >
                        <!-- Error Banner -->
                        <div
                            v-if="errorMessage"
                            class="m-4 mb-0 flex items-start gap-2.5 rounded-lg border border-rose-200 bg-rose-50/80 p-3 text-xs text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300"
                        >
                            <AlertCircle
                                class="mt-0.5 h-4 w-4 shrink-0 text-rose-600 dark:text-rose-400"
                            />
                            <div class="leading-relaxed">
                                <span class="font-bold">Error: </span>
                                <span>{{ errorMessage }}</span>
                            </div>
                        </div>

                        <div
                            class="flex-1 space-y-4 overflow-y-auto p-4 text-slate-800 sm:p-6 dark:text-gray-200"
                        >
                            <!-- Name & Course Grid -->
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div class="space-y-1.5">
                                    <label
                                        for="subject_name"
                                        class="block text-xs font-bold text-slate-700 dark:text-gray-300"
                                    >
                                        Subject Name
                                        <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        id="subject_name"
                                        v-model="name"
                                        type="text"
                                        placeholder="e.g. পদার্থবিজ্ঞান ১ম পত্র"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                                        required
                                        autofocus
                                    />
                                </div>

                                <div class="space-y-1.5">
                                    <label
                                        for="subject_course"
                                        class="block text-xs font-bold text-slate-700 dark:text-gray-300"
                                    >
                                        Course Level
                                        <span class="text-rose-500">*</span>
                                    </label>
                                    <select
                                        id="subject_course"
                                        v-model="course"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                                    >
                                        <option value="hsc">HSC</option>
                                        <option value="ssc">SSC</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Color Palette -->
                            <div class="space-y-1.5">
                                <label
                                    class="block text-xs font-semibold text-slate-700 dark:text-gray-300"
                                >
                                    Badge Color
                                </label>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        v-for="preset in tailwindPresets"
                                        :key="preset"
                                        @click="tailwindFormat = preset"
                                        class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border transition focus:outline-none"
                                        :class="[
                                            preset,
                                            tailwindFormat === preset
                                                ? 'border-slate-900 shadow-xs ring-2 ring-slate-900/10 dark:border-white'
                                                : 'border-transparent opacity-80 hover:opacity-100',
                                        ]"
                                    >
                                        <Check
                                            v-if="tailwindFormat === preset"
                                            class="h-3.5 w-3.5"
                                        />
                                    </button>
                                </div>
                            </div>

                            <!-- Icon Selection -->
                            <div class="space-y-1.5">
                                <label
                                    class="block text-xs font-semibold text-slate-700 dark:text-gray-300"
                                >
                                    Subject Icon
                                </label>
                                <div
                                    class="grid grid-cols-3 gap-2 sm:grid-cols-5 md:grid-cols-9"
                                >
                                    <button
                                        type="button"
                                        v-for="(
                                            iconComponent, iconKey
                                        ) in icons"
                                        :key="iconKey"
                                        @click="icon = iconKey"
                                        class="flex cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border p-2 text-center transition"
                                        :class="
                                            icon === iconKey
                                                ? 'border-indigo-600 bg-indigo-50 text-indigo-700 ring-1 ring-indigo-600 dark:border-indigo-500 dark:bg-indigo-950/60 dark:text-indigo-300'
                                                : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'
                                        "
                                    >
                                        <component
                                            :is="iconComponent"
                                            class="h-4 w-4 shrink-0"
                                        />
                                        <span
                                            class="max-w-full truncate text-[10px] font-medium"
                                            >{{ iconKey }}</span
                                        >
                                    </button>
                                </div>
                            </div>

                            <!-- Live Card Preview -->
                            <div
                                class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/70 p-3 dark:border-gray-700 dark:bg-gray-800/40"
                            >
                                <div
                                    :class="tailwindFormat"
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-black/5 dark:border-white/10"
                                >
                                    <component
                                        :is="activeIconComponent"
                                        class="h-5 w-5"
                                    />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <h4
                                            class="truncate text-xs font-bold text-slate-900 dark:text-gray-100"
                                        >
                                            {{ name || 'Subject Name' }}
                                        </h4>
                                        <span
                                            class="py-0.2 inline-flex rounded px-1 text-[9px] font-bold uppercase"
                                            :class="
                                                course === 'ssc'
                                                    ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300'
                                                    : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300'
                                            "
                                        >
                                            {{ course }}
                                        </span>
                                    </div>
                                    <p
                                        class="truncate text-[11px] text-slate-400"
                                    >
                                        Live preview
                                    </p>
                                </div>
                            </div>

                            <!-- Advanced Settings Toggle -->
                            <div class="pt-1">
                                <button
                                    type="button"
                                    @click="showAdvanced = !showAdvanced"
                                    class="inline-flex cursor-pointer items-center gap-1.5 text-xs font-semibold text-slate-500 transition hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400"
                                >
                                    <component
                                        :is="
                                            showAdvanced
                                                ? ChevronDown
                                                : ChevronRight
                                        "
                                        class="h-3.5 w-3.5"
                                    />
                                    <span
                                        >Advanced settings (English Name, Slug,
                                        Order)</span
                                    >
                                </button>

                                <div
                                    v-if="showAdvanced"
                                    class="mt-2.5 space-y-3 rounded-xl border border-slate-200 bg-slate-50/70 p-3.5 sm:p-4 dark:border-gray-700/80 dark:bg-gray-800/40"
                                >
                                    <div
                                        class="grid grid-cols-1 gap-3 sm:grid-cols-2"
                                    >
                                        <!-- English Name -->
                                        <div class="space-y-1">
                                            <label
                                                for="subject_english_name"
                                                class="block text-[11px] font-semibold text-slate-700 dark:text-gray-300"
                                            >
                                                English Name
                                                <span
                                                    class="font-normal text-slate-400"
                                                    >(Search keyword)</span
                                                >
                                            </label>
                                            <input
                                                id="subject_english_name"
                                                v-model="englishName"
                                                type="text"
                                                placeholder="e.g. Physics 1st Paper"
                                                class="dark:bg-gray-850 w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none dark:border-gray-700 dark:text-gray-100"
                                            />
                                        </div>

                                        <!-- Sort Order -->
                                        <div class="space-y-1">
                                            <label
                                                for="subject_sort_order"
                                                class="block text-[11px] font-semibold text-slate-700 dark:text-gray-300"
                                            >
                                                Sort Order Priority
                                            </label>
                                            <input
                                                id="subject_sort_order"
                                                v-model.number="sortOrder"
                                                type="number"
                                                placeholder="0"
                                                class="dark:bg-gray-850 w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-900 focus:border-indigo-500 focus:outline-none dark:border-gray-700 dark:text-gray-100"
                                            />
                                        </div>
                                    </div>

                                    <!-- Custom Slug -->
                                    <div class="space-y-1">
                                        <label
                                            for="subject_slug"
                                            class="block text-[11px] font-semibold text-slate-700 dark:text-gray-300"
                                        >
                                            Custom URL Slug
                                            <span
                                                class="font-normal text-slate-400"
                                                >(Optional)</span
                                            >
                                        </label>
                                        <input
                                            id="subject_slug"
                                            v-model="slug"
                                            type="text"
                                            placeholder="Auto-generated if empty"
                                            class="dark:bg-gray-850 w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 font-mono text-xs text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none dark:border-gray-700 dark:text-gray-100"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div
                            class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-4 py-3 sm:px-6 dark:border-gray-800 dark:bg-gray-900/60"
                        >
                            <button
                                type="button"
                                @click="handleClose"
                                class="cursor-pointer rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                :disabled="isSaving || !name.trim()"
                                class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-2xs transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <Loader2
                                    v-if="isSaving"
                                    class="h-3.5 w-3.5 animate-spin"
                                />
                                <FolderPlus v-else class="h-3.5 w-3.5" />
                                <span>{{
                                    isSaving ? 'Saving...' : 'Create Subject'
                                }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
