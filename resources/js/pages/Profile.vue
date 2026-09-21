<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    Loader2,
    Save,
    User,
    Globe,
    Image as ImageIcon,
    Sparkles,
    ArrowRight,
    AlertTriangle,
    LifeBuoy,
    Shield,
    Users,
    Lock,
    Zap,
    Mail,
    GraduationCap,
    Trash2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import BaseModal from '@/components/BaseModal.vue';

import { compressImage } from '@/lib/imageCompression';

const props = defineProps({
    user: Object,
});

const page = usePage();
const user = computed(() => props.user || page.props.auth?.user);

const isUnverified = computed(() => {
    return !user.value?.is_verified;
});

const showCurriculumConfirmModal = ref(false);
const pendingCurriculum = ref<'hsc' | 'ssc'>('hsc');
const isSwitchingCurriculum = ref(false);
const isCompressingAvatar = ref(false);
const avatarPreview = ref<string | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);

const currentCurriculum = computed(() => user.value?.curriculum || 'hsc');

const form = useForm({
    _method: 'PUT',
    name: user.value?.name || '',
    username: user.value?.username || '',
    file: null as File | null,
    clear_image: false,
    about: user.value?.about || '',
    institution: user.value?.institution || '',
    activity_privacy: user.value?.activity_privacy || 'public',
    allow_pokes: (user.value as any)?.allow_pokes ?? true,
    facebook: user.value?.facebook || '',
    github: user.value?.github || '',
    instagram: user.value?.instagram || '',
    receive_emails: user.value?.receive_emails ?? true,
});

const handleAvatarSelect = async (event: Event) => {
    const input = event.target as HTMLInputElement;

    if (input.files && input.files[0]) {
        const raw = input.files[0];
        form.errors.file = '';

        const allowed = ['image/jpeg', 'image/png', 'image/webp'];

        if (!allowed.includes(raw.type)) {
            form.errors.file = 'অনুমোদিত ফরম্যাট: JPG, PNG, WEBP।';

            return;
        }

        try {
            isCompressingAvatar.value = true;
            let resultFile: File;

            try {
                resultFile = await compressImage(raw, {
                    maxWidth: 512,
                    maxHeight: 512,
                    quality: 0.85,
                });
            } catch {
                resultFile = raw;
            }

            if (resultFile.size > 5 * 1024 * 1024) {
                form.errors.file = 'ছবিটির আকার ৫MB এর চেয়ে কম হতে হবে।';
                form.file = null;

                return;
            }

            form.file = resultFile;
            form.clear_image = false;

            if (avatarPreview.value) {
                URL.revokeObjectURL(avatarPreview.value);
            }

            avatarPreview.value = URL.createObjectURL(resultFile);
        } finally {
            isCompressingAvatar.value = false;
        }
    }
};

const handleRemoveAvatar = () => {
    form.file = null;
    form.errors.file = '';

    if (avatarPreview.value) {
        URL.revokeObjectURL(avatarPreview.value);
        avatarPreview.value = null;
    }

    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }

    if (user.value?.image_url) {
        form.clear_image = true;
    }
};

const handleSelectCurriculum = (target: 'hsc' | 'ssc') => {
    if (target === currentCurriculum.value) {
        return;
    }

    pendingCurriculum.value = target;
    showCurriculumConfirmModal.value = true;
};

const confirmSwitchCurriculum = () => {
    isSwitchingCurriculum.value = true;
    router.post(
        '/tracker/curriculum',
        { curriculum: pendingCurriculum.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                showCurriculumConfirmModal.value = false;
            },
            onFinish: () => {
                isSwitchingCurriculum.value = false;
            },
        },
    );
};

const submitForm = () => {
    if (isCompressingAvatar.value) {
        return;
    }

    form.post('/profile');
};
</script>

<template>
    <Head>
        <title>Account Settings & Profile</title>
        <meta
            name="description"
            content="Manage your personal profile information, social links, and account settings on HSCStack."
        />
    </Head>

    <div class="mx-auto w-full max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1
                class="text-2xl font-bold text-slate-900 sm:text-3xl dark:text-gray-100"
            >
                Account Settings
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-gray-400">
                Manage your personal profile information, social links, and
                security settings.
            </p>
        </div>

        <!-- Be a Contributor Section (Only for unverified users) -->
        <div
            v-if="isUnverified"
            class="mb-8 overflow-hidden rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50/70 via-white to-violet-50/40 p-6 sm:p-8 dark:border-indigo-500/20 dark:from-gray-900 dark:via-gray-900 dark:to-indigo-950/20"
        >
            <div
                class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="space-y-1.5">
                    <div class="flex items-center gap-2">
                        <div
                            class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-600 text-white shadow-xs dark:bg-indigo-500"
                        >
                            <Sparkles class="h-4 w-4" />
                        </div>
                        <h2
                            class="text-base font-bold text-slate-900 dark:text-gray-100"
                        >
                            Be a Contributor
                        </h2>
                    </div>
                    <p
                        class="max-w-xl text-xs leading-relaxed text-slate-600 dark:text-gray-400"
                    >
                        আমাদের টিমে Resource Curator, Developer বা Campus
                        Promoter হিসেবে যুক্ত হতে চান? আপনার দক্ষতা ও আগ্রহ দিয়ে
                        HSCStack-কে আরও সমৃদ্ধ করতে আবেদন করুন।
                    </p>
                </div>

                <Link
                    href="/join"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-xs transition-all hover:bg-indigo-700 hover:shadow-md active:scale-[0.98] dark:bg-indigo-500 dark:hover:bg-indigo-600"
                >
                    <span>Apply to Join Team</span>
                    <ArrowRight class="h-3.5 w-3.5" />
                </Link>
            </div>
        </div>

        <form @submit.prevent="submitForm" class="space-y-8">
            <!-- Profile Info Card -->
            <div
                class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs sm:p-8 dark:border-gray-700 dark:bg-gray-900"
            >
                <div
                    class="mb-6 flex items-center gap-3 border-b border-slate-100 pb-4 dark:border-gray-800"
                >
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400"
                    >
                        <User class="h-5 w-5" />
                    </div>
                    <div>
                        <h2
                            class="text-base font-semibold text-slate-900 dark:text-gray-100"
                        >
                            Personal Information
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-gray-400">
                            Your basic identification and bio.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label
                            for="name"
                            class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                        >
                            Full Name
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            id="name"
                            required
                            placeholder="Your full name"
                            :disabled="form.processing"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 transition outline-none placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 disabled:bg-slate-50 dark:border-gray-600 dark:bg-gray-950 dark:text-gray-100 dark:placeholder:text-gray-500"
                            :class="{
                                'border-rose-500 focus:ring-rose-500/20':
                                    form.errors.name,
                            }"
                        />
                        <p
                            v-if="form.errors.name"
                            class="mt-1 text-xs text-rose-600"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="username"
                            class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                        >
                            Username
                        </label>
                        <div class="relative">
                            <span
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm font-semibold text-slate-400 dark:text-gray-500"
                            >
                                @
                            </span>
                            <input
                                v-model="form.username"
                                type="text"
                                id="username"
                                required
                                placeholder="your_handle"
                                :disabled="form.processing"
                                class="w-full rounded-lg border border-slate-300 bg-white py-2.5 pr-3.5 pl-8 text-sm text-slate-900 transition outline-none placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 disabled:bg-slate-50 dark:border-gray-600 dark:bg-gray-950 dark:text-gray-100 dark:placeholder:text-gray-500"
                                :class="{
                                    'border-rose-500 focus:ring-rose-500/20':
                                        form.errors.username,
                                }"
                            />
                        </div>
                        <p
                            v-if="form.errors.username"
                            class="mt-1 text-xs text-rose-600"
                        >
                            {{ form.errors.username }}
                        </p>
                        <p
                            v-else
                            class="mt-1 text-[11px] text-slate-400 dark:text-gray-500"
                        >
                            Letters, numbers, and underscores (3–30 chars).
                        </p>
                    </div>

                    <div>
                        <label
                            for="email"
                            class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                        >
                            Email Address
                            <span
                                class="font-normal text-slate-400 dark:text-gray-500"
                                >(Cannot be changed)</span
                            >
                        </label>
                        <input
                            :value="user?.email"
                            type="email"
                            id="email"
                            disabled
                            class="w-full cursor-not-allowed rounded-lg border border-slate-200 bg-slate-100 px-3.5 py-2.5 text-sm text-slate-500 transition outline-none dark:border-gray-700 dark:bg-gray-800/60 dark:text-gray-400"
                        />
                    </div>

                    <div class="sm:col-span-2">
                        <label
                            for="institution"
                            class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                        >
                            Institution
                        </label>
                        <input
                            v-model="form.institution"
                            type="text"
                            id="institution"
                            placeholder="e.g., Rangpur Zilla School"
                            :disabled="form.processing"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 transition outline-none placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 disabled:bg-slate-50 dark:border-gray-600 dark:bg-gray-950 dark:text-gray-100 dark:placeholder:text-gray-500"
                            :class="{
                                'border-rose-500 focus:ring-rose-500/20':
                                    form.errors.institution,
                            }"
                        />
                        <p
                            v-if="form.errors.institution"
                            class="mt-1 text-xs text-rose-600"
                        >
                            {{ form.errors.institution }}
                        </p>
                    </div>

                    <div class="sm:col-span-2">
                        <div class="mb-1.5 flex items-center justify-between">
                            <label
                                for="about"
                                class="block text-xs font-semibold text-slate-700 dark:text-gray-300"
                            >
                                About / Bio
                            </label>
                            <span
                                class="text-xs text-slate-400 dark:text-gray-500"
                            >
                                {{ form.about?.length || 0 }}/255
                            </span>
                        </div>
                        <textarea
                            v-model="form.about"
                            id="about"
                            rows="3"
                            maxlength="255"
                            placeholder="Tell us a little bit about yourself..."
                            :disabled="form.processing"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 transition outline-none placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 disabled:bg-slate-50 dark:border-gray-600 dark:bg-gray-950 dark:text-gray-100 dark:placeholder:text-gray-500"
                            :class="{
                                'border-rose-500 focus:ring-rose-500/20':
                                    form.errors.about,
                            }"
                        ></textarea>
                        <p
                            v-if="form.errors.about"
                            class="mt-1 text-xs text-rose-600"
                        >
                            {{ form.errors.about }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Curriculum & Target Syllabus Card -->
            <div
                class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs sm:p-8 dark:border-gray-700 dark:bg-gray-900"
            >
                <div
                    class="mb-6 flex flex-col gap-2 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400"
                        >
                            <GraduationCap class="h-5 w-5" />
                        </div>
                        <div>
                            <h2
                                class="text-base font-semibold text-slate-900 dark:text-gray-100"
                            >
                                Academic Curriculum
                            </h2>
                            <p
                                class="text-xs text-slate-500 dark:text-gray-400"
                            >
                                আপনার পড়ার কারিকুলাম লেভেল নির্বাচন করুন
                            </p>
                        </div>
                    </div>

                    <span
                        class="inline-flex items-center self-start rounded-lg bg-indigo-50 px-2.5 py-1 text-xs font-bold tracking-wide text-indigo-700 uppercase sm:self-auto dark:bg-indigo-950/60 dark:text-indigo-300"
                    >
                        Active: {{ currentCurriculum }}
                    </span>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- HSC Option -->
                    <div
                        @click="handleSelectCurriculum('hsc')"
                        class="group relative flex cursor-pointer items-center justify-between rounded-2xl border p-4 transition-all duration-150"
                        :class="[
                            currentCurriculum === 'hsc'
                                ? 'border-indigo-600 bg-indigo-50/40 ring-1 ring-indigo-600 dark:border-indigo-500 dark:bg-indigo-950/30'
                                : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/60 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-gray-700 dark:hover:bg-gray-800/40',
                        ]"
                    >
                        <div>
                            <span
                                class="text-base font-bold text-slate-900 dark:text-gray-100"
                            >
                                HSC
                            </span>
                            <p
                                class="text-xs text-slate-500 dark:text-gray-400"
                            >
                                Class 11–12
                            </p>
                        </div>
                        <span
                            v-if="currentCurriculum === 'hsc'"
                            class="flex h-5 w-5 items-center justify-center rounded-full bg-indigo-600 text-white dark:bg-indigo-500"
                        >
                            <span class="text-[10px] font-bold">✓</span>
                        </span>
                    </div>

                    <!-- SSC Option -->
                    <div
                        @click="handleSelectCurriculum('ssc')"
                        class="group relative flex cursor-pointer items-center justify-between rounded-2xl border p-4 transition-all duration-150"
                        :class="[
                            currentCurriculum === 'ssc'
                                ? 'border-amber-600 bg-amber-50/40 ring-1 ring-amber-600 dark:border-amber-500 dark:bg-amber-950/30'
                                : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/60 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-gray-700 dark:hover:bg-gray-800/40',
                        ]"
                    >
                        <div>
                            <span
                                class="text-base font-bold text-slate-900 dark:text-gray-100"
                            >
                                SSC
                            </span>
                            <p
                                class="text-xs text-slate-500 dark:text-gray-400"
                            >
                                Class 9–10
                            </p>
                        </div>
                        <span
                            v-if="currentCurriculum === 'ssc'"
                            class="flex h-5 w-5 items-center justify-center rounded-full bg-amber-600 text-white dark:bg-amber-500"
                        >
                            <span class="text-[10px] font-bold">✓</span>
                        </span>
                    </div>
                </div>

                <p class="mt-3 text-[11px] text-slate-400 dark:text-gray-500">
                    * কারিকুলাম পরিবর্তন করলে স্টাডি ট্র্যাকারের অগ্রগতি রিসেট
                    হবে।
                </p>
            </div>

            <!-- Profile Photo Card -->
            <div
                class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs sm:p-8 dark:border-gray-700 dark:bg-gray-900"
            >
                <div
                    class="mb-6 flex items-center gap-3 border-b border-slate-100 pb-4 dark:border-gray-800"
                >
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400"
                    >
                        <ImageIcon class="h-5 w-5" />
                    </div>
                    <div>
                        <h2
                            class="text-base font-semibold text-slate-900 dark:text-gray-100"
                        >
                            Profile Picture
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-gray-400">
                            Update your avatar displayed across the platform.
                        </p>
                    </div>
                </div>

                <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
                    <div class="relative shrink-0">
                        <img
                            v-if="
                                (avatarPreview ||
                                    (user?.image_url && !form.clear_image)) &&
                                !isCompressingAvatar
                            "
                            :src="avatarPreview || user.image_url"
                            :alt="user.name"
                            class="h-20 w-20 rounded-full border-2 border-slate-200 object-cover shadow-xs dark:border-gray-700"
                        />
                        <div
                            v-else-if="!isCompressingAvatar"
                            class="flex h-20 w-20 items-center justify-center rounded-full bg-slate-900 text-xl font-bold text-white dark:bg-gray-800"
                        >
                            {{ user?.name?.charAt(0)?.toUpperCase() }}
                        </div>
                        <div
                            v-if="isCompressingAvatar"
                            class="flex h-20 w-20 items-center justify-center rounded-full border-2 border-dashed border-indigo-400 bg-indigo-50 dark:bg-indigo-950/40"
                        >
                            <Loader2
                                class="h-6 w-6 animate-spin text-indigo-600 dark:text-indigo-400"
                            />
                        </div>
                    </div>

                    <div class="flex-1">
                        <div class="mb-1.5 flex items-center justify-between">
                            <label
                                for="file"
                                class="block text-xs font-semibold text-slate-700 dark:text-gray-300"
                            >
                                Upload New Photo
                            </label>
                            <button
                                v-if="
                                    (user?.image_url && !form.clear_image) ||
                                    form.file
                                "
                                type="button"
                                @click="handleRemoveAvatar"
                                :disabled="
                                    form.processing || isCompressingAvatar
                                "
                                class="cursor-pointer text-xs font-semibold text-rose-600 hover:text-rose-700 hover:underline disabled:opacity-50 dark:text-rose-400 dark:hover:text-rose-300"
                            >
                                {{
                                    form.file && !user?.image_url
                                        ? 'Clear Selected'
                                        : 'Remove Photo'
                                }}
                            </button>
                        </div>
                        <input
                            ref="fileInputRef"
                            type="file"
                            id="file"
                            accept="image/jpeg,image/png,image/webp"
                            @change="handleAvatarSelect"
                            :disabled="form.processing || isCompressingAvatar"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-500 transition outline-none file:mr-4 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-700 file:transition hover:file:bg-slate-200 disabled:bg-slate-50 dark:border-gray-600 dark:bg-gray-950 dark:text-gray-400 dark:file:bg-gray-800 dark:file:text-gray-300 dark:hover:file:bg-gray-700"
                            :class="{
                                'border-rose-500 focus:ring-rose-500/20':
                                    form.errors.file,
                            }"
                        />
                        <p
                            class="mt-1 text-xs text-slate-400 dark:text-gray-500"
                        >
                            {{
                                isCompressingAvatar
                                    ? 'Optimizing avatar...'
                                    : form.clear_image
                                      ? 'Photo will be removed upon saving.'
                                      : 'Supports PNG, JPG, or WEBP up to 5MB (auto-optimized).'
                            }}
                        </p>
                        <p
                            v-if="form.errors.file"
                            class="mt-1 text-xs text-rose-600"
                        >
                            {{ form.errors.file }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Social Links Card -->
            <div
                class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs sm:p-8 dark:border-gray-700 dark:bg-gray-900"
            >
                <div
                    class="mb-6 flex items-center gap-3 border-b border-slate-100 pb-4 dark:border-gray-800"
                >
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400"
                    >
                        <Globe class="h-5 w-5" />
                    </div>
                    <div>
                        <h2
                            class="text-base font-semibold text-slate-900 dark:text-gray-100"
                        >
                            Social Profiles
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-gray-400">
                            Connect your social accounts.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <div>
                        <label
                            for="facebook"
                            class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                        >
                            Facebook
                        </label>
                        <input
                            v-model="form.facebook"
                            type="text"
                            id="facebook"
                            placeholder="https://facebook.com/username"
                            :disabled="form.processing"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 transition outline-none placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 disabled:bg-slate-50 dark:border-gray-600 dark:bg-gray-950 dark:text-gray-100 dark:placeholder:text-gray-500"
                            :class="{
                                'border-rose-500 focus:ring-rose-500/20':
                                    form.errors.facebook,
                            }"
                        />
                        <p
                            v-if="form.errors.facebook"
                            class="mt-1 text-xs text-rose-600"
                        >
                            {{ form.errors.facebook }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="github"
                            class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                        >
                            GitHub
                        </label>
                        <input
                            v-model="form.github"
                            type="text"
                            id="github"
                            placeholder="https://github.com/username"
                            :disabled="form.processing"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 transition outline-none placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 disabled:bg-slate-50 dark:border-gray-600 dark:bg-gray-950 dark:text-gray-100 dark:placeholder:text-gray-500"
                            :class="{
                                'border-rose-500 focus:ring-rose-500/20':
                                    form.errors.github,
                            }"
                        />
                        <p
                            v-if="form.errors.github"
                            class="mt-1 text-xs text-rose-600"
                        >
                            {{ form.errors.github }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="instagram"
                            class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                        >
                            Instagram
                        </label>
                        <input
                            v-model="form.instagram"
                            type="text"
                            id="instagram"
                            placeholder="https://instagram.com/username"
                            :disabled="form.processing"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 transition outline-none placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 disabled:bg-slate-50 dark:border-gray-600 dark:bg-gray-950 dark:text-gray-100 dark:placeholder:text-gray-500"
                            :class="{
                                'border-rose-500 focus:ring-rose-500/20':
                                    form.errors.instagram,
                            }"
                        />
                        <p
                            v-if="form.errors.instagram"
                            class="mt-1 text-xs text-rose-600"
                        >
                            {{ form.errors.instagram }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Activity Privacy Settings Card -->
            <div
                class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs sm:p-8 dark:border-gray-700 dark:bg-gray-900"
            >
                <div
                    class="mb-6 flex items-center gap-3 border-b border-slate-100 pb-4 dark:border-gray-800"
                >
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                    >
                        <Shield class="h-5 w-5" />
                    </div>
                    <div>
                        <h2
                            class="text-base font-semibold text-slate-900 dark:text-gray-100"
                        >
                            Activity & Profile Privacy
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-gray-400">
                            আপনার প্রোফাইলে ফোরাম প্রশ্ন, উত্তর, পড়ালেখার
                            অগ্রগতি এবং আর্টিকেল কারা দেখতে পারবে তা নির্ধারণ
                            করুন।
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <!-- Public Option -->
                    <label
                        class="relative flex cursor-pointer flex-col rounded-xl border p-4 transition"
                        :class="
                            form.activity_privacy === 'public'
                                ? 'border-indigo-600 bg-indigo-50/40 ring-2 ring-indigo-600/20 dark:border-indigo-500 dark:bg-indigo-950/30'
                                : 'border-slate-200 hover:border-slate-300 dark:border-gray-800 dark:hover:border-gray-700'
                        "
                    >
                        <input
                            type="radio"
                            v-model="form.activity_privacy"
                            value="public"
                            class="sr-only"
                        />
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Globe
                                    class="h-4 w-4"
                                    :class="
                                        form.activity_privacy === 'public'
                                            ? 'text-indigo-600 dark:text-indigo-400'
                                            : 'text-slate-400 dark:text-gray-500'
                                    "
                                />
                                <span
                                    class="text-xs font-bold text-slate-900 dark:text-gray-100"
                                >
                                    Public
                                </span>
                            </div>
                            <span
                                v-if="form.activity_privacy === 'public'"
                                class="h-2 w-2 rounded-full bg-indigo-600 dark:bg-indigo-400"
                            />
                        </div>
                        <p
                            class="mt-2 text-[11px] leading-relaxed text-slate-500 dark:text-gray-400"
                        >
                            যে কেউ আপনার অ্যাক্টিভিটি দেখতে পারবেন।
                        </p>
                    </label>

                    <!-- Appreciators Only Option -->
                    <label
                        class="relative flex cursor-pointer flex-col rounded-xl border p-4 transition"
                        :class="
                            form.activity_privacy === 'appreciators_only'
                                ? 'border-indigo-600 bg-indigo-50/40 ring-2 ring-indigo-600/20 dark:border-indigo-500 dark:bg-indigo-950/30'
                                : 'border-slate-200 hover:border-slate-300 dark:border-gray-800 dark:hover:border-gray-700'
                        "
                    >
                        <input
                            type="radio"
                            v-model="form.activity_privacy"
                            value="appreciators_only"
                            class="sr-only"
                        />
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Users
                                    class="h-4 w-4"
                                    :class="
                                        form.activity_privacy ===
                                        'appreciators_only'
                                            ? 'text-indigo-600 dark:text-indigo-400'
                                            : 'text-slate-400 dark:text-gray-500'
                                    "
                                />
                                <span
                                    class="text-xs font-bold text-slate-900 dark:text-gray-100"
                                >
                                    Appreciators Only
                                </span>
                            </div>
                            <span
                                v-if="
                                    form.activity_privacy ===
                                    'appreciators_only'
                                "
                                class="h-2 w-2 rounded-full bg-indigo-600 dark:bg-indigo-400"
                            />
                        </div>
                        <p
                            class="mt-2 text-[11px] leading-relaxed text-slate-500 dark:text-gray-400"
                        >
                            শুধুমাত্র যারা আপনার প্রোফাইল অ্যাপ্রিশিয়েট করেছেন
                            তারা দেখতে পারবেন।
                        </p>
                    </label>

                    <!-- Private Option -->
                    <label
                        class="relative flex cursor-pointer flex-col rounded-xl border p-4 transition"
                        :class="
                            form.activity_privacy === 'private'
                                ? 'border-indigo-600 bg-indigo-50/40 ring-2 ring-indigo-600/20 dark:border-indigo-500 dark:bg-indigo-950/30'
                                : 'border-slate-200 hover:border-slate-300 dark:border-gray-800 dark:hover:border-gray-700'
                        "
                    >
                        <input
                            type="radio"
                            v-model="form.activity_privacy"
                            value="private"
                            class="sr-only"
                        />
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Lock
                                    class="h-4 w-4"
                                    :class="
                                        form.activity_privacy === 'private'
                                            ? 'text-indigo-600 dark:text-indigo-400'
                                            : 'text-slate-400 dark:text-gray-500'
                                    "
                                />
                                <span
                                    class="text-xs font-bold text-slate-900 dark:text-gray-100"
                                >
                                    Private
                                </span>
                            </div>
                            <span
                                v-if="form.activity_privacy === 'private'"
                                class="h-2 w-2 rounded-full bg-indigo-600 dark:bg-indigo-400"
                            />
                        </div>
                        <p
                            class="mt-2 text-[11px] leading-relaxed text-slate-500 dark:text-gray-400"
                        >
                            আপনার অ্যাক্টিভিটি ও অগ্রগতি শুধুমাত্র আপনি নিজে
                            দেখতে পারবেন।
                        </p>
                    </label>
                </div>

                <div
                    class="mt-6 border-t border-slate-100 pt-6 dark:border-gray-800"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div class="space-y-0.5">
                            <div class="flex items-center gap-2">
                                <Zap
                                    class="h-4 w-4 text-amber-500 dark:text-amber-400"
                                />
                                <span
                                    class="text-xs font-bold text-slate-900 dark:text-gray-100"
                                >
                                    Receive Pokes
                                </span>
                            </div>
                            <p
                                class="text-[11px] text-slate-500 dark:text-gray-400"
                            >
                                সহপাঠীদের থেকে Poke পেতে এই অপশনটি অন রাখুন।
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="form.allow_pokes = !form.allow_pokes"
                            :disabled="form.processing"
                            class="relative inline-flex shrink-0 cursor-pointer items-center focus:outline-none"
                            :aria-checked="form.allow_pokes"
                            aria-label="Receive pokes"
                            role="switch"
                        >
                            <div
                                class="h-5 w-9 rounded-full transition-colors after:absolute after:top-[2px] after:left-[2px] after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow-sm after:transition-all after:content-[''] dark:after:bg-gray-200"
                                :class="
                                    form.allow_pokes
                                        ? 'bg-amber-500 after:translate-x-4 dark:bg-amber-600'
                                        : 'bg-slate-300 dark:bg-gray-700'
                                "
                            ></div>
                        </button>
                    </div>

                    <!-- Email Notifications -->
                    <div
                        class="mt-6 border-t border-slate-100 pt-6 dark:border-gray-800"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-2">
                                    <Mail
                                        class="h-4 w-4 text-indigo-600 dark:text-indigo-400"
                                    />
                                    <span
                                        class="text-xs font-bold text-slate-900 dark:text-gray-100"
                                    >
                                        ইমেইল নোটিফিকেশন
                                    </span>
                                </div>
                                <p
                                    class="text-[11px] text-slate-500 dark:text-gray-400"
                                >
                                    গুরুত্বপূর্ণ আপডেট, নোটিশ ও পড়াশোনা
                                    সংক্রান্ত প্রয়োজনীয় তথ্য ইমেইলে পেতে এই
                                    অপশনটি অন রাখুন।
                                </p>
                            </div>

                            <button
                                type="button"
                                @click="
                                    form.receive_emails = !form.receive_emails
                                "
                                :disabled="form.processing"
                                class="relative inline-flex shrink-0 cursor-pointer items-center focus:outline-none"
                                :aria-checked="form.receive_emails"
                                aria-label="Receive email notifications"
                                role="switch"
                            >
                                <div
                                    class="h-5 w-9 rounded-full transition-colors after:absolute after:top-[2px] after:left-[2px] after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow-sm after:transition-all after:content-[''] dark:after:bg-gray-200"
                                    :class="
                                        form.receive_emails
                                            ? 'bg-indigo-600 after:translate-x-4 dark:bg-indigo-500'
                                            : 'bg-slate-300 dark:bg-gray-700'
                                    "
                                ></div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Support Center Link -->
            <div
                class="flex flex-col items-start justify-between gap-4 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs sm:flex-row sm:items-center dark:border-gray-700 dark:bg-gray-900"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                    >
                        <LifeBuoy class="h-5 w-5" />
                    </div>
                    <div>
                        <h3
                            class="text-sm font-semibold text-slate-900 dark:text-gray-100"
                        >
                            Help & Support Center
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-gray-400">
                            কোনো সমস্যা, প্রশ্ন বা ফিডব্যাকের জন্য সরাসরি
                            সাপোর্ট টিকেট খুলুন।
                        </p>
                    </div>
                </div>

                <Link
                    href="/support"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50/80 px-4 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-100 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    <LifeBuoy
                        class="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400"
                    />
                    <span>Support Center</span>
                    <ArrowRight class="h-3.5 w-3.5 text-slate-400" />
                </Link>
            </div>

            <!-- Danger Zone / Delete Account Link -->
            <div
                class="flex flex-col items-start justify-between gap-4 rounded-2xl border border-rose-200/70 bg-rose-50/30 p-5 shadow-xs sm:flex-row sm:items-center dark:border-rose-900/30 dark:bg-rose-950/10"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-100 text-rose-600 dark:bg-rose-500/15 dark:text-rose-400"
                    >
                        <Trash2 class="h-5 w-5" />
                    </div>
                    <div>
                        <h3
                            class="text-sm font-semibold text-rose-900 dark:text-rose-200"
                        >
                            Delete Account
                        </h3>
                        <p
                            class="text-xs text-rose-700/80 dark:text-rose-400/80"
                        >
                            অ্যাকাউন্ট ও সংশ্লিষ্ট ডেটা স্থায়ীভাবে মুছে ফেলার
                            অনুরোধ করুন।
                        </p>
                    </div>
                </div>

                <Link
                    href="/account/delete"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-rose-200 bg-white px-4 py-2 text-xs font-bold text-rose-600 transition hover:bg-rose-50 dark:border-rose-900/50 dark:bg-gray-900 dark:text-rose-400 dark:hover:bg-rose-950/30"
                >
                    <Trash2 class="h-3.5 w-3.5" />
                    <span>Delete Account</span>
                    <ArrowRight class="h-3.5 w-3.5 text-rose-400" />
                </Link>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end pt-2">
                <button
                    type="submit"
                    :disabled="form.processing || isCompressingAvatar"
                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-xs transition hover:bg-blue-700 focus:ring-4 focus:ring-blue-600/20 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <Loader2
                        v-if="form.processing"
                        class="h-4 w-4 animate-spin"
                    />
                    <Save v-else class="h-4 w-4" />
                    {{ form.processing ? 'Saving...' : 'Save Changes' }}
                </button>
            </div>
        </form>

        <!-- Switch Curriculum Confirmation Modal -->
        <BaseModal
            :is-open="showCurriculumConfirmModal"
            title="কারিকুলাম পরিবর্তন করবেন?"
            description="সতর্কতা: আপনার সিলেবাস ট্র্যাকার রিসেট হবে"
            max-width="md"
            @close="showCurriculumConfirmModal = false"
        >
            <div class="space-y-4 p-5 text-slate-800 dark:text-gray-200">
                <div
                    class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50/80 p-3.5 text-xs text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-200"
                >
                    <AlertTriangle
                        class="h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400"
                    />
                    <div class="space-y-1">
                        <p class="font-bold">সতর্কতা</p>
                        <p class="leading-relaxed">
                            কারিকুলাম পরিবর্তন করলে স্টাডি ট্র্যাকারের টিক দেওয়া
                            সকল অধ্যায়ের অগ্রগতি রিসেট হয়ে যাবে।
                        </p>
                    </div>
                </div>

                <p class="text-xs text-slate-500 dark:text-gray-400">
                    আপনি কি নিশ্চিতভাবে কারিকুলাম
                    <span
                        class="font-bold text-slate-800 uppercase dark:text-gray-200"
                        >{{ pendingCurriculum }}</span
                    >
                    এ পরিবর্তন করতে চান?
                </p>
            </div>

            <div
                class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-5 py-3 dark:border-gray-800 dark:bg-gray-900/60"
            >
                <button
                    type="button"
                    @click="showCurriculumConfirmModal = false"
                    :disabled="isSwitchingCurriculum"
                    class="cursor-pointer rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    বাতিল
                </button>
                <button
                    type="button"
                    @click="confirmSwitchCurriculum"
                    :disabled="isSwitchingCurriculum"
                    class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-2xs transition hover:bg-indigo-700 active:scale-95 disabled:opacity-50"
                >
                    <Loader2
                        v-if="isSwitchingCurriculum"
                        class="h-3.5 w-3.5 animate-spin"
                    />
                    <span>{{
                        isSwitchingCurriculum
                            ? 'পরিবর্তন হচ্ছে...'
                            : 'হ্যাঁ, পরিবর্তন করুন'
                    }}</span>
                </button>
            </div>
        </BaseModal>
    </div>
</template>
