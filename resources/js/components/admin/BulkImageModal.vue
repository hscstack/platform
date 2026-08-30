<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Upload,
    X,
    Trash2,
    FileSpreadsheet,
    Hash,
    Loader2,
    AlertCircle,
} from 'lucide-vue-next';
import { ref, computed, watch, onUnmounted } from 'vue';

const props = defineProps<{
    isOpen: boolean;
    node: {
        id: number;
        name: string;
    };
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const selectedFiles = ref<{ id: string; file: File; previewUrl: string }[]>([]);
const isDragging = ref(false);
const fileLimitError = ref('');
const errorMessage = ref('');
const isSaving = ref(false);
const uploadProgress = ref<number | null>(null);

const namingStrategy = ref<'serial' | 'original'>('serial');
const namingPrefix = ref('image');
const startNumber = ref(1);

const MAX_IMAGES = 20;

const processedTitles = computed(() => {
    return selectedFiles.value.map((item, index) => {
        const num = (Number(startNumber.value) || 1) + index;
        const paddedNum = String(num).padStart(2, '0');

        if (namingStrategy.value === 'serial') {
            const prefix = namingPrefix.value.trim();

            return prefix ? `${prefix} ${paddedNum}` : paddedNum;
        }

        return item.file.name.replace(/\.[^/.]+$/, '');
    });
});

const addFiles = (files: FileList | File[]) => {
    fileLimitError.value = '';
    const imageFiles = Array.from(files).filter((file) =>
        file.type.startsWith('image/'),
    );

    if (imageFiles.length === 0) {
        return;
    }

    if (selectedFiles.value.length + imageFiles.length > MAX_IMAGES) {
        fileLimitError.value = 'অনুগ্রহ করে ২০টি বা তার কম ছবি নির্বাচন করুন।';

        return;
    }

    imageFiles.forEach((file) => {
        selectedFiles.value.push({
            id: `${file.name}-${Date.now()}-${Math.random()}`,
            file,
            previewUrl: URL.createObjectURL(file),
        });
    });
};

const handleFileSelect = (event: Event) => {
    const input = event.target as HTMLInputElement;

    if (input.files?.length) {
        addFiles(input.files);
        input.value = '';
    }
};

const handleDrop = (event: DragEvent) => {
    isDragging.value = false;

    if (event.dataTransfer?.files?.length) {
        addFiles(event.dataTransfer.files);
    }
};

const removeFile = (index: number) => {
    if (selectedFiles.value[index]) {
        URL.revokeObjectURL(selectedFiles.value[index].previewUrl);
        selectedFiles.value.splice(index, 1);
        fileLimitError.value = '';
    }
};

const clearAll = () => {
    selectedFiles.value.forEach((item) => URL.revokeObjectURL(item.previewUrl));
    selectedFiles.value = [];
    fileLimitError.value = '';
    errorMessage.value = '';
    isSaving.value = false;
    uploadProgress.value = null;
};

const handleClose = () => {
    if (isSaving.value) {
        return;
    }

    clearAll();
    emit('close');
};

watch(
    () => props.isOpen,
    (open) => {
        if (!open) {
            clearAll();
        }
    },
);

onUnmounted(() => {
    clearAll();
});

const submitForm = () => {
    if (selectedFiles.value.length === 0 || isSaving.value) {
        return;
    }

    isSaving.value = true;
    uploadProgress.value = 0;
    errorMessage.value = '';

    const payload: Record<string, any> = {
        node_id: props.node.id,
        naming_strategy: namingStrategy.value,
        naming_prefix: namingPrefix.value,
        start_number: startNumber.value,
        files: selectedFiles.value.map((item) => item.file),
        custom_titles: processedTitles.value,
    };

    router.post('/admin/resources/bulk/images', payload, {
        forceFormData: true,
        preserveScroll: true,
        onProgress: (progress) => {
            if (progress?.percentage !== undefined) {
                uploadProgress.value = progress.percentage;
            }
        },
        onSuccess: () => {
            clearAll();
            emit('close');
        },
        onError: (errors) => {
            errorMessage.value =
                Object.values(errors).flat().join(', ') ||
                'Failed to upload images.';
        },
        onFinish: () => {
            isSaving.value = false;
            uploadProgress.value = null;
        },
    });
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
                    class="relative flex max-h-[92vh] w-full max-w-2xl flex-col rounded-t-2xl border border-slate-200 bg-white shadow-xl sm:rounded-2xl dark:border-gray-800 dark:bg-gray-900"
                >
                    <!-- Header -->
                    <div
                        class="flex items-center justify-between border-b border-slate-100 px-4 py-3.5 sm:px-6 dark:border-gray-800"
                    >
                        <div class="min-w-0 flex-1 pr-3">
                            <h3
                                class="truncate text-base font-bold text-slate-900 dark:text-gray-100"
                            >
                                Upload Multiple Images
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

                    <!-- Form Body -->
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
                            <!-- Dropzone Section -->
                            <div class="space-y-1.5">
                                <div
                                    class="flex items-center justify-between text-xs"
                                >
                                    <label
                                        class="font-bold text-slate-700 dark:text-gray-300"
                                    >
                                        Select Images
                                    </label>
                                    <span
                                        class="text-[11px] font-medium text-slate-400 dark:text-gray-500"
                                    >
                                        Max 20 images per batch
                                    </span>
                                </div>

                                <div
                                    @dragover.prevent="isDragging = true"
                                    @dragleave.prevent="isDragging = false"
                                    @drop.prevent="handleDrop"
                                    class="relative rounded-xl border-2 border-dashed p-6 text-center transition-all"
                                    :class="
                                        isDragging
                                            ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/20'
                                            : 'border-slate-200 bg-slate-50/50 hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800/40 dark:hover:bg-gray-800/70'
                                    "
                                >
                                    <input
                                        type="file"
                                        id="modal-bulk-image-upload"
                                        multiple
                                        accept="image/jpeg,image/png,image/jpg,image/webp,image/gif"
                                        class="hidden"
                                        @change="handleFileSelect"
                                    />
                                    <label
                                        for="modal-bulk-image-upload"
                                        class="flex cursor-pointer flex-col items-center justify-center"
                                    >
                                        <div
                                            class="mb-2 rounded-full border border-slate-200 bg-white p-2.5 text-indigo-600 shadow-2xs dark:border-gray-700 dark:bg-gray-800 dark:text-indigo-400"
                                        >
                                            <Upload class="h-5 w-5" />
                                        </div>
                                        <span
                                            class="text-xs font-semibold text-slate-700 dark:text-gray-300"
                                        >
                                            Click to select or drag & drop
                                            images
                                        </span>
                                        <span
                                            class="mt-1 text-[11px] text-slate-400 dark:text-gray-500"
                                        >
                                            JPG, PNG, WEBP, GIF (Max 10MB per
                                            file)
                                        </span>
                                    </label>
                                </div>

                                <div
                                    v-if="fileLimitError"
                                    class="flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50/80 p-2.5 text-xs text-amber-800 dark:border-amber-500/30 dark:bg-amber-950/40 dark:text-amber-300"
                                >
                                    <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                    <span>{{ fileLimitError }}</span>
                                </div>
                            </div>

                            <!-- Naming Settings -->
                            <div
                                v-if="selectedFiles.length > 0"
                                class="space-y-3 rounded-xl border border-slate-200 bg-slate-50/50 p-4 dark:border-gray-700/80 dark:bg-gray-800/40"
                            >
                                <h4
                                    class="text-xs font-bold text-slate-800 dark:text-gray-200"
                                >
                                    File Naming Settings
                                </h4>

                                <div class="grid grid-cols-2 gap-2">
                                    <label
                                        class="flex cursor-pointer items-center gap-2 rounded-lg border bg-white p-2.5 text-xs transition dark:bg-gray-900"
                                        :class="
                                            namingStrategy === 'serial'
                                                ? 'border-indigo-600 font-semibold text-indigo-700 ring-1 ring-indigo-600 dark:border-indigo-500 dark:text-indigo-300'
                                                : 'border-slate-200 text-slate-600 hover:bg-slate-50 dark:border-gray-700 dark:text-gray-400'
                                        "
                                    >
                                        <input
                                            type="radio"
                                            value="serial"
                                            v-model="namingStrategy"
                                            class="text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <Hash class="h-3.5 w-3.5" />
                                        <span>Serial Numbers</span>
                                    </label>

                                    <label
                                        class="flex cursor-pointer items-center gap-2 rounded-lg border bg-white p-2.5 text-xs transition dark:bg-gray-900"
                                        :class="
                                            namingStrategy === 'original'
                                                ? 'border-indigo-600 font-semibold text-indigo-700 ring-1 ring-indigo-600 dark:border-indigo-500 dark:text-indigo-300'
                                                : 'border-slate-200 text-slate-600 hover:bg-slate-50 dark:border-gray-700 dark:text-gray-400'
                                        "
                                    >
                                        <input
                                            type="radio"
                                            value="original"
                                            v-model="namingStrategy"
                                            class="text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <FileSpreadsheet class="h-3.5 w-3.5" />
                                        <span>Original Names</span>
                                    </label>
                                </div>

                                <div
                                    v-if="namingStrategy === 'serial'"
                                    class="grid grid-cols-1 gap-3 pt-1 sm:grid-cols-2"
                                >
                                    <div class="space-y-1">
                                        <label
                                            for="bulk_img_naming_prefix"
                                            class="block text-[11px] font-semibold text-slate-700 dark:text-gray-300"
                                        >
                                            Prefix
                                        </label>
                                        <input
                                            id="bulk_img_naming_prefix"
                                            v-model="namingPrefix"
                                            type="text"
                                            placeholder="e.g. image"
                                            class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                        />
                                    </div>

                                    <div class="space-y-1">
                                        <label
                                            for="bulk_img_start_number"
                                            class="block text-[11px] font-semibold text-slate-700 dark:text-gray-300"
                                        >
                                            Starting Number
                                        </label>
                                        <input
                                            id="bulk_img_start_number"
                                            v-model.number="startNumber"
                                            type="number"
                                            min="1"
                                            placeholder="1"
                                            class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Selected Images List Preview -->
                            <div
                                v-if="selectedFiles.length > 0"
                                class="space-y-2"
                            >
                                <div
                                    class="flex items-center justify-between text-xs"
                                >
                                    <label
                                        class="font-bold text-slate-700 dark:text-gray-300"
                                    >
                                        Selected ({{ selectedFiles.length }})
                                    </label>
                                    <button
                                        type="button"
                                        @click="clearAll"
                                        class="flex cursor-pointer items-center gap-1 font-medium text-rose-600 hover:text-rose-700"
                                    >
                                        <Trash2 class="h-3 w-3" />
                                        <span>Clear all</span>
                                    </button>
                                </div>

                                <div
                                    class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 md:grid-cols-4"
                                >
                                    <div
                                        v-for="(item, index) in selectedFiles"
                                        :key="item.id"
                                        class="group relative flex flex-col overflow-hidden rounded-lg border border-slate-200 bg-white p-1.5 shadow-2xs dark:border-gray-700 dark:bg-gray-800"
                                    >
                                        <div
                                            class="relative aspect-video w-full overflow-hidden rounded bg-slate-100 dark:bg-gray-900"
                                        >
                                            <img
                                                :src="item.previewUrl"
                                                :alt="item.file.name"
                                                class="h-full w-full object-cover"
                                            />
                                            <button
                                                type="button"
                                                @click="removeFile(index)"
                                                class="absolute top-1 right-1 flex h-5 w-5 cursor-pointer items-center justify-center rounded-full bg-slate-900/70 text-white backdrop-blur-xs transition hover:bg-rose-600"
                                            >
                                                <X class="h-3 w-3" />
                                            </button>
                                        </div>
                                        <div class="mt-1 px-0.5">
                                            <p
                                                class="truncate text-[11px] font-semibold text-slate-800 dark:text-gray-200"
                                                :title="processedTitles[index]"
                                            >
                                                {{ processedTitles[index] }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Upload Progress Bar -->
                        <div
                            v-if="isSaving && uploadProgress !== null"
                            class="border-t border-slate-100 bg-slate-50/80 px-4 py-3 sm:px-6 dark:border-gray-800 dark:bg-gray-900/80"
                        >
                            <div
                                class="mb-1.5 flex items-center justify-between text-xs"
                            >
                                <span
                                    class="font-medium text-slate-700 dark:text-gray-300"
                                >
                                    {{
                                        uploadProgress >= 100
                                            ? 'Processing images on server...'
                                            : 'Uploading images...'
                                    }}
                                </span>
                                <span
                                    class="font-semibold text-indigo-600 dark:text-indigo-400"
                                >
                                    {{ uploadProgress }}%
                                </span>
                            </div>
                            <div
                                class="h-2 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-gray-700"
                            >
                                <div
                                    class="h-full rounded-full bg-indigo-600 transition-all duration-150 ease-out dark:bg-indigo-500"
                                    :style="{ width: `${uploadProgress}%` }"
                                ></div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div
                            class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-4 py-3 sm:px-6 dark:border-gray-800 dark:bg-gray-900/60"
                        >
                            <button
                                type="button"
                                @click="handleClose"
                                :disabled="isSaving"
                                class="cursor-pointer rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                :disabled="
                                    isSaving || selectedFiles.length === 0
                                "
                                class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-2xs transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <Loader2
                                    v-if="isSaving"
                                    class="h-3.5 w-3.5 animate-spin"
                                />
                                <span>{{
                                    isSaving
                                        ? uploadProgress !== null &&
                                          uploadProgress < 100
                                            ? `Uploading (${uploadProgress}%)`
                                            : 'Processing...'
                                        : `Upload ${selectedFiles.length || ''} Images`
                                }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
