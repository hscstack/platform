<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Upload,
    X,
    Send,
    ShieldCheck,
    Search,
    FileText,
    CheckCircle,
    Loader2,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
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

const props = defineProps<{
    subjects: Subject[];
}>();

const form = useForm({
    curriculum: 'hsc' as 'hsc' | 'ssc',
    subject_id: '' as string | number,
    node_id: '' as string | number,
    title: '',
    body: '',
    image: null as File | null,
});

const showConfirmModal = ref(false);
const modalConfirmed = ref(false);
const imagePreview = ref<string | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);

const safeSubjects = computed<Subject[]>(() => {
    if (Array.isArray(props.subjects)) {
        return props.subjects;
    }

    if (props.subjects && typeof props.subjects === 'object') {
        return Object.values(props.subjects) as Subject[];
    }

    return [];
});

const filteredSubjects = computed(() => {
    return safeSubjects.value.filter((s) => s && s.course === form.curriculum);
});

const selectedSubject = computed(() => {
    return safeSubjects.value.find(
        (s) => s && s.id === Number(form.subject_id),
    );
});

const currentSubjectNodes = computed(() => {
    return selectedSubject.value?.nodes || [];
});

watch(
    () => form.subject_id,
    () => {
        form.node_id = '';
    },
);

const setCurriculum = (curriculum: 'hsc' | 'ssc') => {
    form.curriculum = curriculum;
    form.subject_id = '';
    form.node_id = '';
};

const isCompressingImage = ref(false);
const imageError = ref('');

const processSelectedImage = async (rawFile: File) => {
    imageError.value = '';

    const allowed = ['image/jpeg', 'image/png', 'image/webp'];

    if (!allowed.includes(rawFile.type)) {
        imageError.value = 'অনুমোদিত ফরম্যাট: JPG, PNG, WEBP।';

        return;
    }

    try {
        isCompressingImage.value = true;
        let resultFile: File;

        try {
            resultFile = await compressImage(rawFile, {
                maxWidth: 2048,
                maxHeight: 2048,
                quality: 0.85,
            });
        } catch {
            resultFile = rawFile;
        }

        if (resultFile.size > 5 * 1024 * 1024) {
            imageError.value =
                'ছবিটি অপটিমাইজ করার পরও ৫MB এর বেশি। অনুগ্রহ করে ছোট ছবি নির্বাচন করুন।';

            return;
        }

        form.image = resultFile;

        if (imagePreview.value) {
            URL.revokeObjectURL(imagePreview.value);
        }

        imagePreview.value = URL.createObjectURL(resultFile);
    } finally {
        isCompressingImage.value = false;
    }
};

const handleFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;

    if (target.files && target.files[0]) {
        processSelectedImage(target.files[0]);
    }
};

const handleFileDrop = (e: DragEvent) => {
    if (e.dataTransfer?.files && e.dataTransfer.files[0]) {
        const file = e.dataTransfer.files[0];
        const allowed = ['image/jpeg', 'image/png', 'image/webp'];

        if (allowed.includes(file.type)) {
            processSelectedImage(file);
        }
    }
};

const removeImage = () => {
    form.image = null;
    imageError.value = '';

    if (imagePreview.value) {
        URL.revokeObjectURL(imagePreview.value);
        imagePreview.value = null;
    }

    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const handleOpenModal = () => {
    if (isCompressingImage.value) {
        return;
    }

    // Basic frontend checks before modal
    if (!form.title.trim()) {
        form.post('/forum'); // trigger inertia validation errors if fields are empty

        return;
    }

    modalConfirmed.value = false;
    showConfirmModal.value = true;
};

const submit = () => {
    if (isCompressingImage.value || !modalConfirmed.value) {
        return;
    }

    form.post('/forum', {
        forceFormData: true,
        onSuccess: () => {
            showConfirmModal.value = false;
        },
        onError: () => {
            showConfirmModal.value = false;
        },
    });
};
</script>

<template>
    <Head title="Ask a Question — HSCStack Forum" />

    <main class="mx-auto max-w-3xl px-3.5 py-3.5 sm:px-6 sm:py-8">
        <!-- Back Link -->
        <div class="mb-2.5 sm:mb-5">
            <Link
                href="/forum"
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 transition hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400"
            >
                <ArrowLeft class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                <span>Back to Forum</span>
            </Link>
        </div>

        <!-- Card Container -->
        <div
            class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:p-8 dark:border-gray-800 dark:bg-gray-900"
        >
            <div
                class="mb-4 border-b border-slate-100 pb-4 sm:mb-6 sm:pb-5 dark:border-gray-800"
            >
                <h1
                    class="text-lg font-extrabold text-slate-900 sm:text-2xl dark:text-gray-100"
                >
                    Ask a Question
                </h1>
                <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">
                    নির্দিষ্ট বিষয় ও বিস্তারিত তথ্য দিয়ে আপনার পড়াশোনা সংক্রান্ত
                    প্রশ্নটি লিখুন।
                </p>
            </div>

            <form @submit.prevent="handleOpenModal" class="space-y-6">
                <!-- Curriculum Selection -->
                <div>
                    <label
                        class="mb-2 block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-gray-300"
                    >
                        Curriculum <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            @click="setCurriculum('hsc')"
                            class="flex items-center justify-center rounded-xl border py-3 text-sm font-bold transition"
                            :class="[
                                form.curriculum === 'hsc'
                                    ? 'border-indigo-600 bg-indigo-50/70 text-indigo-600 ring-2 ring-indigo-600/20 dark:bg-indigo-950/40 dark:text-indigo-400'
                                    : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800',
                            ]"
                        >
                            HSC
                        </button>
                        <button
                            type="button"
                            @click="setCurriculum('ssc')"
                            class="flex items-center justify-center rounded-xl border py-3 text-sm font-bold transition"
                            :class="[
                                form.curriculum === 'ssc'
                                    ? 'border-emerald-600 bg-emerald-50/70 text-emerald-600 ring-2 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-400'
                                    : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800',
                            ]"
                        >
                            SSC
                        </button>
                    </div>
                    <p
                        v-if="form.errors.curriculum"
                        class="mt-1.5 text-xs text-rose-500"
                    >
                        {{ form.errors.curriculum }}
                    </p>
                </div>

                <!-- Subject & Chapter Row -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- Subject -->
                    <div>
                        <label
                            for="subject_id"
                            class="mb-2 block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-gray-300"
                        >
                            Subject
                            <span class="text-xs font-normal text-slate-400"
                                >(Optional)</span
                            >
                        </label>
                        <select
                            id="subject_id"
                            v-model="form.subject_id"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-2xs transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100"
                        >
                            <option value="">Other / General</option>
                            <option
                                v-for="subj in filteredSubjects"
                                :key="subj.id"
                                :value="subj.id"
                            >
                                {{ subj.name }}
                            </option>
                        </select>
                        <p
                            v-if="form.errors.subject_id"
                            class="mt-1.5 text-xs text-rose-500"
                        >
                            {{ form.errors.subject_id }}
                        </p>
                    </div>

                    <!-- Topic / Chapter Node (Nullable) -->
                    <div>
                        <label
                            for="node_id"
                            class="mb-2 block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-gray-300"
                        >
                            Chapter / Topic
                            <span class="text-xs font-normal text-slate-400"
                                >(Optional)</span
                            >
                        </label>
                        <select
                            id="node_id"
                            v-model="form.node_id"
                            :disabled="
                                !form.subject_id ||
                                currentSubjectNodes.length === 0
                            "
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-2xs transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100 dark:disabled:bg-gray-800/50 dark:disabled:text-gray-500"
                        >
                            <option value="">
                                {{
                                    !form.subject_id
                                        ? 'Select a subject first'
                                        : currentSubjectNodes.length === 0
                                          ? 'No chapters available'
                                          : 'All Chapters / Topics (Optional)'
                                }}
                            </option>
                            <option
                                v-for="node in currentSubjectNodes"
                                :key="node.id"
                                :value="node.id"
                            >
                                {{ node.name }}
                            </option>
                        </select>
                        <p
                            v-if="form.errors.node_id"
                            class="mt-1.5 text-xs text-rose-500"
                        >
                            {{ form.errors.node_id }}
                        </p>
                    </div>
                </div>

                <!-- Title -->
                <div>
                    <label
                        for="title"
                        class="mb-2 block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-gray-300"
                    >
                        Question Title <span class="text-rose-500">*</span>
                    </label>
                    <input
                        id="title"
                        v-model="form.title"
                        type="text"
                        required
                        maxlength="255"
                        placeholder="যেমন: ভেক্টর বিভাজনের বাস্তব উদাহরণ কী কী হতে পারে?"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-2xs transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500"
                    />
                    <p
                        v-if="form.errors.title"
                        class="mt-1.5 text-xs text-rose-500"
                    >
                        {{ form.errors.title }}
                    </p>
                </div>

                <!-- Body -->
                <div>
                    <label
                        for="body"
                        class="mb-2 block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-gray-300"
                    >
                        Question Details
                        <span class="text-xs font-normal text-slate-400"
                            >(Optional)</span
                        >
                    </label>
                    <textarea
                        id="body"
                        v-model="form.body"
                        rows="6"
                        placeholder="আপনার সমস্যাটি বিস্তারিতভাবে লিখুন (প্রযোজ্য ক্ষেত্রে)..."
                        class="w-full rounded-xl border border-slate-200 bg-white p-3.5 text-sm text-slate-900 shadow-2xs transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500"
                    ></textarea>
                    <p
                        v-if="form.errors.body"
                        class="mt-1.5 text-xs text-rose-500"
                    >
                        {{ form.errors.body }}
                    </p>
                </div>

                <!-- Optional Image Upload -->
                <div>
                    <label
                        class="mb-2 block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-gray-300"
                    >
                        Attach Image
                        <span class="text-xs font-normal text-slate-400"
                            >(Optional, Max 20MB, auto-optimized)</span
                        >
                    </label>

                    <div v-if="!imagePreview">
                        <input
                            ref="fileInputRef"
                            type="file"
                            id="post-image-file"
                            accept="image/jpeg,image/png,image/jpg,image/webp"
                            :disabled="isCompressingImage"
                            @click="
                                (e) =>
                                    ((e.target as HTMLInputElement).value = '')
                            "
                            @change="handleFileChange"
                            class="hidden"
                        />
                        <label
                            for="post-image-file"
                            @dragover.prevent
                            @dragenter.prevent
                            @drop.prevent="handleFileDrop"
                            class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/50 p-6 text-center transition hover:border-indigo-500 hover:bg-indigo-50/20 dark:border-gray-800 dark:bg-gray-900/50 dark:hover:border-indigo-500"
                            :class="{
                                'cursor-not-allowed opacity-60':
                                    isCompressingImage,
                            }"
                        >
                            <Loader2
                                v-if="isCompressingImage"
                                class="mb-2 h-6 w-6 animate-spin text-indigo-600 dark:text-indigo-400"
                            />
                            <Upload
                                v-else
                                class="mb-2 h-6 w-6 text-slate-400 dark:text-gray-500"
                            />
                            <span
                                class="text-xs font-semibold text-slate-700 dark:text-gray-300"
                            >
                                {{
                                    isCompressingImage
                                        ? 'Optimizing image...'
                                        : 'Click or drag to upload an image'
                                }}
                            </span>
                            <span
                                class="mt-1 text-[11px] text-slate-400 dark:text-gray-500"
                            >
                                JPG, PNG, WEBP up to 20MB (auto-optimized)
                            </span>
                        </label>
                    </div>

                    <!-- Image Preview Area -->
                    <div v-else class="relative inline-block">
                        <img
                            :src="imagePreview"
                            alt="Uploaded question preview"
                            class="max-h-56 rounded-xl border border-slate-200 object-contain shadow-xs dark:border-gray-800"
                        />
                        <button
                            type="button"
                            @click="removeImage"
                            class="absolute top-2 right-2 rounded-lg bg-slate-900/80 p-1.5 text-white shadow-md backdrop-blur-xs transition hover:bg-rose-600"
                            aria-label="Remove image"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <p v-if="imageError" class="mt-1.5 text-xs text-rose-500">
                        {{ imageError }}
                    </p>

                    <p
                        v-if="form.errors.image"
                        class="mt-1.5 text-xs text-rose-500"
                    >
                        {{ form.errors.image }}
                    </p>
                </div>

                <!-- Submit Button -->
                <div
                    class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5 dark:border-gray-800"
                >
                    <Link
                        href="/forum"
                        class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing || isCompressingImage"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-700 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <Send class="h-4 w-4" />
                        <span>{{
                            form.processing ? 'Posting...' : 'Post Question'
                        }}</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Anti-Spam / Guidelines Confirmation Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-150 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition duration-100 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-if="showConfirmModal"
                    class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-xs dark:bg-black/60"
                    @click.self="showConfirmModal = false"
                >
                    <div
                        class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-gray-800 dark:bg-gray-900"
                    >
                        <!-- Modal Header -->
                        <div
                            class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3.5 dark:border-gray-800"
                        >
                            <div class="flex items-center gap-2.5">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                                >
                                    <ShieldCheck class="h-4.5 w-4.5" />
                                </div>
                                <h3
                                    class="text-sm font-bold text-slate-900 dark:text-gray-100"
                                >
                                    প্রশ্ন পোস্ট করার পূর্বে নিশ্চিতকরণ
                                </h3>
                            </div>

                            <button
                                type="button"
                                @click="showConfirmModal = false"
                                class="cursor-pointer rounded-lg p-1 text-slate-400 hover:text-slate-600 dark:text-gray-500 dark:hover:text-gray-300"
                                aria-label="Close modal"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <!-- Modal Body / Guidelines Checklist -->
                        <div
                            class="space-y-3.5 text-xs text-slate-600 dark:text-gray-300"
                        >
                            <p
                                class="text-[11px] leading-relaxed text-slate-500 dark:text-gray-400"
                            >
                                ফোরামের মানসম্মত পরিবেশ ও প্রাসঙ্গিকতা বজায়
                                রাখতে অনুগ্রহ করে নিচের বিষয়গুলো যাচাই করুন:
                            </p>

                            <div
                                class="space-y-2 rounded-xl bg-slate-50 p-3.5 dark:bg-gray-800/50"
                            >
                                <div class="flex items-start gap-2.5">
                                    <Search
                                        class="mt-0.5 h-3.5 w-3.5 shrink-0 text-indigo-600 dark:text-indigo-400"
                                    />
                                    <span
                                        >আমি পূর্বে ফোরামে এই সম্পর্কিত প্রশ্ন
                                        অনুসন্ধান করে দেখেছি এবং কোনো সমাধান
                                        পাওয়া যায়নি।</span
                                    >
                                </div>
                                <div class="flex items-start gap-2.5">
                                    <FileText
                                        class="mt-0.5 h-3.5 w-3.5 shrink-0 text-indigo-600 dark:text-indigo-400"
                                    />
                                    <span
                                        >প্রশ্নটি সুস্পষ্ট এবং প্রয়োজনীয় তথ্যসহ
                                        বিস্তারিতভাবে তুলে ধরা হয়েছে।</span
                                    >
                                </div>
                                <div class="flex items-start gap-2.5">
                                    <CheckCircle
                                        class="mt-0.5 h-3.5 w-3.5 shrink-0 text-emerald-600 dark:text-emerald-400"
                                    />
                                    <span
                                        >এটি কোনো অপ্রাসঙ্গিক বিষয়বস্তু,
                                        ডুপ্লিকেট পোস্ট বা স্প্যাম নয়।</span
                                    >
                                </div>
                            </div>

                            <!-- Interactive Checkbox -->
                            <label
                                class="flex cursor-pointer items-start gap-3 rounded-xl border border-indigo-100 bg-indigo-50/50 p-3 transition select-none hover:bg-indigo-50 dark:border-indigo-950/60 dark:bg-indigo-950/20 dark:hover:bg-indigo-950/30"
                            >
                                <input
                                    type="checkbox"
                                    v-model="modalConfirmed"
                                    class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-offset-gray-900"
                                />
                                <span
                                    class="font-bold text-slate-900 dark:text-gray-100"
                                >
                                    আমি নিশ্চিত করছি যে উপরের সকল নির্দেশিকা
                                    মেনে প্রশ্নটি করছি।
                                </span>
                            </label>
                        </div>

                        <!-- Modal Actions Footer -->
                        <div
                            class="mt-5 flex items-center justify-end gap-2.5 border-t border-slate-100 pt-4 dark:border-gray-800"
                        >
                            <button
                                type="button"
                                @click="showConfirmModal = false"
                                class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800"
                            >
                                Back & Edit
                            </button>
                            <button
                                type="button"
                                @click="submit"
                                :disabled="
                                    !modalConfirmed ||
                                    form.processing ||
                                    isCompressingImage
                                "
                                class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-indigo-700 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <Loader2
                                    v-if="form.processing"
                                    class="h-3.5 w-3.5 animate-spin"
                                />
                                <Send v-else class="h-3.5 w-3.5" />
                                <span>{{
                                    form.processing
                                        ? 'Posting...'
                                        : 'Confirm & Post'
                                }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </main>
</template>
