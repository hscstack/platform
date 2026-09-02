<script setup lang="ts">
import { Upload, X, Loader2, Sparkles } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import { useImageUpload } from '@/lib/useImageUpload';

const props = withDefaults(
    defineProps<{
        modelValue?: File | null;
        currentImageUrl?: string | null;
        label?: string;
        helpText?: string;
        shape?: 'rectangle' | 'square' | 'circle';
        maxOriginalSizeMB?: number;
        disabled?: boolean;
        showSizeReduction?: boolean;
    }>(),
    {
        modelValue: null,
        currentImageUrl: null,
        label: '',
        helpText: 'JPG, PNG, WEBP (Auto-optimized)',
        shape: 'rectangle',
        maxOriginalSizeMB: 20,
        disabled: false,
        showSizeReduction: true,
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', file: File | null): void;
    (e: 'change', file: File | null): void;
    (e: 'clear'): void;
}>();

const fileInputRef = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);

const {
    file,
    previewUrl,
    isCompressing,
    error,
    originalSizeFormatted,
    compressedSizeFormatted,
    processFile,
    clear: clearUploadState,
} = useImageUpload({
    maxOriginalSizeMB: props.maxOriginalSizeMB,
    onCompressed: (compressed) => {
        emit('update:modelValue', compressed);
        emit('change', compressed);
    },
});

const activePreview = computed(() => {
    return previewUrl.value || props.currentImageUrl || null;
});

const triggerPicker = () => {
    if (props.disabled || isCompressing.value) {
        return;
    }

    fileInputRef.value?.click();
};

const handleFileInput = async (event: Event) => {
    const target = event.target as HTMLInputElement;

    if (target.files && target.files[0]) {
        await processFile(target.files[0]);
        target.value = '';
    }
};

const handleDrop = async (event: DragEvent) => {
    isDragging.value = false;

    if (props.disabled || isCompressing.value) {
        return;
    }

    if (event.dataTransfer?.files && event.dataTransfer.files[0]) {
        await processFile(event.dataTransfer.files[0]);
    }
};

const removeImage = (event: Event) => {
    event.stopPropagation();
    clearUploadState();
    emit('update:modelValue', null);
    emit('change', null);
    emit('clear');
};

watch(
    () => props.modelValue,
    (val) => {
        if (!val && file.value) {
            clearUploadState();
        }
    },
);
</script>

<template>
    <div class="w-full">
        <!-- Optional Label -->
        <label
            v-if="label"
            class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-gray-300"
        >
            {{ label }}
        </label>

        <!-- Hidden File Input -->
        <input
            ref="fileInputRef"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            class="hidden"
            :disabled="disabled || isCompressing"
            @change="handleFileInput"
        />

        <!-- Circle Shape (for Avatars) -->
        <div
            v-if="shape === 'circle'"
            class="relative inline-block"
            @click="triggerPicker"
        >
            <div
                class="group relative flex h-28 w-28 cursor-pointer items-center justify-center overflow-hidden rounded-full border-2 border-dashed border-slate-300 bg-slate-50 transition-all hover:border-indigo-500 dark:border-gray-700 dark:bg-gray-800"
                :class="{
                    'border-indigo-500 ring-2 ring-indigo-500/20': isDragging,
                    'cursor-not-allowed opacity-60': disabled || isCompressing,
                }"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="handleDrop"
            >
                <img
                    v-if="activePreview && !isCompressing"
                    :src="activePreview"
                    alt="Preview"
                    class="h-full w-full object-cover"
                />

                <div
                    v-else-if="!isCompressing"
                    class="flex flex-col items-center justify-center p-2 text-center text-slate-400 dark:text-gray-500"
                >
                    <Upload
                        class="h-6 w-6 transition-transform group-hover:scale-110"
                    />
                    <span class="mt-1 text-xs font-medium">Upload</span>
                </div>

                <!-- Compressing Overlay -->
                <div
                    v-if="isCompressing"
                    class="absolute inset-0 flex flex-col items-center justify-center bg-white/80 dark:bg-gray-900/80"
                >
                    <Loader2
                        class="h-6 w-6 animate-spin text-indigo-600 dark:text-indigo-400"
                    />
                </div>
            </div>

            <!-- Remove Button -->
            <button
                v-if="activePreview && !isCompressing && !disabled"
                type="button"
                title="Remove image"
                class="absolute top-0 right-0 flex h-6 w-6 items-center justify-center rounded-full bg-red-600 text-white shadow hover:bg-red-700"
                @click="removeImage"
            >
                <X class="h-3.5 w-3.5" />
            </button>
        </div>

        <!-- Box / Rectangle Shape -->
        <div
            v-else
            class="group relative flex cursor-pointer flex-col items-center justify-center overflow-hidden rounded-xl border-2 border-dashed transition-all"
            :class="[
                shape === 'square'
                    ? 'aspect-square max-w-xs'
                    : 'min-h-[160px] w-full',
                isDragging
                    ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/20'
                    : 'border-slate-300 bg-slate-50/70 hover:border-indigo-400 hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800/50 dark:hover:border-gray-600 dark:hover:bg-gray-800',
                activePreview ? 'p-2' : 'p-6',
                disabled || isCompressing
                    ? 'pointer-events-none opacity-70'
                    : '',
            ]"
            @click="triggerPicker"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop"
        >
            <!-- Preview with Image Loaded -->
            <div
                v-if="activePreview && !isCompressing"
                class="relative flex w-full items-center justify-center overflow-hidden rounded-lg"
            >
                <img
                    :src="activePreview"
                    alt="Preview"
                    class="max-h-64 w-auto rounded-lg object-contain"
                />

                <!-- Overlay on hover -->
                <div
                    class="absolute inset-0 flex items-center justify-center bg-slate-900/40 opacity-0 transition-opacity group-hover:opacity-100"
                >
                    <span
                        class="rounded-lg bg-white/90 px-3 py-1.5 text-xs font-semibold text-slate-800 shadow dark:bg-gray-800 dark:text-gray-200"
                    >
                        Click or drop to replace
                    </span>
                </div>

                <!-- Remove Button -->
                <button
                    type="button"
                    title="Remove image"
                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded-full bg-slate-900/75 text-white backdrop-blur transition-all hover:bg-red-600"
                    @click="removeImage"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <!-- Empty Dropzone State -->
            <div
                v-else-if="!isCompressing"
                class="flex flex-col items-center justify-center text-center"
            >
                <div
                    class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-indigo-50 text-indigo-600 transition-transform group-hover:scale-110 dark:bg-indigo-950/50 dark:text-indigo-400"
                >
                    <Upload class="h-6 w-6" />
                </div>
                <p
                    class="text-sm font-medium text-slate-700 dark:text-gray-300"
                >
                    <span
                        class="text-indigo-600 hover:underline dark:text-indigo-400"
                        >Click to upload</span
                    >
                    or drag and drop
                </p>
                <p
                    v-if="helpText"
                    class="mt-1 text-xs text-slate-500 dark:text-gray-400"
                >
                    {{ helpText }}
                </p>
            </div>

            <!-- Compressing State -->
            <div
                v-if="isCompressing"
                class="flex flex-col items-center justify-center py-6 text-center"
            >
                <Loader2
                    class="h-8 w-8 animate-spin text-indigo-600 dark:text-indigo-400"
                />
                <p
                    class="mt-2.5 text-sm font-medium text-slate-700 dark:text-gray-300"
                >
                    Optimizing image...
                </p>
                <p class="text-xs text-slate-500 dark:text-gray-400">
                    Scaling & compressing to WebP for faster loading
                </p>
            </div>
        </div>

        <!-- Size Reduction Badge -->
        <div
            v-if="
                showSizeReduction &&
                originalSizeFormatted &&
                compressedSizeFormatted &&
                !isCompressing
            "
            class="mt-2 flex items-center gap-1.5 text-xs text-emerald-600 dark:text-emerald-400"
        >
            <Sparkles class="h-3.5 w-3.5" />
            <span>
                Optimized:
                <span class="text-slate-400 line-through">{{
                    originalSizeFormatted
                }}</span>
                &rarr;
                <span class="font-semibold">{{ compressedSizeFormatted }}</span>
            </span>
        </div>

        <!-- Error Message -->
        <p
            v-if="error"
            class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400"
        >
            {{ error }}
        </p>
    </div>
</template>
