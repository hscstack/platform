<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    Loader2,
    Save,
    User,
    Globe,
    Image as ImageIcon,
    Sparkles,
    ArrowRight,
    AlertTriangle,
    ChevronDown,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    user: Object,
});

const page = usePage();
const user = computed(() => props.user || page.props.auth?.user);

const hasNoRole = computed(() => {
    const roles = user.value?.roles;

    return !roles || roles.length === 0;
});

const showAdvancedSettings = ref(false);
const showConfirmModal = ref(false);

const form = useForm({
    _method: 'PUT',
    name: user.value?.name || '',
    username: user.value?.username || '',
    file: null as File | null,
    about: user.value?.about || '',
    institution: user.value?.institution || '',
    facebook: user.value?.facebook || '',
    github: user.value?.github || '',
    instagram: user.value?.instagram || '',
    receive_emails: user.value?.receive_emails ?? true,
});

const handleEmailToggle = () => {
    if (form.receive_emails) {
        showConfirmModal.value = true;
    } else {
        form.receive_emails = true;
    }
};

const confirmDisable = () => {
    form.receive_emails = false;
    showConfirmModal.value = false;
};

const submitForm = () => {
    form.post('/profile', {
        preserveScroll: true,
    });
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

        <!-- Be a Contributor Section (Only for users without roles) -->
        <div
            v-if="hasNoRole"
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
                            Institution / Organization
                        </label>
                        <input
                            v-model="form.institution"
                            type="text"
                            id="institution"
                            placeholder="e.g., Dhaka College, BUET"
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
                        <label
                            for="about"
                            class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                        >
                            About / Bio
                        </label>
                        <textarea
                            v-model="form.about"
                            id="about"
                            rows="3"
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
                    <div class="shrink-0">
                        <img
                            v-if="user?.image_url"
                            :src="user.image_url"
                            :alt="user.name"
                            class="h-20 w-20 rounded-full border-2 border-slate-200 object-cover shadow-xs dark:border-gray-700"
                        />
                        <div
                            v-else
                            class="flex h-20 w-20 items-center justify-center rounded-full bg-slate-900 text-xl font-bold text-white dark:bg-gray-800"
                        >
                            {{ user?.name?.charAt(0)?.toUpperCase() }}
                        </div>
                    </div>

                    <div class="flex-1">
                        <label
                            for="file"
                            class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                        >
                            Upload New Photo
                        </label>
                        <input
                            type="file"
                            id="file"
                            accept="image/*"
                            @change="
                                form.file =
                                    ($event.target as HTMLInputElement)
                                        .files?.[0] || null
                            "
                            :disabled="form.processing"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-500 transition outline-none file:mr-4 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-700 file:transition hover:file:bg-slate-200 disabled:bg-slate-50 dark:border-gray-600 dark:bg-gray-950 dark:text-gray-400 dark:file:bg-gray-800 dark:file:text-gray-300 dark:hover:file:bg-gray-700"
                            :class="{
                                'border-rose-500 focus:ring-rose-500/20':
                                    form.errors.file,
                            }"
                        />
                        <p
                            class="mt-1 text-xs text-slate-400 dark:text-gray-500"
                        >
                            Supports PNG, JPG, or WEBP up to 2MB.
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

            <!-- Advanced Notification Preferences (Collapsible Dropdown) -->
            <div
                class="rounded-xl border border-slate-200/60 bg-slate-50/40 p-3 sm:p-4 dark:border-gray-800 dark:bg-gray-900/30"
            >
                <button
                    type="button"
                    @click="showAdvancedSettings = !showAdvancedSettings"
                    class="flex w-full items-center justify-between text-left text-xs font-medium text-slate-500 transition hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-200"
                >
                    <div class="flex items-center gap-2">
                        <span>Advanced Notification Preferences</span>
                        <span
                            v-if="!form.receive_emails"
                            class="inline-flex items-center rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-950/60 dark:text-amber-300"
                        >
                            Updates Paused
                        </span>
                    </div>
                    <ChevronDown
                        class="h-3.5 w-3.5 text-slate-400 transition-transform duration-200 dark:text-gray-500"
                        :class="{ 'rotate-180': showAdvancedSettings }"
                    />
                </button>

                <transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 -translate-y-1"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-1"
                >
                    <div
                        v-if="showAdvancedSettings"
                        class="mt-3 border-t border-slate-200/50 pt-3 dark:border-gray-800/60"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="text-xs font-medium text-slate-700 dark:text-gray-300"
                                    >
                                        Essential study updates & announcements
                                    </span>
                                    <span
                                        class="rounded bg-slate-200/60 px-1.5 py-0.5 text-[10px] font-medium text-slate-600 dark:bg-gray-800 dark:text-gray-400"
                                    >
                                        Recommended
                                    </span>
                                </div>
                                <p
                                    class="text-[11px] text-slate-500 dark:text-gray-400"
                                >
                                    Receive notifications for syllabus changes,
                                    study materials, and platform alerts.
                                </p>
                            </div>

                            <button
                                type="button"
                                @click="handleEmailToggle"
                                :disabled="form.processing"
                                class="relative inline-flex shrink-0 cursor-pointer items-center focus:outline-none"
                            >
                                <div
                                    class="h-5 w-9 rounded-full transition-colors after:absolute after:top-[2px] after:left-[2px] after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow-sm after:transition-all after:content-[''] dark:after:bg-gray-200"
                                    :class="
                                        form.receive_emails
                                            ? 'bg-slate-700 after:translate-x-4 dark:bg-gray-500'
                                            : 'bg-slate-300 dark:bg-gray-700'
                                    "
                                ></div>
                            </button>
                        </div>

                        <!-- Dark Friction Warning if disabled -->
                        <div
                            v-if="!form.receive_emails"
                            class="mt-3 flex items-start gap-2.5 rounded-lg border border-amber-200/80 bg-amber-50/80 p-2.5 text-xs text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-300"
                        >
                            <AlertTriangle
                                class="mt-0.5 h-3.5 w-3.5 shrink-0 text-amber-600 dark:text-amber-400"
                            />
                            <div class="flex-1 text-[11px] leading-relaxed">
                                <span class="font-medium"
                                    >You may miss critical updates:</span
                                >
                                Disabling this will prevent you from receiving
                                vital academic alerts, syllabus updates, and new
                                study materials.
                                <button
                                    type="button"
                                    @click="form.receive_emails = true"
                                    class="ml-1.5 font-semibold text-amber-700 underline hover:text-amber-900 dark:text-amber-300 dark:hover:text-amber-100"
                                >
                                    Keep enabled
                                </button>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end pt-2">
                <button
                    type="submit"
                    :disabled="form.processing"
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

        <!-- Confirmation Modal for turning off emails (Dark Friction UX) -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="showConfirmModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs dark:bg-black/60"
            >
                <div
                    class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="flex items-start gap-4">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400"
                        >
                            <AlertTriangle class="h-5 w-5" />
                        </div>
                        <div class="space-y-1">
                            <h3
                                class="text-base font-semibold text-slate-900 dark:text-gray-100"
                            >
                                Turn off essential announcements?
                            </h3>
                            <p
                                class="text-xs leading-relaxed text-slate-500 dark:text-gray-400"
                            >
                                You will stop receiving critical syllabus
                                announcements, new exam preparation materials,
                                and platform alerts. We strongly recommend
                                keeping this enabled.
                            </p>
                        </div>
                    </div>

                    <div
                        class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"
                    >
                        <button
                            type="button"
                            @click="confirmDisable"
                            class="rounded-xl px-4 py-2 text-xs font-medium text-slate-400 transition hover:text-slate-600 dark:text-gray-500 dark:hover:text-gray-300"
                        >
                            Yes, turn off anyway
                        </button>
                        <button
                            type="button"
                            @click="showConfirmModal = false"
                            class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-2.5 text-xs font-semibold text-white shadow-xs transition hover:bg-blue-700"
                        >
                            Keep Notifications Enabled
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>
