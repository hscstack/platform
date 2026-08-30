<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    FileText,
    File,
    Image as ImageIcon,
    Video,
    Upload,
    Link as LinkIcon,
    X,
    Loader2,
    AlertCircle,
} from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';

const props = defineProps<{
    isOpen: boolean;
    node: {
        id: number;
        name: string;
    };
    resource?: {
        id: number;
        node_id: number;
        resource_type: string;
        title: string;
        content?: string | null;
        external_url?: string | null;
        file_path?: string | null;
    } | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const resourceTypes = [
    {
        id: 'image',
        name: 'Image',
        icon: ImageIcon,
        color: 'text-emerald-600 bg-emerald-50 border-emerald-200 dark:text-emerald-400 dark:bg-emerald-500/10 dark:border-emerald-500/30',
    },
    {
        id: 'pdf',
        name: 'PDF Doc',
        icon: File,
        color: 'text-rose-600 bg-rose-50 border-rose-200 dark:text-rose-400 dark:bg-rose-500/10 dark:border-rose-500/30',
    },
    {
        id: 'video',
        name: 'Video Link',
        icon: Video,
        color: 'text-blue-600 bg-blue-50 border-blue-200 dark:text-blue-400 dark:bg-blue-500/10 dark:border-blue-500/30',
    },
    {
        id: 'note',
        name: 'Text Note',
        icon: FileText,
        color: 'text-amber-600 bg-amber-50 border-amber-200 dark:text-amber-400 dark:bg-amber-500/10 dark:border-amber-500/30',
    },
];

const resourceType = ref<'image' | 'pdf' | 'video' | 'note'>('image');
const title = ref('');
const content = ref('');
const externalUrl = ref('');
const file = ref<File | null>(null);
const isSaving = ref(false);
const errorMessage = ref('');

const requiresFile = computed(() => resourceType.value === 'image');
const requiresLink = computed(() =>
    ['video', 'pdf'].includes(resourceType.value),
);

const initForm = () => {
    if (props.resource) {
        resourceType.value = (props.resource.resource_type as any) || 'image';
        title.value = props.resource.title || '';
        content.value = props.resource.content || '';
        externalUrl.value = props.resource.external_url || '';
        file.value = null;
    } else {
        resourceType.value = 'image';
        title.value = '';
        content.value = '';
        externalUrl.value = '';
        file.value = null;
    }

    errorMessage.value = '';
    isSaving.value = false;
};

watch(
    () => props.isOpen,
    (open) => {
        if (open) {
            initForm();
        }
    },
);

const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;

    if (target.files && target.files[0]) {
        file.value = target.files[0];
    }
};

const handleClose = () => {
    if (isSaving.value) {
        return;
    }

    emit('close');
};

const submitForm = () => {
    if (!title.value.trim() || isSaving.value) {
        return;
    }

    if (requiresFile.value && !file.value && !props.resource?.file_path) {
        errorMessage.value = 'Please select an image file to upload.';

        return;
    }

    if (requiresLink.value && !externalUrl.value.trim()) {
        errorMessage.value = 'Please provide a valid URL.';

        return;
    }

    isSaving.value = true;
    errorMessage.value = '';

    const payload: Record<string, any> = {
        node_id: props.node.id,
        resource_type: resourceType.value,
        title: title.value.trim(),
        content: content.value ? content.value.trim() : null,
    };

    if (requiresFile.value && file.value) {
        payload.file = file.value;
    }

    if (requiresLink.value && externalUrl.value.trim()) {
        payload.external_url = externalUrl.value.trim();
    } else if (props.resource) {
        payload.external_url = null;
    }

    if (props.resource) {
        router.post(`/admin/resources/${props.resource.id}/patch`, payload, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                emit('close');
            },
            onError: (errors) => {
                errorMessage.value =
                    Object.values(errors).flat().join(', ') ||
                    'Failed to update resource.';
            },
            onFinish: () => {
                isSaving.value = false;
            },
        });
    } else {
        router.post('/admin/resources', payload, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                emit('close');
            },
            onError: (errors) => {
                errorMessage.value =
                    Object.values(errors).flat().join(', ') ||
                    'Failed to create resource.';
            },
            onFinish: () => {
                isSaving.value = false;
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
                                {{
                                    resource ? 'Edit Resource' : 'Add Resource'
                                }}
                            </h3>
                            <p
                                class="truncate text-xs text-slate-500 dark:text-gray-400"
                            >
                                In:
                                <span
                                    class="font-medium text-slate-700 dark:text-gray-300"
                                >
                                    {{ node.name }}
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
                            <!-- 1. Resource Type Selector -->
                            <div class="space-y-1.5">
                                <label
                                    class="block text-xs font-bold text-slate-700 dark:text-gray-300"
                                >
                                    Resource Type
                                </label>
                                <div
                                    class="grid grid-cols-2 gap-2 sm:grid-cols-4"
                                >
                                    <button
                                        type="button"
                                        v-for="type in resourceTypes"
                                        :key="type.id"
                                        @click="resourceType = type.id as any"
                                        class="flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-xl border p-2.5 text-center transition focus:outline-none"
                                        :class="
                                            resourceType === type.id
                                                ? 'border-indigo-600 bg-indigo-50/70 font-semibold text-indigo-700 ring-1 ring-indigo-600 dark:border-indigo-500 dark:bg-indigo-950/50 dark:text-indigo-300'
                                                : 'dark:hover:bg-gray-750 border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300'
                                        "
                                    >
                                        <div
                                            class="rounded-lg p-1.5"
                                            :class="type.color"
                                        >
                                            <component
                                                :is="type.icon"
                                                class="h-4 w-4 shrink-0"
                                            />
                                        </div>
                                        <span class="text-[11px]">{{
                                            type.name
                                        }}</span>
                                    </button>
                                </div>
                            </div>

                            <!-- 2. Resource Title -->
                            <div class="space-y-1.5">
                                <label
                                    for="resource_title"
                                    class="block text-xs font-bold text-slate-700 dark:text-gray-300"
                                >
                                    Resource Title
                                    <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    id="resource_title"
                                    v-model="title"
                                    type="text"
                                    placeholder="e.g., Lecture 01 Slide Notes"
                                    maxlength="100"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                                    required
                                    autofocus
                                />
                            </div>

                            <!-- 3. File URL (Only for Video and PDF) -->
                            <div v-if="requiresLink" class="space-y-1.5">
                                <label
                                    for="resource_external_url"
                                    class="block text-xs font-bold text-slate-700 dark:text-gray-300"
                                >
                                    {{
                                        resourceType === 'pdf'
                                            ? 'PDF Resource URL'
                                            : 'Video URL'
                                    }}
                                    <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative flex items-center">
                                    <LinkIcon
                                        class="pointer-events-none absolute left-3 h-3.5 w-3.5 text-slate-400 dark:text-gray-500"
                                    />
                                    <input
                                        id="resource_external_url"
                                        v-model="externalUrl"
                                        type="url"
                                        :placeholder="
                                            resourceType === 'pdf'
                                                ? 'https://example.com/document.pdf'
                                                : 'https://www.youtube.com/watch?v=...'
                                        "
                                        class="w-full rounded-lg border border-slate-300 bg-white py-2 pr-3 pl-9 text-xs text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                                        required
                                    />
                                </div>
                            </div>

                            <!-- 4. File Upload (Only for Image) -->
                            <div v-if="requiresFile" class="space-y-1.5">
                                <label
                                    class="block text-xs font-bold text-slate-700 dark:text-gray-300"
                                >
                                    Upload Image
                                    <span class="text-rose-500">*</span>
                                </label>
                                <div
                                    class="rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/50 p-4 text-center transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800/60"
                                >
                                    <input
                                        type="file"
                                        id="resource_file_upload"
                                        class="hidden"
                                        @change="handleFileSelect"
                                        accept="image/jpeg,image/png,image/jpg"
                                    />
                                    <label
                                        for="resource_file_upload"
                                        class="flex cursor-pointer flex-col items-center justify-center"
                                    >
                                        <div
                                            class="mb-1.5 rounded-full border border-slate-200 bg-white p-2 text-slate-500 shadow-2xs dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
                                        >
                                            <Upload class="h-4 w-4" />
                                        </div>
                                        <span
                                            class="text-xs font-medium text-indigo-600 dark:text-indigo-400"
                                        >
                                            {{
                                                file
                                                    ? file.name
                                                    : 'Choose image file or drag here'
                                            }}
                                        </span>
                                        <span
                                            class="mt-0.5 text-[10px] text-slate-400 dark:text-gray-500"
                                        >
                                            Max size: 10MB (JPG, JPEG, PNG)
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <!-- 5. Content Body (Optional notes/description) -->
                            <div class="space-y-1.5">
                                <label
                                    for="resource_content"
                                    class="block text-xs font-semibold text-slate-700 dark:text-gray-300"
                                >
                                    Description / Body
                                    <span
                                        class="font-normal text-slate-400 dark:text-gray-500"
                                        >(Optional)</span
                                    >
                                </label>
                                <textarea
                                    id="resource_content"
                                    v-model="content"
                                    rows="3"
                                    placeholder="Type optional descriptions, lecture overview, or note contents..."
                                    class="w-full rounded-lg border border-slate-300 bg-white p-2.5 text-xs text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                                />
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
                                :disabled="isSaving || !title.trim()"
                                class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-2xs transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <Loader2
                                    v-if="isSaving"
                                    class="h-3.5 w-3.5 animate-spin"
                                />
                                <span>{{
                                    isSaving
                                        ? 'Saving...'
                                        : resource
                                          ? 'Update Resource'
                                          : 'Create Resource'
                                }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
