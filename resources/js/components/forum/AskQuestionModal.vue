<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Send, Loader2, ChevronDown, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import BaseModal from '@/components/BaseModal.vue';
import { compressImage } from '@/lib/imageCompression';

interface NodeItem {
    id: number;
    subject_id: number;
    name: string;
    slug: string;
}

interface Subject {
    id: number;
    name: string;
    course: 'hsc' | 'ssc';
    slug: string;
    nodes?: NodeItem[];
}

const props = withDefaults(
    defineProps<{
        isOpen: boolean;
        subjects?: Subject[];
        initialCurriculum?: 'hsc' | 'ssc';
    }>(),
    {
        subjects: () => [],
        initialCurriculum: 'hsc',
    },
);

const emit = defineEmits<{
    (e: 'update:isOpen', value: boolean): void;
    (e: 'close'): void;
}>();

const form = useForm({
    curriculum: 'hsc' as 'hsc' | 'ssc',
    subject_id: '' as string | number,
    node_id: '' as string | number,
    body: '',
    images: [] as string[],
});

interface LocalImageItem {
    id: string;
    file?: File;
    previewUrl: string;
    uploadedKey?: string;
}

const localImages = ref<LocalImageItem[]>([]);
const isCompressing = ref(false);
const isUploading = ref(false);
const imageError = ref<string | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);

const safeSubjects = computed<Subject[]>(() => {
    return Array.isArray(props.subjects) ? props.subjects : [];
});

const filteredSubjects = computed(() => {
    return safeSubjects.value.filter((s) => s.course === form.curriculum);
});

const filteredNodes = computed(() => {
    if (!form.subject_id) {
        return [];
    }

    const subj = safeSubjects.value.find(
        (s) => s.id === Number(form.subject_id),
    );

    return subj?.nodes || [];
});

watch(
    () => props.initialCurriculum,
    (curr) => {
        if (curr) {
            form.curriculum = curr;
        }
    },
    { immediate: true },
);

watch(
    () => form.curriculum,
    () => {
        form.subject_id = '';
        form.node_id = '';
    },
);

watch(
    () => form.subject_id,
    () => {
        form.node_id = '';
    },
);

watch(
    () => props.isOpen,
    (open) => {
        if (open) {
            form.reset();
            form.clearErrors();

            if (props.initialCurriculum) {
                form.curriculum = props.initialCurriculum;
            }

            cleanupLocalImages();
        } else {
            cleanupLocalImages();
        }
    },
);

const cleanupLocalImages = () => {
    localImages.value.forEach((img) => {
        try {
            URL.revokeObjectURL(img.previewUrl);
        } catch {}
    });
    localImages.value = [];
    imageError.value = null;
};

const triggerFileInput = () => {
    fileInputRef.value?.click();
};

const handleFilesSelected = async (e: Event) => {
    const target = e.target as HTMLInputElement;

    if (!target.files || target.files.length === 0) {
        return;
    }

    imageError.value = null;
    const files = Array.from(target.files);

    if (localImages.value.length + files.length > 4) {
        imageError.value = `সর্বোচ্চ ৪টি ছবি যুক্ত করা যাবে (বর্তমানে আছে ${localImages.value.length}টি)।`;
        target.value = '';

        return;
    }

    try {
        isCompressing.value = true;

        for (const file of files) {
            if (
                !['image/jpeg', 'image/png', 'image/webp'].includes(file.type)
            ) {
                imageError.value =
                    'শুধুমাত্র JPG, PNG বা WebP ফরম্যাটের ছবি আপলোড করা যাবে।';
                continue;
            }

            let compressed: File;

            try {
                compressed = await compressImage(file, {
                    maxWidth: 2048,
                    maxHeight: 2048,
                    quality: 0.85,
                });
            } catch {
                compressed = file;
            }

            if (compressed.size > 5 * 1024 * 1024) {
                imageError.value =
                    'ছবিটি ৫MB এর বেশি। অনুগ্রহ করে ছোট ছবি নির্বাচন করুন।';
                continue;
            }

            localImages.value.push({
                id: Math.random().toString(36).slice(2),
                file: compressed,
                previewUrl: URL.createObjectURL(compressed),
            });
        }
    } finally {
        isCompressing.value = false;
        target.value = '';
    }
};

const removeLocalImage = (index: number) => {
    const item = localImages.value[index];

    if (item) {
        try {
            URL.revokeObjectURL(item.previewUrl);
        } catch {}

        localImages.value.splice(index, 1);
    }
};

const handleClose = () => {
    if (form.processing || isUploading.value) {
        return;
    }

    cleanupLocalImages();
    emit('update:isOpen', false);
    emit('close');
};

const submitQuestion = async () => {
    if (!form.body || form.body.trim().length < 5) {
        form.setError(
            'body',
            'প্রশ্নের বিস্তারিত বিবরণ ন্যূনতম ৫ অক্ষরের হতে হবে।',
        );

        return;
    }

    if (form.processing || isUploading.value) {
        return;
    }

    try {
        isUploading.value = true;

        // If there are local image files, request presigned URLs
        const filesToUpload = localImages.value.filter(
            (img) => img.file && !img.uploadedKey,
        );

        if (filesToUpload.length > 0) {
            const presignPayload = {
                files: filesToUpload.map((img) => ({
                    content_type: img.file!.type,
                    size: img.file!.size,
                })),
            };

            const presignRes = await fetch('/forum/upload-url', {
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
                body: JSON.stringify(presignPayload),
            });

            if (!presignRes.ok) {
                const errData = await presignRes.json().catch(() => ({}));

                throw new Error(
                    errData.message || 'ছবি আপলোডের অনুমতি পাওয়া যায়নি।',
                );
            }

            const { urls } = await presignRes.json();

            for (let i = 0; i < filesToUpload.length; i++) {
                const { upload_url, key } = urls[i];
                const file = filesToUpload[i].file!;

                const uploadRes = await fetch(upload_url, {
                    method: 'PUT',
                    headers: { 'Content-Type': file.type },
                    body: file,
                });

                if (!uploadRes.ok) {
                    throw new Error('ক্লাউডে ছবি আপলোড করতে সমস্যা হয়েছে।');
                }

                filesToUpload[i].uploadedKey = key;
            }
        }

        form.images = localImages.value
            .map((img) => img.uploadedKey)
            .filter(Boolean) as string[];

        form.post('/forum/questions', {
            preserveScroll: true,
            onSuccess: () => {
                handleClose();
            },
            onError: (errs) => {
                imageError.value = Object.values(errs).flat().join(', ');
            },
            onFinish: () => {
                isUploading.value = false;
            },
        });
    } catch (err: any) {
        isUploading.value = false;
        imageError.value = err.message || 'প্রশ্ন পোস্ট করতে সমস্যা হয়েছে।';
    }
};
</script>

<template>
    <BaseModal
        :is-open="isOpen"
        max-width="max-w-2xl"
        title="নতুন প্রশ্ন করুন"
        @close="handleClose"
    >
        <div class="space-y-4 p-4 sm:p-6">
            <!-- Curriculum Selector -->
            <div>
                <label
                    class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-gray-300"
                >
                    কারিকুলাম লেভেল <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-2 gap-2">
                    <button
                        type="button"
                        @click="form.curriculum = 'hsc'"
                        class="flex items-center justify-center gap-2 rounded-xl border py-2.5 text-xs font-bold transition"
                        :class="
                            form.curriculum === 'hsc'
                                ? 'border-indigo-600 bg-indigo-50/70 text-indigo-700 dark:border-indigo-500 dark:bg-indigo-950/40 dark:text-indigo-300'
                                : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400'
                        "
                    >
                        <span>HSC</span>
                    </button>
                    <button
                        type="button"
                        @click="form.curriculum = 'ssc'"
                        class="flex items-center justify-center gap-2 rounded-xl border py-2.5 text-xs font-bold transition"
                        :class="
                            form.curriculum === 'ssc'
                                ? 'border-indigo-600 bg-indigo-50/70 text-indigo-700 dark:border-indigo-500 dark:bg-indigo-950/40 dark:text-indigo-300'
                                : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400'
                        "
                    >
                        <span>SSC</span>
                    </button>
                </div>
            </div>

            <!-- Subject & Chapter Selection -->
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label
                        class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-gray-300"
                    >
                        বিষয় (ঐচ্ছিক)
                    </label>
                    <div class="relative">
                        <select
                            v-model="form.subject_id"
                            class="w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 py-2.5 pr-8 text-xs font-medium text-slate-800 transition focus:border-indigo-500 focus:outline-none dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200"
                        >
                            <option value="">সকল বিষয় / সাধারণ প্রশ্ন</option>
                            <option
                                v-for="subj in filteredSubjects"
                                :key="subj.id"
                                :value="subj.id"
                            >
                                {{ subj.name }}
                            </option>
                        </select>
                        <ChevronDown
                            class="pointer-events-none absolute top-3 right-3 h-4 w-4 text-slate-400 dark:text-gray-500"
                        />
                    </div>
                </div>

                <div>
                    <label
                        class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-gray-300"
                    >
                        অধ্যায় (ঐচ্ছিক)
                    </label>
                    <div class="relative">
                        <select
                            v-model="form.node_id"
                            :disabled="
                                !form.subject_id || filteredNodes.length === 0
                            "
                            class="w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 py-2.5 pr-8 text-xs font-medium text-slate-800 transition focus:border-indigo-500 focus:outline-none disabled:bg-slate-50 disabled:text-slate-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:disabled:bg-gray-900/40"
                        >
                            <option value="">পুরো বিষয় সম্পর্কিত</option>
                            <option
                                v-for="node in filteredNodes"
                                :key="node.id"
                                :value="node.id"
                            >
                                {{ node.name }}
                            </option>
                        </select>
                        <ChevronDown
                            class="pointer-events-none absolute top-3 right-3 h-4 w-4 text-slate-400 dark:text-gray-500"
                        />
                    </div>
                </div>
            </div>

            <!-- Question Body -->
            <div>
                <label
                    class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-gray-300"
                >
                    আপনার প্রশ্নটি বিস্তারিত লিখুন
                    <span class="text-rose-500">*</span>
                </label>
                <textarea
                    v-model="form.body"
                    rows="5"
                    placeholder="পড়ালেখার যে বিষয়টি বুঝতে আপনার সমস্যা হচ্ছে তা বিস্তারিত লিখুন..."
                    class="w-full rounded-2xl border border-slate-200 p-3.5 text-sm text-slate-800 placeholder-slate-400 transition focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/20 focus:outline-none dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-500"
                ></textarea>
                <p
                    v-if="form.errors.body"
                    class="mt-1 text-xs font-medium text-rose-500"
                >
                    {{ form.errors.body }}
                </p>
            </div>

            <!-- Attached Images (Up to 4) -->
            <div>
                <div class="mb-1.5 flex items-center justify-between">
                    <label
                        class="text-xs font-bold text-slate-700 dark:text-gray-300"
                    >
                        ছবি যুক্ত করুন (সর্বোচ্চ ৪টি, ঐচ্ছিক)
                    </label>
                    <span class="text-[11px] font-medium text-slate-400">
                        {{ localImages.length }}/4
                    </span>
                </div>

                <!-- Hidden file input -->
                <input
                    ref="fileInputRef"
                    type="file"
                    multiple
                    accept="image/jpeg,image/png,image/webp"
                    class="hidden"
                    @change="handleFilesSelected"
                />

                <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-4">
                    <div
                        v-for="(img, idx) in localImages"
                        :key="img.id"
                        class="group relative aspect-square overflow-hidden rounded-xl border border-slate-200 bg-slate-50 dark:border-gray-800 dark:bg-gray-900"
                    >
                        <img
                            :src="img.previewUrl"
                            class="h-full w-full object-cover"
                        />
                        <button
                            type="button"
                            @click="removeLocalImage(idx)"
                            class="absolute top-1.5 right-1.5 rounded-lg bg-black/60 p-1 text-white shadow-xs backdrop-blur-xs transition hover:bg-rose-600"
                            title="মুছে ফেলুন"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                        </button>
                    </div>

                    <!-- Add Button -->
                    <button
                        v-if="localImages.length < 4"
                        type="button"
                        :disabled="isCompressing"
                        @click="triggerFileInput"
                        class="flex aspect-square flex-col items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-slate-200 text-slate-500 transition hover:border-indigo-300 hover:bg-indigo-50/30 hover:text-indigo-600 dark:border-gray-800 dark:text-gray-400 dark:hover:border-indigo-900/60 dark:hover:bg-indigo-950/20"
                    >
                        <Loader2
                            v-if="isCompressing"
                            class="h-5 w-5 animate-spin text-indigo-500"
                        />
                        <template v-else>
                            <Plus class="h-5 w-5" />
                            <span class="text-[11px] font-semibold"
                                >ছবি যোগ করুন</span
                            >
                        </template>
                    </button>
                </div>

                <p
                    v-if="imageError"
                    class="mt-1.5 text-xs font-medium text-rose-500"
                >
                    {{ imageError }}
                </p>
            </div>

            <!-- Footer Actions -->
            <div
                class="flex items-center justify-end gap-2.5 border-t border-slate-100 pt-3 dark:border-gray-800/80"
            >
                <button
                    type="button"
                    @click="handleClose"
                    :disabled="form.processing || isUploading"
                    class="rounded-xl px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800"
                >
                    বাতিল
                </button>

                <button
                    type="button"
                    @click="submitQuestion"
                    :disabled="
                        form.processing ||
                        isUploading ||
                        isCompressing ||
                        !form.body.trim()
                    "
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-xs transition hover:bg-indigo-700 active:scale-95 disabled:opacity-50"
                >
                    <Loader2
                        v-if="form.processing || isUploading"
                        class="h-4 w-4 animate-spin"
                    />
                    <Send v-else class="h-4 w-4" />
                    <span>{{
                        form.processing || isUploading
                            ? 'পোস্ট হচ্ছে...'
                            : 'প্রশ্ন পোস্ট করুন'
                    }}</span>
                </button>
            </div>
        </div>
    </BaseModal>
</template>
