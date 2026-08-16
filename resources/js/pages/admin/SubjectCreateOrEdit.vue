<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
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
} from 'lucide-vue-next';
import { computed } from 'vue';
import { kInput, kSelect, kButton } from 'konsta/vue';

const icons = {
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
const props = defineProps({
    subject: Object,
});

const form = useForm({
    name: props.subject?.name || '',
    course: props.subject?.course || 'hsc',
    tailwind_format: props.subject?.tailwind_format || 'bg-red-50 text-red-600',
    icon: props.subject?.icon || 'BookOpen',
    sort_order: props.subject?.sort_order || 0,
});

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

const activeIconComponent = computed(() => icons[form.icon] || BookOpen);

const goBack = () => {
    window.history.back();
};

const submitForm = () => {
    if (props.subject) {
        form.patch(`/admin/subjects/edit/${props.subject.id}`, {
            preserveScroll: true,
        });
    } else {
        form.post('/admin/subjects', { preserveScroll: true });
    }
};
</script>

<template>
    <div
        class="flex min-h-full w-full flex-col justify-start bg-slate-50 p-6 lg:p-10 dark:bg-gray-950"
    >
        <div
            class="w-full rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm md:p-10 dark:border-gray-700 dark:bg-gray-900"
        >
            <div
                class="mb-8 flex flex-col justify-between gap-4 border-b border-slate-100 pb-6 sm:flex-row sm:items-center dark:border-gray-800"
            >
                <div>
                    <h1
                        class="text-2xl font-bold text-slate-900 dark:text-gray-100"
                    >
                        {{ props.subject ? 'Edit' : 'Create' }} New Subject
                    </h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                        {{
                            props.subject
                                ? 'Update the subject details below.'
                                : 'Add a new subject category to the platform.'
                        }}
                    </p>
                </div>
                <k-button
                    outline
                    @click="goBack"
                    class="!self-start sm:!self-center"
                >
                    &larr; Back
                </k-button>
            </div>

            <form @submit.prevent="submitForm" class="space-y-8">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div>
                        <k-input
                            label="Subject Name"
                            type="text"
                            :value="form.name"
                            @input="form.name = $event.target.value"
                            placeholder="e.g. Physics..."
                            outline
                            :error="form.errors.name"
                        />
                        <p
                            v-if="form.errors.name"
                            class="mt-1 text-sm text-rose-600"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div>
                        <k-select
                            label="Course"
                            :value="form.course"
                            @change="form.course = $event.target.value"
                            :options="[
                                { value: 'hsc', label: 'HSC' },
                                { value: 'ssc', label: 'SSC' },
                            ]"
                            outline
                            :error="form.errors.course"
                        />
                        <p
                            v-if="form.errors.course"
                            class="mt-1 text-sm text-rose-600"
                        >
                            {{ form.errors.course }}
                        </p>
                    </div>

                    <div>
                        <k-input
                            label="Sort Order"
                            type="number"
                            :value="form.sort_order"
                            @input="
                                form.sort_order = Number($event.target.value)
                            "
                            outline
                            :error="form.errors.sort_order"
                        />
                        <p
                            v-if="form.errors.sort_order"
                            class="mt-1 text-sm text-rose-600"
                        >
                            {{ form.errors.sort_order }}
                        </p>
                    </div>
                </div>

                <div>
                    <label
                        class="mb-3 block text-sm font-semibold text-slate-700 dark:text-gray-300"
                        >Visual Theme Style</label
                    >
                    <div class="flex flex-wrap gap-3">
                        <button
                            type="button"
                            v-for="preset in tailwindPresets"
                            :key="preset"
                            @click="form.tailwind_format = preset"
                            class="flex h-10 w-10 items-center justify-center rounded-full border-2 transition focus:outline-none"
                            :class="[
                                form.tailwind_format === preset
                                    ? preset +
                                      ' scale-110 border-slate-900 shadow-md'
                                    : preset +
                                      ' border-transparent hover:scale-105' +
                                      (preset.includes('100')
                                          ? 'border-slate-200 dark:border-gray-700'
                                          : 'border-slate-150'),
                            ]"
                        >
                            <Check
                                v-if="form.tailwind_format === preset"
                                class="h-4 w-4"
                            />
                        </button>
                    </div>
                    <p
                        v-if="form.errors.tailwind_format"
                        class="mt-1 text-sm text-rose-600"
                    >
                        {{ form.errors.tailwind_format }}
                    </p>
                </div>

                <div>
                    <label
                        class="mb-2.5 block text-sm font-semibold text-slate-700 dark:text-gray-300"
                        >Subject Icon</label
                    >
                    <div
                        class="grid grid-cols-3 gap-3 sm:grid-cols-5 md:grid-cols-9"
                    >
                        <button
                            type="button"
                            v-for="(iconComponent, iconKey) in icons"
                            :key="iconKey"
                            @click="form.icon = iconKey"
                            class="flex flex-col items-center justify-center gap-1.5 rounded-xl border p-3.5 transition-all"
                            :class="
                                form.icon === iconKey
                                    ? 'border-blue-600 bg-blue-50 text-blue-600 ring-2 ring-blue-600/10 dark:bg-blue-500/10 dark:text-blue-400'
                                    : 'border-slate-200 text-slate-600 hover:bg-slate-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800'
                            "
                        >
                            <component
                                :is="iconComponent"
                                class="h-5 w-5 shrink-0"
                            />
                            <span
                                class="max-w-full truncate text-[11px] font-medium tracking-tight"
                                >{{ iconKey }}</span
                            >
                        </button>
                    </div>
                    <p
                        v-if="form.errors.icon"
                        class="mt-1 text-sm text-rose-600"
                    >
                        {{ form.errors.icon }}
                    </p>
                </div>

                <hr class="my-4 border-slate-100 dark:border-gray-800" />

                <div
                    class="rounded-xl border border-slate-200/60 bg-slate-50 p-5 dark:border-gray-700 dark:bg-gray-800"
                >
                    <span
                        class="mb-3.5 block text-xs font-bold tracking-wider text-slate-400 uppercase dark:text-gray-500"
                        >Live Dashboard Card Preview</span
                    >
                    <div
                        class="flex max-w-sm items-center space-x-4 rounded-lg border border-slate-100 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900"
                    >
                        <div
                            :class="form.tailwind_format"
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl transition-all duration-300"
                        >
                            <component
                                :is="activeIconComponent"
                                class="h-6 w-6"
                            />
                        </div>
                        <div class="overflow-hidden">
                            <h4
                                class="truncate font-bold text-slate-800 dark:text-gray-200"
                            >
                                {{ form.name || 'Untitled Subject' }}
                            </h4>
                            <p
                                class="text-xs text-slate-500 dark:text-gray-400"
                            >
                                {{ props.subject?.children_count ?? 0 }}
                                Chapters
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="flex justify-end space-x-3 border-t border-slate-100 pt-4 dark:border-gray-800"
                >
                    <k-button outline @click="goBack"> Cancel </k-button>
                    <k-button
                        type="submit"
                        fill
                        :disabled="form.processing"
                        class="!rounded-lg"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Subject' }}
                    </k-button>
                </div>
            </form>
        </div>
    </div>
</template>
