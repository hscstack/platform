<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { Upload, X, Trash2, FileSpreadsheet, Hash, Tag } from 'lucide-vue-next';
import { ref, computed, onUnmounted } from 'vue';

const props = defineProps({
    redirect: {
        type: String,
        default: '/',
    },
    node: {
        type: Object,
        required: true,
        default: () => ({ id: null }),
    },
});

const selectedFiles = ref([]);
const isDragging = ref(false);

const form = useForm({
    node_id: props.node?.id ?? null,
    naming_strategy: 'original', // 'original' | 'serial' | 'suffix'
    naming_suffix: '',
    start_number: 1,
    files: [],
    custom_titles: [],
    redirect: props.redirect,
});

// Compute titles dynamically based on selected naming strategy
const processedTitles = computed(() => {
    return selectedFiles.value.map((item, index) => {
        const num = (Number(form.start_number) || 1) + index;
        const paddedNum = String(num).padStart(2, '0');

        if (form.naming_strategy === 'serial') {
            return paddedNum;
        }

        if (form.naming_strategy === 'suffix') {
            const prefix = form.naming_suffix.trim();

            return prefix ? `${prefix} ${paddedNum}` : paddedNum;
        }

        // Original default name without file extension
        return item.file.name.replace(/\.[^/.]+$/, '');
    });
});

const addFiles = (files) => {
    const imageFiles = Array.from(files).filter((file) =>
        file.type.startsWith('image/'),
    );

    imageFiles.forEach((file) => {
        selectedFiles.value.push({
            id: `${file.name}-${Date.now()}-${Math.random()}`,
            file,
            previewUrl: URL.createObjectURL(file),
        });
    });
};

const handleFileSelect = (event) => {
    const input = event.target;

    if (input.files?.length) {
        addFiles(input.files);
        input.value = ''; // Reset input to allow selecting same files again if needed
    }
};

const handleDrop = (event) => {
    isDragging.value = false;

    if (event.dataTransfer?.files?.length) {
        addFiles(event.dataTransfer.files);
    }
};

const removeFile = (index) => {
    if (selectedFiles.value[index]) {
        URL.revokeObjectURL(selectedFiles.value[index].previewUrl);
        selectedFiles.value.splice(index, 1);
    }
};

const clearAll = () => {
    selectedFiles.value.forEach((item) => URL.revokeObjectURL(item.previewUrl));
    selectedFiles.value = [];
};

// Memory cleanup on component destruction
onUnmounted(() => {
    clearAll();
});

const submitForm = () => {
    if (selectedFiles.value.length === 0) {
        return;
    }

    form.files = selectedFiles.value.map((item) => item.file);
    form.custom_titles = processedTitles.value;

    form.post('/admin/resources/bulk/images', {
        forceFormData: true,
        onSuccess: () => {
            clearAll();
        },
    });
};
</script>

<template>
    <div
        class="flex min-h-full w-full flex-col justify-start bg-slate-50 p-6 lg:p-10 dark:bg-gray-950"
    >
        <div
            class="mx-auto w-full max-w-4xl rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm md:p-10 dark:border-gray-700 dark:bg-gray-900"
        >
            <!-- Header -->
            <div
                class="mb-8 flex flex-col justify-between gap-4 border-b border-slate-100 pb-6 sm:flex-row sm:items-center dark:border-gray-800"
            >
                <div>
                    <h1
                        class="text-2xl font-bold text-slate-900 dark:text-gray-100"
                    >
                        Bulk Upload Images
                    </h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                        Upload multiple image resources simultaneously and
                        configure naming conventions.
                    </p>
                </div>
            </div>

            <form @submit.prevent="submitForm" class="space-y-6">
                <!-- Dropzone Section -->
                <div>
                    <label
                        class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300"
                    >
                        Select Images
                    </label>
                    <div
                        @dragover.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="handleDrop"
                        class="relative rounded-xl border-2 border-dashed p-8 text-center transition-all"
                        :class="[
                            isDragging
                                ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-500/10'
                                : 'border-slate-200 bg-slate-50/50 dark:border-gray-700 dark:bg-gray-800/50 dark:hover:bg-gray-800',
                            form.errors.files
                                ? 'border-rose-300 bg-rose-50/20 dark:border-rose-500/30 dark:bg-rose-500/10'
                                : '',
                        ]"
                    >
                        <input
                            type="file"
                            id="bulk-file-upload"
                            multiple
                            accept="image/*"
                            class="hidden"
                            @change="handleFileSelect"
                        />
                        <label
                            for="bulk-file-upload"
                            class="flex cursor-pointer flex-col items-center justify-center"
                        >
                            <div
                                class="mb-3 rounded-full border border-slate-100 bg-white p-3.5 text-blue-600 shadow-sm dark:border-gray-800 dark:bg-gray-900"
                            >
                                <Upload class="h-6 w-6" />
                            </div>
                            <span
                                class="text-sm font-semibold text-slate-700 dark:text-gray-300"
                            >
                                Click to upload or drag & drop images
                            </span>
                            <span
                                class="mt-1 text-xs text-slate-400 dark:text-gray-500"
                            >
                                PNG, JPG, WEBP, or GIF (Multiple selection
                                supported)
                            </span>
                        </label>
                    </div>
                    <p
                        v-if="form.errors.files"
                        class="mt-1.5 text-sm text-rose-600"
                    >
                        {{ form.errors.files }}
                    </p>
                </div>

                <!-- Naming Convention Options -->
                <div
                    v-if="selectedFiles.length > 0"
                    class="space-y-4 rounded-xl border border-slate-200 bg-slate-50/50 p-5 dark:border-gray-700 dark:bg-gray-800/50"
                >
                    <h3
                        class="text-sm font-semibold text-slate-800 dark:text-gray-200"
                    >
                        File Naming Settings
                    </h3>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <!-- Option 1: Original -->
                        <label
                            class="flex cursor-pointer items-center gap-3 rounded-lg border bg-white p-3 transition dark:bg-gray-900"
                            :class="
                                form.naming_strategy === 'original'
                                    ? 'border-blue-600 ring-2 ring-blue-600/10'
                                    : 'border-slate-200 dark:border-gray-700 dark:hover:border-gray-600'
                            "
                        >
                            <input
                                type="radio"
                                value="original"
                                v-model="form.naming_strategy"
                                class="text-blue-600 focus:ring-blue-500 dark:text-blue-400"
                            />
                            <div
                                class="flex items-center gap-2 text-xs font-medium text-slate-700 dark:text-gray-300"
                            >
                                <FileSpreadsheet
                                    class="h-4 w-4 text-slate-400 dark:text-gray-500"
                                />
                                Original Filenames
                            </div>
                        </label>

                        <!-- Option 2: Serial -->
                        <label
                            class="flex cursor-pointer items-center gap-3 rounded-lg border bg-white p-3 transition dark:bg-gray-900"
                            :class="
                                form.naming_strategy === 'serial'
                                    ? 'border-blue-600 ring-2 ring-blue-600/10'
                                    : 'border-slate-200 dark:border-gray-700 dark:hover:border-gray-600'
                            "
                        >
                            <input
                                type="radio"
                                value="serial"
                                v-model="form.naming_strategy"
                                class="text-blue-600 focus:ring-blue-500 dark:text-blue-400"
                            />
                            <div
                                class="flex items-center gap-2 text-xs font-medium text-slate-700 dark:text-gray-300"
                            >
                                <Hash
                                    class="h-4 w-4 text-slate-400 dark:text-gray-500"
                                />
                                Serial Numbers (01, 02)
                            </div>
                        </label>

                        <!-- Option 3: Suffix -->
                        <label
                            class="flex cursor-pointer items-center gap-3 rounded-lg border bg-white p-3 transition dark:bg-gray-900"
                            :class="
                                form.naming_strategy === 'suffix'
                                    ? 'border-blue-600 ring-2 ring-blue-600/10'
                                    : 'border-slate-200 dark:border-gray-700 dark:hover:border-gray-600'
                            "
                        >
                            <input
                                type="radio"
                                value="suffix"
                                v-model="form.naming_strategy"
                                class="text-blue-600 focus:ring-blue-500 dark:text-blue-400"
                            />
                            <div
                                class="flex items-center gap-2 text-xs font-medium text-slate-700 dark:text-gray-300"
                            >
                                <Tag
                                    class="h-4 w-4 text-slate-400 dark:text-gray-500"
                                />
                                Custom Prefix + Serial
                            </div>
                        </label>
                    </div>

                    <!-- Options for Serial/Suffix -->
                    <div
                        v-if="form.naming_strategy !== 'original'"
                        class="grid grid-cols-1 gap-4 pt-2 sm:grid-cols-2"
                    >
                        <div v-if="form.naming_strategy === 'suffix'">
                            <label
                                class="mb-1 block text-xs font-semibold text-slate-600 dark:text-gray-400"
                            >
                                Custom Name Prefix
                            </label>
                            <input
                                v-model="form.naming_suffix"
                                type="text"
                                placeholder="e.g. Lecture Slide"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-900"
                            />
                        </div>

                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold text-slate-600 dark:text-gray-400"
                            >
                                Starting Number
                            </label>
                            <input
                                v-model.number="form.start_number"
                                type="number"
                                min="1"
                                placeholder="1"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-900"
                            />
                        </div>
                    </div>
                </div>

                <!-- File Previews Grid -->
                <div v-if="selectedFiles.length > 0">
                    <div class="mb-3 flex items-center justify-between">
                        <label
                            class="text-sm font-semibold text-slate-700 dark:text-gray-300"
                        >
                            Selected Images ({{ selectedFiles.length }})
                        </label>
                        <button
                            type="button"
                            @click="clearAll"
                            class="flex items-center gap-1 text-xs font-medium text-rose-600 hover:text-rose-700"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                            Remove All
                        </button>
                    </div>

                    <div
                        class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4"
                    >
                        <div
                            v-for="(item, index) in selectedFiles"
                            :key="item.id"
                            class="group relative flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white p-2 shadow-xs transition hover:border-slate-300 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-gray-600"
                        >
                            <!-- Thumbnail -->
                            <div
                                class="relative aspect-video w-full overflow-hidden rounded-lg bg-slate-100 dark:bg-gray-800"
                            >
                                <img
                                    :src="item.previewUrl"
                                    :alt="item.file.name"
                                    class="h-full w-full object-cover"
                                />
                                <button
                                    type="button"
                                    @click="removeFile(index)"
                                    class="absolute top-1.5 right-1.5 flex h-6 w-6 items-center justify-center rounded-full bg-slate-900/60 text-white backdrop-blur-xs transition hover:bg-rose-600"
                                >
                                    <X class="h-3.5 w-3.5" />
                                </button>
                            </div>

                            <!-- Title Preview -->
                            <div class="mt-2.5 px-1">
                                <p
                                    class="truncate text-xs font-bold text-slate-800 dark:text-gray-200"
                                    :title="processedTitles[index]"
                                >
                                    {{ processedTitles[index] }}
                                </p>
                                <p
                                    class="mt-0.5 truncate text-[10px] text-slate-400 dark:text-gray-500"
                                >
                                    Original: {{ item.file.name }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div
                    class="flex justify-end space-x-3 border-t border-slate-100 pt-6 dark:border-gray-800"
                >
                    <Link
                        :href="redirect"
                        type="button"
                        class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="
                            form.processing || selectedFiles.length === 0
                        "
                        class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 focus:ring-4 focus:ring-blue-600/20 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{
                            form.processing
                                ? 'Uploading...'
                                : `Upload ${selectedFiles.length} Images`
                        }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
