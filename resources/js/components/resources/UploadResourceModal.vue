<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    X,
    FileText,
    FlaskConical,
    Youtube,
    FolderPlus,
    UploadCloud,
    Trash2,
    Link as LinkIcon,
    AlertCircle,
    Loader2,
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { compressImage } from '@/lib/imageCompression';

interface SelectedImage {
    id: string;
    file: File;
    previewUrl: string;
    title: string;
}

const props = defineProps<{
    isOpen: boolean;
    node: {
        id: number;
        name: string;
    };
    defaultType?: 'note' | 'practical' | 'video' | 'other';
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

type ResourceType = 'note' | 'practical' | 'video' | 'other';
type DocumentSourceType = 'images' | 'gdrive';

const resourceType = ref<ResourceType>(props.defaultType || 'note');
const docSourceType = ref<DocumentSourceType>('images');

const title = ref('');
const description = ref('');
const gdriveUrl = ref('');
const playlistUrl = ref('');

const selectedImages = ref<SelectedImage[]>([]);
const isSubmitting = ref(false);
const uploadProgress = ref<string | null>(null);
const errorMessage = ref<string | null>(null);

const fileInputRef = ref<HTMLInputElement | null>(null);

const resetForm = () => {
    title.value = '';
    description.value = '';
    gdriveUrl.value = '';
    playlistUrl.value = '';
    errorMessage.value = null;
    uploadProgress.value = null;
    isSubmitting.value = false;

    selectedImages.value.forEach((img) => URL.revokeObjectURL(img.previewUrl));
    selectedImages.value = [];
};

const handleClose = () => {
    if (isSubmitting.value) {
        return;
    }

    resetForm();
    emit('close');
};

const triggerFileInput = () => {
    fileInputRef.value?.click();
};

const handleFilesSelected = (event: Event) => {
    const input = event.target as HTMLInputElement;

    if (!input.files || input.files.length === 0) {
        return;
    }

    errorMessage.value = null;
    const files = Array.from(input.files);

    for (const file of files) {
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            errorMessage.value = 'শুধুমাত্র JPG, PNG বা WEBP ছবি গ্রহণযোগ্য।';
            continue;
        }

        if (file.size > 20 * 1024 * 1024) {
            errorMessage.value =
                'প্রতিটি ছবির সর্বোচ্চ সাইজ ২০ মেগাবাইট হতে পারবে।';
            continue;
        }

        const previewUrl = URL.createObjectURL(file);
        selectedImages.value.push({
            id: Math.random().toString(36).substring(2, 9),
            file,
            previewUrl,
            title: '',
        });
    }

    input.value = '';
};

const removeImage = (index: number) => {
    const removed = selectedImages.value.splice(index, 1)[0];

    if (removed) {
        URL.revokeObjectURL(removed.previewUrl);
    }
};

const isFormValid = computed(() => {
    if (!title.value.trim()) {
        return false;
    }

    if (resourceType.value === 'video') {
        return Boolean(playlistUrl.value.trim());
    }

    if (docSourceType.value === 'gdrive') {
        return Boolean(gdriveUrl.value.trim());
    }

    return selectedImages.value.length > 0;
});

const submitResource = async () => {
    if (!isFormValid.value || isSubmitting.value) {
        return;
    }

    isSubmitting.value = true;
    errorMessage.value = null;

    try {
        if (resourceType.value === 'video') {
            // Video Playlist Submit
            uploadProgress.value = 'প্লেলিস্ট প্রসেস করা হচ্ছে...';

            router.post(
                '/resources/videos',
                {
                    node_id: props.node.id,
                    title: title.value.trim(),
                    description: description.value.trim() || null,
                    playlist_url: playlistUrl.value.trim(),
                },
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        handleClose();
                    },
                    onError: (errors) => {
                        errorMessage.value =
                            Object.values(errors).flat().join(', ') ||
                            'ভিডিও প্লেলিস্ট সাবমিট করতে সমস্যা হয়েছে।';
                    },
                    onFinish: () => {
                        isSubmitting.value = false;
                        uploadProgress.value = null;
                    },
                },
            );

            return;
        }

        // Document Submit: Google Drive Link
        if (docSourceType.value === 'gdrive') {
            uploadProgress.value = 'সংরক্ষণ করা হচ্ছে...';

            router.post(
                '/resources/documents',
                {
                    node_id: props.node.id,
                    resource_type: resourceType.value,
                    title: title.value.trim(),
                    description: description.value.trim() || null,
                    items: [
                        {
                            format: 'pdf',
                            source: gdriveUrl.value.trim(),
                            title: title.value.trim(),
                        },
                    ],
                },
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        handleClose();
                    },
                    onError: (errors) => {
                        errorMessage.value =
                            Object.values(errors).flat().join(', ') ||
                            'ডকুমেন্ট সাবমিট করতে সমস্যা হয়েছে।';
                    },
                    onFinish: () => {
                        isSubmitting.value = false;
                        uploadProgress.value = null;
                    },
                },
            );

            return;
        }

        // Document Submit: Images via Cloudflare R2 Pre-signed URLs
        const total = selectedImages.value.length;
        const itemsPayload: Array<{
            format: string;
            source: string;
            title: string | null;
        }> = [];

        for (let i = 0; i < total; i++) {
            const item = selectedImages.value[i];
            uploadProgress.value = `ছবি কম্প্রেস ও আপলোড হচ্ছে (${i + 1}/${total})...`;

            // 1. Client-side WebP compression
            const compressedFile = await compressImage(item.file, {
                maxWidth: 2048,
                maxHeight: 2048,
                quality: 0.85,
                targetFormat: 'image/webp',
            });

            // 2. Request pre-signed URL from Laravel
            const presignRes = await fetch('/resources/upload-url', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN':
                        (
                            document.querySelector(
                                'meta[name="csrf-token"]',
                            ) as HTMLMetaElement
                        )?.content || '',
                },
                body: JSON.stringify({
                    content_type: 'image/webp',
                    size: compressedFile.size,
                }),
            });

            if (!presignRes.ok) {
                const errData = await presignRes.json().catch(() => ({}));

                throw new Error(
                    errData.message ||
                        errData.errors?.pending_limit?.[0] ||
                        'আপলোড ইউআরএল তৈরি করতে ব্যর্থ হয়েছে।',
                );
            }

            const { key, upload_url } = await presignRes.json();

            // 3. Direct HTTP PUT to Cloudflare R2
            const putRes = await fetch(upload_url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'image/webp',
                },
                body: compressedFile,
            });

            if (!putRes.ok) {
                throw new Error(`ক্লাউডে ছবি #${i + 1} আপলোড ব্যর্থ হয়েছে।`);
            }

            itemsPayload.push({
                format: 'image',
                source: key,
                title: item.title.trim() || null,
            });
        }

        // 4. Save the resource with child items
        uploadProgress.value = 'রিসোর্স সংরক্ষণ করা হচ্ছে...';

        router.post(
            '/resources/documents',
            {
                node_id: props.node.id,
                resource_type: resourceType.value,
                title: title.value.trim(),
                description: description.value.trim() || null,
                items: itemsPayload,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    handleClose();
                },
                onError: (errors) => {
                    errorMessage.value =
                        Object.values(errors).flat().join(', ') ||
                        'রিসোর্স সংরক্ষণ করতে সমস্যা হয়েছে।';
                },
                onFinish: () => {
                    isSubmitting.value = false;
                    uploadProgress.value = null;
                },
            },
        );
    } catch (err: any) {
        errorMessage.value = err.message || 'একটি অপ্রত্যাশিত সমস্যা হয়েছে।';
        isSubmitting.value = false;
        uploadProgress.value = null;
    }
};
</script>

<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
    >
        <!-- Backdrop -->
        <div
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
            @click="handleClose"
        />

        <!-- Modal Box -->
        <div
            class="relative flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:border dark:border-gray-800 dark:bg-gray-900"
        >
            <!-- Header -->
            <div
                class="flex items-center justify-between border-b border-slate-100 px-6 py-4 dark:border-gray-800"
            >
                <div>
                    <h2
                        class="text-lg font-bold text-slate-800 dark:text-gray-100"
                    >
                        রিসোর্স আপলোড করুন
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-gray-400">
                        ফোল্ডার:
                        <span
                            class="font-medium text-slate-700 dark:text-gray-300"
                            >{{ node.name }}</span
                        >
                    </p>
                </div>
                <button
                    type="button"
                    class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                    @click="handleClose"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- Body -->
            <div class="space-y-5 overflow-y-auto px-6 py-5">
                <!-- Error Alert -->
                <div
                    v-if="errorMessage"
                    class="flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 p-3.5 text-xs text-red-700 dark:border-red-900/40 dark:bg-red-950/40 dark:text-red-400"
                >
                    <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
                    <span>{{ errorMessage }}</span>
                </div>

                <!-- Resource Type Selector Tabs -->
                <div>
                    <label
                        class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                    >
                        রিসোর্সের ধরন
                    </label>
                    <div class="grid grid-cols-4 gap-2">
                        <button
                            type="button"
                            :class="[
                                'flex flex-col items-center gap-1.5 rounded-xl border p-2.5 text-xs font-semibold transition',
                                resourceType === 'note'
                                    ? 'border-emerald-500 bg-emerald-50/60 text-emerald-700 dark:border-emerald-500 dark:bg-emerald-950/30 dark:text-emerald-400'
                                    : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800/50',
                            ]"
                            @click="resourceType = 'note'"
                        >
                            <FileText class="h-4 w-4" />
                            <span>নোট</span>
                        </button>
                        <button
                            type="button"
                            :class="[
                                'flex flex-col items-center gap-1.5 rounded-xl border p-2.5 text-xs font-semibold transition',
                                resourceType === 'practical'
                                    ? 'border-purple-500 bg-purple-50/60 text-purple-700 dark:border-purple-500 dark:bg-purple-950/30 dark:text-purple-400'
                                    : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800/50',
                            ]"
                            @click="resourceType = 'practical'"
                        >
                            <FlaskConical class="h-4 w-4" />
                            <span>ব্যবহারিক</span>
                        </button>
                        <button
                            type="button"
                            :class="[
                                'flex flex-col items-center gap-1.5 rounded-xl border p-2.5 text-xs font-semibold transition',
                                resourceType === 'video'
                                    ? 'border-rose-500 bg-rose-50/60 text-rose-700 dark:border-rose-500 dark:bg-rose-950/30 dark:text-rose-400'
                                    : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800/50',
                            ]"
                            @click="resourceType = 'video'"
                        >
                            <Youtube class="h-4 w-4" />
                            <span>ভিডিও</span>
                        </button>
                        <button
                            type="button"
                            :class="[
                                'flex flex-col items-center gap-1.5 rounded-xl border p-2.5 text-xs font-semibold transition',
                                resourceType === 'other'
                                    ? 'border-blue-500 bg-blue-50/60 text-blue-700 dark:border-blue-500 dark:bg-blue-950/30 dark:text-blue-400'
                                    : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800/50',
                            ]"
                            @click="resourceType = 'other'"
                        >
                            <FolderPlus class="h-4 w-4" />
                            <span>অন্যান্য</span>
                        </button>
                    </div>
                </div>

                <!-- Title & Description -->
                <div class="space-y-3">
                    <div>
                        <label
                            class="mb-1 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                        >
                            শিরোনাম <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="title"
                            type="text"
                            placeholder="যেমন: তাপগতিবিদ্যা লেকচার ১"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-200 dark:focus:border-emerald-500"
                        />
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                        >
                            বিবরণ
                            <span class="font-normal text-slate-400"
                                >(ঐচ্ছিক)</span
                            >
                        </label>
                        <textarea
                            v-model="description"
                            rows="2"
                            placeholder="রিসোর্স সম্পর্কে কিছু তথ্য..."
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-200 dark:focus:border-emerald-500"
                        />
                    </div>
                </div>

                <!-- VIDEO PLAYLIST INPUT -->
                <div v-if="resourceType === 'video'" class="space-y-2">
                    <label
                        class="block text-xs font-semibold text-slate-700 dark:text-gray-300"
                    >
                        ইউটিউব প্লেলিস্ট লিংক
                        <span class="text-rose-500">*</span>
                    </label>
                    <input
                        v-model="playlistUrl"
                        type="url"
                        placeholder="https://www.youtube.com/playlist?list=PL..."
                        class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-rose-500 focus:bg-white focus:outline-hidden dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-200 dark:focus:border-rose-500"
                    />
                    <p class="text-[11px] text-slate-500 dark:text-gray-400">
                        প্লেলিস্টটি পাবলিক বা আনলিস্টেড হতে হবে। সবগুলো ভিডিও
                        পর্ব স্বয়ংক্রিয়ভাবে সিরিয়ালি সাজিয়ে নেওয়া হবে।
                    </p>
                </div>

                <!-- DOCUMENTS (NOTE / PRACTICAL / OTHER) -->
                <div v-else class="space-y-4">
                    <!-- Source Toggle: Images vs Google Drive -->
                    <div
                        class="flex rounded-xl bg-slate-100 p-1 dark:bg-gray-800"
                    >
                        <button
                            type="button"
                            :class="[
                                'flex-1 rounded-lg py-1.5 text-xs font-semibold transition',
                                docSourceType === 'images'
                                    ? 'bg-white text-slate-800 shadow-xs dark:bg-gray-900 dark:text-gray-200'
                                    : 'text-slate-600 hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200',
                            ]"
                            @click="docSourceType = 'images'"
                        >
                            ছবি আপলোড (ফটো খাতা)
                        </button>
                        <button
                            type="button"
                            :class="[
                                'flex-1 rounded-lg py-1.5 text-xs font-semibold transition',
                                docSourceType === 'gdrive'
                                    ? 'bg-white text-slate-800 shadow-xs dark:bg-gray-900 dark:text-gray-200'
                                    : 'text-slate-600 hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200',
                            ]"
                            @click="docSourceType = 'gdrive'"
                        >
                            গুগল ড্রাইভ লিংক (PDF)
                        </button>
                    </div>

                    <!-- GOOGLE DRIVE INPUT -->
                    <div v-if="docSourceType === 'gdrive'" class="space-y-2">
                        <label
                            class="block text-xs font-semibold text-slate-700 dark:text-gray-300"
                        >
                            গুগল ড্রাইভ PDF লিংক
                            <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input
                                v-model="gdriveUrl"
                                type="url"
                                placeholder="https://drive.google.com/file/d/.../view"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 pr-3.5 pl-9 text-sm text-slate-800 placeholder-slate-400 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-200 dark:focus:border-emerald-500"
                            />
                            <LinkIcon
                                class="absolute top-3 left-3 h-4 w-4 text-slate-400"
                            />
                        </div>
                        <p
                            class="text-[11px] text-slate-500 dark:text-gray-400"
                        >
                            ড্রাইভের লিংক শেয়ারিং অপশন
                            <strong>"Anyone with the link can view"</strong> করা
                            আছে কি না যাচাই করে নিন।
                        </p>
                    </div>

                    <!-- IMAGE UPLOADER -->
                    <div v-else class="space-y-3">
                        <input
                            ref="fileInputRef"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            multiple
                            class="hidden"
                            @change="handleFilesSelected"
                        />

                        <!-- Drag & Drop Zone -->
                        <div
                            class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/50 p-6 text-center transition hover:border-emerald-500 hover:bg-emerald-50/20 dark:border-gray-800 dark:bg-gray-800/30 dark:hover:border-emerald-500"
                            @click="triggerFileInput"
                        >
                            <div
                                class="mb-2 rounded-xl bg-white p-3 shadow-xs dark:bg-gray-800"
                            >
                                <UploadCloud
                                    class="h-6 w-6 text-emerald-600 dark:text-emerald-400"
                                />
                            </div>
                            <p
                                class="text-xs font-semibold text-slate-700 dark:text-gray-300"
                            >
                                নোটের পাতার ছবি নির্বাচন করুন বা টেনে এনে ছাড়ুন
                            </p>
                            <p class="mt-1 text-[11px] text-slate-400">
                                JPG, PNG, WEBP (স্বয়ংক্রিয়ভাবে অপ্টিমাইজ করা
                                হবে)
                            </p>
                        </div>

                        <!-- Selected Images Grid -->
                        <div v-if="selectedImages.length > 0" class="space-y-2">
                            <div
                                class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-gray-400"
                            >
                                <span
                                    >নির্বাচিত ছবি ({{
                                        selectedImages.length
                                    }}টি পাতা)</span
                                >
                                <button
                                    type="button"
                                    class="text-emerald-600 hover:underline dark:text-emerald-400"
                                    @click="triggerFileInput"
                                >
                                    + আরও যোগ করুন
                                </button>
                            </div>

                            <div
                                class="grid max-h-56 grid-cols-2 gap-2.5 overflow-y-auto p-1 sm:grid-cols-3"
                            >
                                <div
                                    v-for="(img, idx) in selectedImages"
                                    :key="img.id"
                                    class="group relative rounded-xl border border-slate-200 bg-white p-1.5 shadow-2xs dark:border-gray-800 dark:bg-gray-800"
                                >
                                    <div
                                        class="aspect-4/3 w-full overflow-hidden rounded-lg bg-slate-100 dark:bg-gray-900"
                                    >
                                        <img
                                            :src="img.previewUrl"
                                            alt="Page preview"
                                            class="h-full w-full object-cover"
                                        />
                                    </div>
                                    <div
                                        class="mt-1 flex items-center justify-between px-1"
                                    >
                                        <span
                                            class="text-[10px] font-bold text-slate-400"
                                            >পাতা #{{ idx + 1 }}</span
                                        >
                                        <button
                                            type="button"
                                            class="rounded p-1 text-slate-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/40 dark:hover:text-red-400"
                                            @click.stop="removeImage(idx)"
                                        >
                                            <Trash2 class="h-3 w-3" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div
                class="flex items-center justify-between border-t border-slate-100 bg-slate-50/50 px-6 py-4 dark:border-gray-800 dark:bg-gray-800/40"
            >
                <div
                    class="text-xs font-medium text-slate-500 dark:text-gray-400"
                >
                    <span
                        v-if="uploadProgress"
                        class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400"
                    >
                        <Loader2 class="h-3.5 w-3.5 animate-spin" />
                        {{ uploadProgress }}
                    </span>
                </div>

                <div class="flex items-center gap-2.5">
                    <button
                        type="button"
                        :disabled="isSubmitting"
                        class="dark:hover:bg-gray-750 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                        @click="handleClose"
                    >
                        বাতিল
                    </button>
                    <button
                        type="button"
                        :disabled="!isFormValid || isSubmitting"
                        class="flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-emerald-700 disabled:opacity-50"
                        @click="submitResource"
                    >
                        <Loader2
                            v-if="isSubmitting"
                            class="h-3.5 w-3.5 animate-spin"
                        />
                        <span>{{
                            isSubmitting ? 'আপলোড হচ্ছে...' : 'জমা দিন'
                        }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
