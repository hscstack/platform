<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Pencil,
    Trash2,
    File,
    Check,
    Plus,
    UploadCloud,
    Link as LinkIcon,
    AlertCircle,
    Youtube,
    GripVertical,
    RotateCcw,
    Loader2,
    ChevronDown,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import BaseModal from '@/components/BaseModal.vue';
import ImageViewerModal from '@/components/ImageViewerModal.vue';
import { compressImage } from '@/lib/imageCompression';

interface ResourceItem {
    id: number;
    resource_id?: number;
    format: 'image' | 'video' | 'pdf' | string;
    title?: string | null;
    source: string;
    url?: string;
    is_new?: boolean;
    is_deleted?: boolean;
    file?: File;
}

interface ResourceData {
    id: number;
    node_id: number;
    user_id?: number | null;
    resource_type: string;
    title: string;
    description?: string | null;
    status?: 'pending' | 'approved' | 'rejected';
    admin_note?: string | null;
    items?: ResourceItem[];
}

const props = withDefaults(
    defineProps<{
        isOpen: boolean;
        resource?: ResourceData | null;
        node?: { id: number; name: string } | null;
        defaultType?: 'note' | 'video' | 'practical' | 'marked_book' | 'other';
    }>(),
    {
        resource: null,
        node: null,
        defaultType: 'note',
    },
);

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'update:isOpen', value: boolean): void;
    (e: 'save', payload: any): void;
}>();

const isEditing = computed(() => Boolean(props.resource?.id));

const isSubmitting = ref(false);
const uploadProgress = ref<string | null>(null);
const errorMessage = ref<string | null>(null);
const itemErrorMessage = ref<string | null>(null);

// Form state
const form = ref({
    title: '',
    description: '',
    resource_type: 'note' as
        | 'note'
        | 'video'
        | 'practical'
        | 'marked_book'
        | 'other',
    items: [] as ResourceItem[],
});

// Content inputs
const youtubeInput = ref('');
const isImportingYoutube = ref(false);
const docInputMode = ref<'images' | 'gdrive'>('images');
const gdriveInputUrl = ref('');
const fileInputRef = ref<HTMLInputElement | null>(null);

// Track created object URLs to revoke them on cleanup
const createdObjectUrls = ref<string[]>([]);

const cleanupObjectUrls = () => {
    createdObjectUrls.value.forEach((url) => {
        try {
            URL.revokeObjectURL(url);
        } catch {
            // ignore
        }
    });
    createdObjectUrls.value = [];
};

// Active items & format lock
const activeItems = computed(() => {
    return form.value.items.filter((item) => !item.is_deleted);
});

const lockedFormat = computed<'image' | 'video' | 'pdf' | null>(() => {
    const active = activeItems.value;

    if (active.length === 0) {
        return null;
    }

    return (active[0].format as 'image' | 'video' | 'pdf') || null;
});

watch(lockedFormat, (fmt) => {
    if (fmt === 'image') {
        docInputMode.value = 'images';
    } else if (fmt === 'pdf') {
        docInputMode.value = 'gdrive';
    }
});

// YouTube thumbnail helper
const getYouTubeThumbnail = (url?: string | null): string | null => {
    if (!url) {
        return null;
    }

    const match = url.match(
        /(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=|shorts\/))([\w-]{11})/,
    );

    return match?.[1]
        ? `https://img.youtube.com/vi/${match[1]}/hqdefault.jpg`
        : null;
};

// Drag and drop reordering (Desktop)
const draggedIndex = ref<number | null>(null);
const dragOverIndex = ref<number | null>(null);

const handleDragStart = (e: DragEvent, index: number) => {
    draggedIndex.value = index;

    if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', index.toString());
    }
};

const handleDragOver = (e: DragEvent, index: number) => {
    e.preventDefault();

    if (e.dataTransfer) {
        e.dataTransfer.dropEffect = 'move';
    }

    if (index === 0) {
        const rect = (e.currentTarget as HTMLElement).getBoundingClientRect();

        if (
            e.clientX < rect.left + rect.width / 3 ||
            e.clientY < rect.top + rect.height / 3
        ) {
            dragOverIndex.value = -1;

            return;
        }
    }

    dragOverIndex.value = index;
};

const handleDrop = (e: DragEvent, targetIndex: number) => {
    e.preventDefault();

    if (draggedIndex.value === null) {
        draggedIndex.value = null;
        dragOverIndex.value = null;

        return;
    }

    const fromIdx = draggedIndex.value;
    const target = dragOverIndex.value ?? targetIndex;

    if (target === -1) {
        const items = [...form.value.items];
        const [movedItem] = items.splice(fromIdx, 1);
        items.unshift(movedItem);
        form.value.items = items;
    } else if (fromIdx !== target) {
        const items = [...form.value.items];
        const [movedItem] = items.splice(fromIdx, 1);
        const insertIdx = fromIdx < target ? target : target + 1;
        items.splice(insertIdx, 0, movedItem);
        form.value.items = items;
    }

    draggedIndex.value = null;
    dragOverIndex.value = null;
};

const handleDragEnd = () => {
    draggedIndex.value = null;
    dragOverIndex.value = null;
};

// Touch drag-and-drop for mobile devices
let touchStartIndex = -1;

const triggerHaptic = (ms = 10) => {
    if (typeof navigator !== 'undefined' && 'vibrate' in navigator) {
        try {
            navigator.vibrate(ms);
        } catch {
            // ignore
        }
    }
};

const handleTouchStart = (_e: TouchEvent, index: number) => {
    touchStartIndex = index;
    draggedIndex.value = index;
    triggerHaptic(12);
};

const handleTouchMove = (e: TouchEvent) => {
    if (touchStartIndex === -1) {
        return;
    }

    const touch = e.touches[0];
    const el = document.elementFromPoint(touch.clientX, touch.clientY);
    const itemCard = el?.closest('[data-item-index]') as HTMLElement | null;

    if (itemCard && itemCard.dataset.itemIndex !== undefined) {
        const overIdx = parseInt(itemCard.dataset.itemIndex, 10);

        if (!isNaN(overIdx) && overIdx !== touchStartIndex) {
            const items = [...form.value.items];
            const [movedItem] = items.splice(touchStartIndex, 1);
            items.splice(overIdx, 0, movedItem);
            form.value.items = items;
            touchStartIndex = overIdx;
            draggedIndex.value = overIdx;
            triggerHaptic(8);
        }
    }
};

const handleTouchEnd = () => {
    touchStartIndex = -1;
    draggedIndex.value = null;
    dragOverIndex.value = null;
};

// Lightbox Image Viewer Modal State
const isImageViewerOpen = ref(false);
const activePreviewIndex = ref(0);

const imageItems = computed(() => {
    return form.value.items.filter(
        (item) => item.format === 'image' && !item.is_deleted,
    );
});

const currentPreviewImage = computed(() => {
    return imageItems.value[activePreviewIndex.value] || null;
});

const openImageViewer = (item: ResourceItem) => {
    const idx = imageItems.value.findIndex(
        (i) => (i.id && i.id === item.id) || (i.url && i.url === item.url),
    );
    activePreviewIndex.value = idx !== -1 ? idx : 0;
    isImageViewerOpen.value = true;
};

const goToPrevImage = () => {
    if (activePreviewIndex.value > 0) {
        activePreviewIndex.value--;
    }
};

const goToNextImage = () => {
    if (activePreviewIndex.value < imageItems.value.length - 1) {
        activePreviewIndex.value++;
    }
};

const initForm = () => {
    cleanupObjectUrls();

    if (props.resource) {
        form.value = {
            title: props.resource.title || '',
            description: props.resource.description || '',
            resource_type: (props.resource.resource_type || 'note') as any,
            items: props.resource.items
                ? JSON.parse(JSON.stringify(props.resource.items))
                : [],
        };
    } else {
        form.value = {
            title: '',
            description: '',
            resource_type: props.defaultType || 'note',
            items: [],
        };
    }

    youtubeInput.value = '';
    isImportingYoutube.value = false;
    docInputMode.value = 'images';
    gdriveInputUrl.value = '';
    errorMessage.value = null;
    itemErrorMessage.value = null;
    draggedIndex.value = null;
    dragOverIndex.value = null;
    isImageViewerOpen.value = false;
};

watch(
    () => props.isOpen,
    (open) => {
        if (open) {
            initForm();
        } else {
            cleanupObjectUrls();
        }
    },
    { immediate: true },
);

const handleClose = (force = false) => {
    if (!force && isSubmitting.value) {
        return;
    }

    cleanupObjectUrls();
    initForm();
    emit('close');
    emit('update:isOpen', false);
};

const toggleDeleteItem = (index: number) => {
    const item = form.value.items[index];

    if (!item) {
        return;
    }

    if (item.is_new || !isEditing.value) {
        if (item.file && item.url) {
            try {
                URL.revokeObjectURL(item.url);
            } catch {
                // ignore
            }

            createdObjectUrls.value = createdObjectUrls.value.filter(
                (u) => u !== item.url,
            );
        }

        form.value.items.splice(index, 1);

        return;
    }

    item.is_deleted = !item.is_deleted;
};

const handleClearAllItems = () => {
    if (form.value.items.length === 0) {
        return;
    }

    if (!confirm('আপনি কি নিশ্চিত যে সবগুলো আইটেম মুছে ফেলতে চান?')) {
        return;
    }

    cleanupObjectUrls();

    if (!isEditing.value) {
        form.value.items = [];
    } else {
        form.value.items = form.value.items
            .filter((item) => !item.is_new)
            .map((item) => ({ ...item, is_deleted: true }));
    }
};

// Add New Files Logic
const triggerFileInput = () => {
    fileInputRef.value?.click();
};

const handleNewFilesSelected = (e: Event) => {
    const input = e.target as HTMLInputElement;

    if (!input.files || input.files.length === 0) {
        return;
    }

    itemErrorMessage.value = null;
    const files = Array.from(input.files);

    const currentImageCount = activeItems.value.filter(
        (i) => i.format === 'image',
    ).length;

    if (currentImageCount + files.length > 50) {
        itemErrorMessage.value = `একটি রিসোর্সে সর্বোচ্চ ৫০টি ছবি যুক্ত করা যাবে (বর্তমানে আছে ${currentImageCount}টি)।`;
        input.value = '';

        return;
    }

    for (const file of files) {
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            itemErrorMessage.value =
                'শুধুমাত্র JPG, PNG বা WEBP ছবি গ্রহণযোগ্য।';
            continue;
        }

        if (file.size > 20 * 1024 * 1024) {
            itemErrorMessage.value = 'প্রতিটি ছবির সর্বোচ্চ সাইজ ২০ মেগাবাইট।';
            continue;
        }

        const previewUrl = URL.createObjectURL(file);
        createdObjectUrls.value.push(previewUrl);

        form.value.items.push({
            id: 0,
            resource_id: props.resource?.id,
            format: 'image',
            title: null,
            source: '',
            url: previewUrl,
            is_new: true,
            is_deleted: false,
            file,
        });
    }

    input.value = '';
};

// Add YouTube Video or Playlist
const handleAddYoutube = async () => {
    const url = youtubeInput.value.trim();

    if (!url) {
        return;
    }

    if (!url.includes('youtube.com') && !url.includes('youtu.be')) {
        itemErrorMessage.value = 'সঠিক ইউটিউব লিংক দিন।';

        return;
    }

    itemErrorMessage.value = null;

    // Playlist link
    if (url.includes('list=')) {
        isImportingYoutube.value = true;

        try {
            const res = await fetch('/resources/fetch-playlist', {
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
                body: JSON.stringify({ playlist_url: url }),
            });
            const data = await res.json();

            if (!res.ok) {
                throw new Error(
                    data.message ||
                        data.error ||
                        'প্লেলিস্ট থেকে ভিডিও আনা যায়নি।',
                );
            }

            if (!data.items || data.items.length === 0) {
                throw new Error('প্লেলিস্টে কোনো উন্মুক্ত ভিডিও পাওয়া যায়নি।');
            }

            const currentVideoCount = activeItems.value.filter(
                (i) => i.format === 'video',
            ).length;
            const allowedToAdd = 50 - currentVideoCount;

            if (allowedToAdd <= 0) {
                throw new Error(
                    'একটি রিসোর্সে সর্বোচ্চ ৫০টি ভিডিও যুক্ত করা যাবে।',
                );
            }

            const itemsToAdd = data.items.slice(0, allowedToAdd);

            for (const item of itemsToAdd) {
                form.value.items.push({
                    id: 0,
                    resource_id: props.resource?.id,
                    format: 'video',
                    title: item.title || null,
                    source: item.source,
                    url: item.source,
                    is_new: true,
                    is_deleted: false,
                });
            }

            if (data.items.length > allowedToAdd) {
                itemErrorMessage.value = `প্লেলিস্টের প্রথম ${allowedToAdd}টি ভিডিও যুক্ত করা হয়েছে (সর্বোচ্চ সীমা ৫০টি)।`;
            }

            youtubeInput.value = '';
        } catch (err: any) {
            itemErrorMessage.value =
                err.message || 'প্লেলিস্ট আনতে সমস্যা হয়েছে।';
        } finally {
            isImportingYoutube.value = false;
        }
    } else {
        // Single video
        const currentVideoCount = activeItems.value.filter(
            (i) => i.format === 'video',
        ).length;

        if (currentVideoCount >= 50) {
            itemErrorMessage.value =
                'একটি রিসোর্সে সর্বোচ্চ ৫০টি ভিডিও যুক্ত করা যাবে।';

            return;
        }

        form.value.items.push({
            id: 0,
            resource_id: props.resource?.id,
            format: 'video',
            title: `পর্ব ${form.value.items.length + 1}`,
            source: url,
            url,
            is_new: true,
            is_deleted: false,
        });
        youtubeInput.value = '';
    }
};

// Add Google Drive PDF
const handleAddGdrive = () => {
    const url = gdriveInputUrl.value.trim();

    if (!url) {
        return;
    }

    if (!url.includes('drive.google.com')) {
        itemErrorMessage.value = 'সঠিক গুগল ড্রাইভ লিংক দিন।';

        return;
    }

    const currentPdfCount = activeItems.value.filter(
        (i) => i.format === 'pdf',
    ).length;

    if (currentPdfCount >= 5) {
        itemErrorMessage.value =
            'একটি রিসোর্সে সর্বোচ্চ ৫টি PDF ডকুমেন্ট যুক্ত করা যাবে।';

        return;
    }

    itemErrorMessage.value = null;
    form.value.items.push({
        id: 0,
        resource_id: props.resource?.id,
        format: 'pdf',
        title: 'PDF ডকুমেন্ট',
        source: url,
        url,
        is_new: true,
        is_deleted: false,
    });

    gdriveInputUrl.value = '';
};

// Form Validation
const typeMismatchWarning = computed<string | null>(() => {
    const active = activeItems.value;

    if (active.length === 0) {
        return null;
    }

    const formats = new Set(active.map((i) => i.format));

    if (formats.size > 1) {
        return 'রিসোর্সে একাধিক ভিন্ন ফরম্যাট (ছবি, PDF, ভিডিও) একসাথে রাখা যাবে না।';
    }

    const format = active[0].format;

    if (form.value.resource_type === 'video' && format !== 'video') {
        return 'সতর্কতা: এই রিসোর্সে ছবি বা PDF যুক্ত রয়েছে। ভিডিও ক্যাটাগরিতে শুধুমাত্র ভিডিও লিঙ্ক গ্রহণযোগ্য। অনুগ্রহ করে ক্যাটাগরি পরিবর্তন করুন (যেমন: হ্যান্ডনোট / দাগানো বই) অথবা ছবিগুলো মুছে ভিডিও যোগ করুন।';
    }

    if (form.value.resource_type !== 'video' && format === 'video') {
        return 'সতর্কতা: এই রিসোর্সে ভিডিও পর্ব রয়েছে। হ্যান্ডনোট বা বই ক্যাটাগরিতে ভিডিও রাখা যাবে না। ক্যাটাগরি "ভিডিও লেকচার" নির্বাচন করুন।';
    }

    return null;
});

const isFormValid = computed(() => {
    if (!form.value.title.trim()) {
        return false;
    }

    const active = activeItems.value;

    if (active.length === 0) {
        return false;
    }

    const formats = new Set(active.map((i) => i.format));

    if (formats.size > 1) {
        return false;
    }

    const format = active[0].format;

    if (form.value.resource_type === 'video' && format !== 'video') {
        return false;
    }

    if (form.value.resource_type !== 'video' && format === 'video') {
        return false;
    }

    for (const item of active) {
        if (
            (item.format === 'video' || item.format === 'pdf') &&
            !item.source?.trim()
        ) {
            return false;
        }
    }

    return true;
});

// Submit / Save Logic
const handleSubmit = async () => {
    if (!isFormValid.value || isSubmitting.value) {
        return;
    }

    errorMessage.value = null;

    if (!isEditing.value && !props.node) {
        errorMessage.value = 'ফোল্ডার বা চ্যাপ্টার নির্বাচন করা হয়নি।';

        return;
    }

    isSubmitting.value = true;

    try {
        const active = activeItems.value;
        const itemsPayload: Array<{
            format: string;
            source: string;
            title: string | null;
        }> = [];

        // Identify new image files that need compression and S3 upload
        const newImageItems = active.filter(
            (item) => item.format === 'image' && item.file,
        );

        if (newImageItems.length > 0) {
            uploadProgress.value = `ছবি কম্প্রেস করা হচ্ছে (0/${newImageItems.length})...`;

            const compressedFiles: File[] = [];

            for (let i = 0; i < newImageItems.length; i++) {
                uploadProgress.value = `ছবি কম্প্রেস করা হচ্ছে (${i + 1}/${newImageItems.length})...`;
                const compressed = await compressImage(newImageItems[i].file!, {
                    maxWidth: 2048,
                    maxHeight: 2048,
                    quality: 0.85,
                    targetFormat: 'image/webp',
                });
                compressedFiles.push(compressed);
            }

            uploadProgress.value = 'আপলোড লিংক প্রস্তুত করা হচ্ছে...';
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
                    files: compressedFiles.map((file) => ({
                        content_type: 'image/webp',
                        size: file.size,
                    })),
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

            const { urls } = await presignRes.json();

            // Upload directly to Cloudflare R2
            for (let i = 0; i < newImageItems.length; i++) {
                uploadProgress.value = `ক্লাউডে ছবি আপলোড হচ্ছে (${i + 1}/${newImageItems.length})...`;
                const { key, upload_url } = urls[i];
                const file = compressedFiles[i];

                const putRes = await fetch(upload_url, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'image/webp',
                    },
                    body: file,
                });

                if (!putRes.ok) {
                    throw new Error(
                        `ক্লাউডে ছবি #${i + 1} আপলোড ব্যর্থ হয়েছে।`,
                    );
                }

                // Update item's source with the uploaded key
                newImageItems[i].source = key;
            }
        }

        for (const item of active) {
            itemsPayload.push({
                format: item.format,
                source: item.source,
                title: item.title?.trim() || null,
            });
        }

        uploadProgress.value = 'সংরক্ষণ করা হচ্ছে...';

        const endpoint = isEditing.value
            ? `/resources/${props.resource!.id}`
            : '/resources';

        const payload: Record<string, any> = {
            resource_type: form.value.resource_type,
            title: form.value.title.trim(),
            description: form.value.description.trim() || null,
            items: itemsPayload,
        };

        if (isEditing.value) {
            payload.id = props.resource!.id;
        } else {
            payload.node_id = props.node!.id;
        }

        const submitFn = isEditing.value ? router.patch : router.post;

        submitFn(endpoint, payload, {
            preserveScroll: true,
            onSuccess: () => {
                if (isEditing.value) {
                    emit('save', payload);
                }

                isSubmitting.value = false;
                handleClose(true);
            },
            onError: (errors) => {
                errorMessage.value =
                    Object.values(errors).flat().join(', ') ||
                    (isEditing.value
                        ? 'রিসোর্স আপডেট করতে সমস্যা হয়েছে।'
                        : 'রিসোর্স সাবমিট করতে সমস্যা হয়েছে।');
            },
            onFinish: () => {
                isSubmitting.value = false;
                uploadProgress.value = null;
            },
        });
    } catch (err: any) {
        errorMessage.value =
            err.message || 'আপলোড ব্যর্থ হয়েছে। আবার চেষ্টা করুন।';
        isSubmitting.value = false;
        uploadProgress.value = null;
    }
};
</script>

<template>
    <BaseModal
        :is-open="isOpen"
        :title="isEditing ? 'রিসোর্স সম্পাদনা' : 'নতুন রিসোর্স আপলোড'"
        :description="
            props.node?.name
                ? `${props.node.name} অধ্যায়ে যুক্ত করুন`
                : 'শিক্ষার্থীদের জন্য রিসোর্স শেয়ার করুন'
        "
        max-width="2xl"
        @close="handleClose(false)"
    >
        <template #icon>
            <div
                class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
            >
                <Pencil v-if="isEditing" class="h-4.5 w-4.5" />
                <Plus v-else class="h-4.5 w-4.5" />
            </div>
        </template>

        <div class="space-y-4.5 p-4 sm:p-6">
            <!-- 1. Category Dropdown -->
            <div>
                <label
                    class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-gray-300"
                >
                    রিসোর্সের ধরন
                </label>
                <div class="relative">
                    <select
                        v-model="form.resource_type"
                        class="w-full appearance-none rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 pr-10 text-xs font-semibold text-slate-800 transition focus:border-indigo-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                    >
                        <option value="note">হ্যান্ডনোট</option>
                        <option value="marked_book">দাগানো বই</option>
                        <option value="video">ভিডিও লেকচার</option>
                        <option value="practical">ব্যবহারিক</option>
                        <option value="other">অন্যান্য</option>
                    </select>
                    <ChevronDown
                        class="pointer-events-none absolute top-3 right-3 h-4 w-4 text-slate-400 dark:text-gray-500"
                    />
                </div>
            </div>

            <!-- Warning if Category does not match current Items -->
            <div
                v-if="typeMismatchWarning"
                class="flex items-start gap-2.5 rounded-xl border border-amber-300 bg-amber-50/90 p-3 text-xs font-semibold text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-300"
            >
                <AlertCircle
                    class="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400"
                />
                <span>{{ typeMismatchWarning }}</span>
            </div>

            <!-- 2. Title & Optional Description -->
            <div class="space-y-3">
                <div>
                    <label
                        class="mb-1 block text-xs font-bold text-slate-700 dark:text-gray-300"
                    >
                        রিসোর্সের শিরোনাম <span class="text-rose-500">*</span>
                    </label>
                    <input
                        v-model="form.title"
                        type="text"
                        placeholder="যেমন: গতিবিদ্যা পূর্ণাঙ্গ হ্যান্ডনোট..."
                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder-gray-500"
                    />
                </div>

                <div>
                    <label
                        class="mb-1 block text-[11px] font-semibold text-slate-500 dark:text-gray-400"
                    >
                        বিবরণ (ঐচ্ছিক)
                    </label>
                    <input
                        v-model="form.description"
                        type="text"
                        placeholder="রিসোর্স সম্পর্কিত অতিরিক্ত তথ্য থাকলে লিখুন..."
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder-gray-500"
                    />
                </div>
            </div>

            <!-- 3. Content Input Section -->
            <!-- A. Video Resources -->
            <div v-if="form.resource_type === 'video'" class="space-y-2.5">
                <label
                    class="block text-xs font-bold text-slate-700 dark:text-gray-300"
                >
                    ইউটিউব লিংক দিন
                </label>
                <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <input
                            v-model="youtubeInput"
                            type="url"
                            :disabled="isImportingYoutube"
                            placeholder="একক ভিডিও লিংক বা সম্পূর্ণ প্লেলিস্ট লিংক পেস্ট করুন..."
                            class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pr-3 pl-8 text-xs text-slate-900 placeholder-slate-400 focus:border-rose-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder-gray-500"
                            @keydown.enter.prevent="handleAddYoutube"
                        />
                        <Youtube
                            class="absolute top-2.5 left-2.5 h-3.5 w-3.5 text-rose-500"
                        />
                    </div>
                    <button
                        type="button"
                        :disabled="isImportingYoutube || !youtubeInput.trim()"
                        @click="handleAddYoutube"
                        class="inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-xl bg-rose-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <Loader2
                            v-if="isImportingYoutube"
                            class="h-3.5 w-3.5 animate-spin"
                        />
                        <Plus v-else class="h-3.5 w-3.5" />
                        <span>{{
                            isImportingYoutube ? 'আনা হচ্ছে...' : 'যোগ করুন'
                        }}</span>
                    </button>
                </div>
            </div>

            <!-- B. Note / Practical / Other Resources -->
            <div v-else class="space-y-3">
                <!-- Hidden file input always mounted in DOM for photo picking -->
                <input
                    ref="fileInputRef"
                    type="file"
                    multiple
                    accept="image/jpeg,image/png,image/webp"
                    class="hidden"
                    @change="handleNewFilesSelected"
                />

                <!-- If locked to PDF or user switched to Google Drive -->
                <div
                    v-if="docInputMode === 'gdrive' || lockedFormat === 'pdf'"
                    class="space-y-2"
                >
                    <label
                        class="block text-xs font-bold text-slate-700 dark:text-gray-300"
                    >
                        গুগল ড্রাইভ PDF লিংক
                    </label>
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1">
                            <input
                                v-model="gdriveInputUrl"
                                type="url"
                                placeholder="https://drive.google.com/file/d/.../view"
                                class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pr-3 pl-8 text-xs text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder-gray-500"
                                @keydown.enter.prevent="handleAddGdrive"
                            />
                            <LinkIcon
                                class="absolute top-2.5 left-2.5 h-3.5 w-3.5 text-slate-400"
                            />
                        </div>
                        <button
                            type="button"
                            :disabled="!gdriveInputUrl.trim()"
                            @click="handleAddGdrive"
                            class="inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <Plus class="h-3.5 w-3.5" />
                            <span>যোগ করুন</span>
                        </button>
                    </div>

                    <div v-if="lockedFormat !== 'pdf'" class="text-right">
                        <button
                            type="button"
                            @click="docInputMode = 'images'"
                            class="cursor-pointer text-[11px] font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                        >
                            &larr; ছবি ফাইল আপলোড করুন
                        </button>
                    </div>
                </div>

                <!-- Default: Image Dropzone Button (Only shown when 0 items exist) -->
                <div v-else-if="form.items.length === 0" class="space-y-2">
                    <button
                        type="button"
                        @click="triggerFileInput"
                        class="flex w-full cursor-pointer items-center justify-center gap-2.5 rounded-xl border-2 border-dashed border-indigo-200 bg-indigo-50/40 px-4 py-3.5 text-center transition hover:border-indigo-400 hover:bg-indigo-50/70 dark:border-indigo-900/50 dark:bg-indigo-950/20 dark:hover:border-indigo-700"
                    >
                        <div
                            class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600 dark:bg-indigo-900/60 dark:text-indigo-400"
                        >
                            <UploadCloud class="h-4 w-4" />
                        </div>
                        <span
                            class="text-xs font-bold text-slate-800 dark:text-gray-200"
                        >
                            নোটের ছবিসমূহ নির্বাচন করুন
                        </span>
                    </button>

                    <div
                        v-if="lockedFormat !== 'image'"
                        class="pt-0.5 text-center"
                    >
                        <button
                            type="button"
                            @click="docInputMode = 'gdrive'"
                            class="cursor-pointer text-[11px] font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                        >
                            অথবা গুগল ড্রাইভ PDF লিংক দিন &rarr;
                        </button>
                    </div>
                </div>
            </div>

            <!-- Error Feedback -->
            <div
                v-if="itemErrorMessage"
                class="flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50/90 p-2.5 text-xs font-semibold text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300"
            >
                <AlertCircle class="h-4 w-4 shrink-0" />
                <span>{{ itemErrorMessage }}</span>
            </div>

            <!-- 3. Current Items Preview Grid (Multi-column) -->
            <div v-if="form.items.length > 0" class="space-y-2 pt-1">
                <div
                    class="flex items-center justify-between text-xs font-bold text-slate-700 dark:text-gray-300"
                >
                    <span> Items({{ activeItems.length }}) </span>
                    <div class="flex items-center gap-2">
                        <button
                            v-if="
                                form.resource_type !== 'video' &&
                                lockedFormat === 'image'
                            "
                            type="button"
                            @click="triggerFileInput"
                            class="inline-flex cursor-pointer items-center gap-1 rounded-lg bg-indigo-50 px-2.5 py-1 text-[11px] font-bold text-indigo-600 transition hover:bg-indigo-100 dark:bg-indigo-950/50 dark:text-indigo-300 dark:hover:bg-indigo-900/50"
                        >
                            <Plus class="h-3 w-3" />
                            <span>আরও ছবি যোগ করুন</span>
                        </button>

                        <button
                            v-if="activeItems.length > 0"
                            type="button"
                            @click="handleClearAllItems"
                            class="cursor-pointer rounded-lg p-1 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 dark:hover:text-rose-400"
                            title="সবগুলো মুছে ফেলুন"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <div
                    class="grid grid-cols-2 items-start gap-2.5 sm:grid-cols-3 md:grid-cols-4"
                >
                    <div
                        v-for="(item, index) in form.items"
                        :key="item.id || item.url || index"
                        :data-item-index="index"
                        :draggable="!item.is_deleted"
                        @dragstart="handleDragStart($event, index)"
                        @dragover="handleDragOver($event, index)"
                        @drop="handleDrop($event, index)"
                        @dragend="handleDragEnd"
                        class="group relative flex flex-col rounded-xl border p-2 shadow-2xs transition-all select-none"
                        :class="[
                            item.is_deleted
                                ? 'border-rose-300 bg-rose-50/50 opacity-70 dark:border-rose-900/60 dark:bg-rose-950/20'
                                : draggedIndex === index
                                  ? 'scale-95 border-dashed border-slate-300 opacity-30 dark:border-gray-700'
                                  : 'border-slate-200/90 bg-white hover:border-slate-300 dark:border-gray-800 dark:bg-gray-800/80',
                        ]"
                    >
                        <!-- Top Mini Bar -->
                        <div class="mb-1.5 flex items-center justify-between">
                            <div class="flex items-center gap-1">
                                <div
                                    v-if="!item.is_deleted"
                                    @touchstart.passive="
                                        handleTouchStart($event, index)
                                    "
                                    @touchmove="handleTouchMove"
                                    @touchend="handleTouchEnd"
                                    class="flex h-5 w-4 shrink-0 cursor-grab touch-none items-center justify-center rounded text-slate-400 hover:text-slate-600 active:cursor-grabbing dark:hover:text-gray-200"
                                    title="টেনে সাজান"
                                >
                                    <GripVertical class="h-3 w-3" />
                                </div>
                                <span
                                    class="py-0.2 rounded bg-slate-100 px-1 text-[9px] font-extrabold text-slate-600 dark:bg-gray-700 dark:text-gray-300"
                                >
                                    #{{ index + 1 }}
                                </span>
                            </div>

                            <button
                                type="button"
                                @click="toggleDeleteItem(index)"
                                class="cursor-pointer rounded p-0.5 text-slate-400 transition hover:text-rose-600 dark:hover:text-rose-400"
                                :title="
                                    item.is_deleted ? 'বাতিল' : 'মুছে ফেলুন'
                                "
                            >
                                <RotateCcw
                                    v-if="item.is_deleted"
                                    class="h-3 w-3 text-indigo-600"
                                />
                                <Trash2 v-else class="h-3 w-3" />
                            </button>
                        </div>

                        <!-- Media Thumbnail -->
                        <div
                            class="relative aspect-4/3 w-full shrink-0 overflow-hidden rounded-lg bg-slate-100 dark:bg-gray-900"
                        >
                            <!-- Image Format -->
                            <button
                                v-if="item.format === 'image'"
                                type="button"
                                :disabled="item.is_deleted"
                                @click="openImageViewer(item)"
                                class="h-full w-full cursor-zoom-in"
                            >
                                <img
                                    :src="item.url"
                                    :alt="'Page ' + (index + 1)"
                                    class="h-full w-full object-cover"
                                />
                            </button>

                            <!-- Video Format -->
                            <div
                                v-else-if="item.format === 'video'"
                                class="h-full w-full"
                            >
                                <img
                                    v-if="
                                        getYouTubeThumbnail(
                                            item.source || item.url,
                                        )
                                    "
                                    :src="
                                        getYouTubeThumbnail(
                                            item.source || item.url,
                                        )!
                                    "
                                    alt="Thumbnail"
                                    class="h-full w-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center bg-rose-50 dark:bg-rose-950/30"
                                >
                                    <Youtube class="h-6 w-6 text-rose-500" />
                                </div>
                            </div>

                            <!-- PDF Format -->
                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center"
                            >
                                <File class="h-6 w-6 text-rose-500" />
                            </div>

                            <!-- Deleted Overlay -->
                            <div
                                v-if="item.is_deleted"
                                class="absolute inset-0 flex items-center justify-center bg-rose-950/30 backdrop-blur-[1px]"
                            >
                                <span
                                    class="rounded bg-rose-600 px-1.5 py-0.5 text-[9px] font-extrabold text-white"
                                >
                                    মুছে ফেলা হবে
                                </span>
                            </div>
                        </div>

                        <!-- Video Title (Only for Video format) -->
                        <div v-if="item.format === 'video'" class="mt-1.5">
                            <input
                                v-model="item.title"
                                type="text"
                                :disabled="item.is_deleted"
                                :placeholder="`পর্ব ${index + 1} শিরোনাম`"
                                class="w-full rounded border border-slate-200 bg-slate-50/70 px-1.5 py-0.5 text-[10px] text-slate-800 focus:bg-white focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Global Error Banner -->
            <div
                v-if="errorMessage"
                class="flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50/90 p-3 text-xs font-medium text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-300"
            >
                <AlertCircle class="h-4 w-4 shrink-0" />
                <span>{{ errorMessage }}</span>
            </div>
        </div>

        <!-- Footer -->
        <template #footer>
            <div class="flex items-center justify-between gap-3">
                <span class="text-[11px] text-slate-400 dark:text-gray-500">
                    <span
                        v-if="uploadProgress"
                        class="flex items-center gap-1.5 font-bold text-indigo-600 dark:text-indigo-400"
                    >
                        <Loader2 class="h-3.5 w-3.5 animate-spin" />
                        {{ uploadProgress }}
                    </span>
                </span>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        :disabled="isSubmitting"
                        @click="handleClose(false)"
                        class="cursor-pointer rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 shadow-2xs transition hover:bg-slate-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                    >
                        বাতিল
                    </button>

                    <button
                        type="button"
                        :disabled="isSubmitting || !isFormValid"
                        :title="
                            typeMismatchWarning ||
                            (!form.title.trim()
                                ? 'রিসোর্সের শিরোনাম দিন'
                                : activeItems.length === 0
                                  ? 'কমপক্ষে একটি পৃষ্ঠা বা ভিডিও যোগ করুন'
                                  : '')
                        "
                        @click="handleSubmit"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-indigo-600 px-5 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <Loader2
                            v-if="isSubmitting"
                            class="h-3.5 w-3.5 animate-spin"
                        />
                        <Check v-else class="h-3.5 w-3.5" />
                        <span>{{
                            isEditing ? 'সংরক্ষণ করুন' : 'আপলোড করুন'
                        }}</span>
                    </button>
                </div>
            </div>
        </template>
    </BaseModal>

    <!-- Lightbox Modal for Pin-Point Image Zoom & Pan Preview -->
    <ImageViewerModal
        v-if="currentPreviewImage"
        v-model="isImageViewerOpen"
        :src="currentPreviewImage.url || currentPreviewImage.source"
        :title="currentPreviewImage.title || `পৃষ্ঠা ${activePreviewIndex + 1}`"
        :has-prev="activePreviewIndex > 0"
        :has-next="activePreviewIndex < imageItems.length - 1"
        :page-indicator="`পৃষ্ঠা ${activePreviewIndex + 1} / ${imageItems.length}`"
        @prev="goToPrevImage"
        @next="goToNextImage"
        @close="isImageViewerOpen = false"
    />
</template>
