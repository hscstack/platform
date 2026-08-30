<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    X,
    Loader2,
    AlertCircle,
    ChevronDown,
    ChevronRight,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps<{
    isOpen: boolean;
    subject: {
        id: number;
        name: string;
        slug: string;
    };
    parent?: {
        id: number;
        name: string;
    } | null;
    node?: {
        id: number;
        name: string;
        slug: string;
        sort_order?: number;
    } | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const name = ref('');
const slug = ref('');
const sortOrder = ref(0);
const showAdvanced = ref(false);
const isSaving = ref(false);
const errorMessage = ref('');

const initForm = () => {
    if (props.node) {
        name.value = props.node.name || '';
        slug.value = props.node.slug || '';
        sortOrder.value = props.node.sort_order ?? 0;
    } else {
        name.value = '';
        slug.value = '';
        sortOrder.value = 0;
    }

    errorMessage.value = '';
    showAdvanced.value = false;
};

watch(
    () => props.isOpen,
    (open) => {
        if (open) {
            initForm();
        }
    },
);

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

    const payload = {
        name: name.value,
        slug: slug.value || null,
        parent_id: props.parent?.id || null,
        sort_order: sortOrder.value,
    };

    if (props.node) {
        router.patch(
            `/admin/subjects/${props.subject.id}/nodes/${props.node.id}`,
            payload,
            {
                preserveScroll: true,
                onSuccess: () => {
                    isSaving.value = false;
                    emit('close');
                },
                onError: (errors) => {
                    isSaving.value = false;
                    errorMessage.value =
                        Object.values(errors).flat().join(', ') ||
                        'Failed to update folder.';
                },
            },
        );
    } else {
        router.post(`/admin/subjects/${props.subject.id}/nodes`, payload, {
            preserveScroll: true,
            onSuccess: () => {
                isSaving.value = false;
                emit('close');
            },
            onError: (errors) => {
                isSaving.value = false;
                errorMessage.value =
                    Object.values(errors).flat().join(', ') ||
                    'Failed to create folder.';
            },
        });
    }
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
                    class="relative flex max-h-[92vh] w-full max-w-lg flex-col rounded-t-2xl border border-slate-200 bg-white shadow-xl sm:rounded-2xl dark:border-gray-800 dark:bg-gray-900"
                >
                    <!-- Header -->
                    <div
                        class="flex items-center justify-between border-b border-slate-100 px-4 py-3.5 sm:px-6 dark:border-gray-800"
                    >
                        <div class="min-w-0 flex-1 pr-3">
                            <h3
                                class="truncate text-base font-bold text-slate-900 dark:text-gray-100"
                            >
                                {{ node ? 'Edit Folder' : 'Create Folder' }}
                            </h3>
                            <p
                                class="truncate text-xs text-slate-500 dark:text-gray-400"
                            >
                                In:
                                <span
                                    class="font-medium text-slate-700 dark:text-gray-300"
                                >
                                    {{ parent ? parent.name : subject.name }}
                                </span>
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
                            <!-- Folder Name -->
                            <div class="space-y-1.5">
                                <label
                                    for="single_folder_name"
                                    class="block text-xs font-bold text-slate-700 dark:text-gray-300"
                                >
                                    Folder Name
                                    <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    id="single_folder_name"
                                    v-model="name"
                                    type="text"
                                    placeholder="e.g., Chapter 1: Introduction"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                                    autofocus
                                    required
                                />
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
                                    <span>Advanced settings (Slug, Order)</span>
                                </button>

                                <div
                                    v-if="showAdvanced"
                                    class="mt-2.5 space-y-3 rounded-xl border border-slate-200 bg-slate-50/70 p-3.5 sm:p-4 dark:border-gray-700/80 dark:bg-gray-800/40"
                                >
                                    <!-- Custom Slug -->
                                    <div class="space-y-1">
                                        <label
                                            for="single_folder_slug"
                                            class="block text-[11px] font-semibold text-slate-700 dark:text-gray-300"
                                        >
                                            Custom Slug
                                            <span
                                                class="font-normal text-slate-400"
                                                >(Optional)</span
                                            >
                                        </label>
                                        <input
                                            id="single_folder_slug"
                                            v-model="slug"
                                            type="text"
                                            placeholder="Auto-generated if empty"
                                            class="dark:bg-gray-850 w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 font-mono text-xs text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none dark:border-gray-700 dark:text-gray-100"
                                        />
                                    </div>

                                    <!-- Sort Order -->
                                    <div class="space-y-1">
                                        <label
                                            for="single_folder_sort_order"
                                            class="block text-[11px] font-semibold text-slate-700 dark:text-gray-300"
                                        >
                                            Sort Order Priority
                                        </label>
                                        <input
                                            id="single_folder_sort_order"
                                            v-model.number="sortOrder"
                                            type="number"
                                            placeholder="0"
                                            class="dark:bg-gray-850 w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-900 focus:border-indigo-500 focus:outline-none dark:border-gray-700 dark:text-gray-100"
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
                                class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-2xs transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <Loader2
                                    v-if="isSaving"
                                    class="h-3.5 w-3.5 animate-spin"
                                />
                                <span>{{
                                    isSaving
                                        ? 'Saving...'
                                        : node
                                          ? 'Update Folder'
                                          : 'Create Folder'
                                }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
