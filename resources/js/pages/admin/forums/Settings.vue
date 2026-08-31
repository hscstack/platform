<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Settings,
    MessageSquareText,
    Flag,
    Sliders,
    ShieldAlert,
    Save,
    Loader2,
} from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps<{
    settings: {
        posting_enabled: boolean;
        comments_enabled: boolean;
        disabled_reason: string;
        auto_unpublish_threshold: number;
        profanity_filter_enabled: boolean;
        banned_words: string;
    };
    pendingReportsCount: number;
}>();

const form = useForm({
    posting_enabled: props.settings.posting_enabled,
    comments_enabled: props.settings.comments_enabled,
    disabled_reason: props.settings.disabled_reason,
    auto_unpublish_threshold: props.settings.auto_unpublish_threshold,
    profanity_filter_enabled: props.settings.profanity_filter_enabled,
    banned_words: props.settings.banned_words,
});

const submitSettings = () => {
    form.post('/admin/forums/settings', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Forum Settings - Admin Panel" />

    <div class="space-y-6">
        <!-- Header -->
        <div
            class="flex flex-col gap-3 border-b border-slate-100 pb-5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800"
        >
            <div class="flex items-center gap-3">
                <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                >
                    <Settings class="h-5 w-5" />
                </div>
                <div>
                    <h1
                        class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl dark:text-gray-100"
                    >
                        Forum Global Settings
                    </h1>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                        Configure posting permissions, comments availability,
                        auto-unpublish rules, and profanity filtering.
                    </p>
                </div>
            </div>
        </div>

        <!-- Sub Tabs -->
        <div
            class="flex items-center gap-2 border-b border-slate-200 dark:border-gray-800"
        >
            <Link
                href="/admin/forums"
                class="flex items-center gap-2 border-b-2 border-transparent px-4 py-2.5 text-xs font-bold text-slate-500 transition-all hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-200"
            >
                <MessageSquareText class="h-4 w-4" />
                <span>All Discussions</span>
            </Link>

            <Link
                href="/admin/forums/reports"
                class="flex items-center gap-2 border-b-2 border-transparent px-4 py-2.5 text-xs font-bold text-slate-500 transition-all hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-200"
            >
                <Flag class="h-4 w-4 text-rose-500" />
                <span>Reported Content</span>
                <span
                    v-if="pendingReportsCount > 0"
                    class="py-0.2 rounded-full bg-rose-500 px-1.5 text-[10px] font-bold text-white"
                >
                    {{ pendingReportsCount }}
                </span>
            </Link>

            <Link
                href="/admin/forums/settings"
                class="flex items-center gap-2 border-b-2 border-indigo-600 px-4 py-2.5 text-xs font-bold text-indigo-600 transition-all dark:border-indigo-400 dark:text-indigo-400"
            >
                <Settings class="h-4 w-4" />
                <span>Forum Settings</span>
            </Link>
        </div>

        <form @submit.prevent="submitSettings" class="max-w-4xl space-y-6">
            <!-- 1. Global Availability Section -->
            <div
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs sm:p-6 dark:border-gray-800 dark:bg-gray-900"
            >
                <div
                    class="mb-4 flex items-center gap-2.5 border-b border-slate-100 pb-3 dark:border-gray-800"
                >
                    <Sliders
                        class="h-5 w-5 text-indigo-600 dark:text-indigo-400"
                    />
                    <div>
                        <h2
                            class="text-sm font-bold text-slate-900 dark:text-gray-100"
                        >
                            Community Availability & Toggles
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-gray-400">
                            Turn forum features on or off globally for
                            maintenance or moderation periods.
                        </p>
                    </div>
                </div>

                <div class="space-y-4">
                    <!-- Post creation toggle -->
                    <div
                        class="flex items-center justify-between gap-4 rounded-xl border border-slate-100 bg-slate-50/50 p-4 dark:border-gray-800 dark:bg-gray-800/40"
                    >
                        <div>
                            <label
                                class="text-xs font-bold text-slate-900 dark:text-gray-100"
                            >
                                Allow New Questions / Posts
                            </label>
                            <p
                                class="text-xs text-slate-500 dark:text-gray-400"
                            >
                                When disabled, users will not be able to create
                                new questions.
                            </p>
                        </div>
                        <input
                            v-model="form.posting_enabled"
                            type="checkbox"
                            class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-700"
                        />
                    </div>

                    <!-- Comments / Answers toggle -->
                    <div
                        class="flex items-center justify-between gap-4 rounded-xl border border-slate-100 bg-slate-50/50 p-4 dark:border-gray-800 dark:bg-gray-800/40"
                    >
                        <div>
                            <label
                                class="text-xs font-bold text-slate-900 dark:text-gray-100"
                            >
                                Allow Answers & Comments
                            </label>
                            <p
                                class="text-xs text-slate-500 dark:text-gray-400"
                            >
                                When disabled, answers and reply submissions
                                will be disabled across all discussions.
                            </p>
                        </div>
                        <input
                            v-model="form.comments_enabled"
                            type="checkbox"
                            class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-700"
                        />
                    </div>

                    <!-- Disabled message -->
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-gray-300"
                        >
                            Maintenance / Pause Notice (Optional)
                        </label>
                        <input
                            v-model="form.disabled_reason"
                            type="text"
                            placeholder="e.g. Forum discussions are temporarily paused during board exam week."
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs text-slate-900 shadow-2xs transition outline-none placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                        />
                    </div>
                </div>
            </div>

            <!-- 2. Auto-Moderation & Reports Section -->
            <div
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs sm:p-6 dark:border-gray-800 dark:bg-gray-900"
            >
                <div
                    class="mb-4 flex items-center gap-2.5 border-b border-slate-100 pb-3 dark:border-gray-800"
                >
                    <ShieldAlert
                        class="h-5 w-5 text-rose-600 dark:text-rose-400"
                    />
                    <div>
                        <h2
                            class="text-sm font-bold text-slate-900 dark:text-gray-100"
                        >
                            Auto-Moderation & Content Screening
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-gray-400">
                            Automatic unpublish threshold on community reports
                            and profanity screening.
                        </p>
                    </div>
                </div>

                <div class="space-y-4">
                    <!-- Auto-unpublish threshold -->
                    <div
                        class="flex flex-col gap-2 rounded-xl border border-slate-100 bg-slate-50/50 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-800/40"
                    >
                        <div>
                            <label
                                class="text-xs font-bold text-slate-900 dark:text-gray-100"
                            >
                                Auto-Unpublish Report Threshold
                            </label>
                            <p
                                class="text-xs text-slate-500 dark:text-gray-400"
                            >
                                Automatically hide a post from public view when
                                it receives this many pending reports.
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <input
                                v-model.number="form.auto_unpublish_threshold"
                                type="number"
                                min="1"
                                max="50"
                                required
                                class="w-20 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-center text-xs font-bold text-slate-900 shadow-2xs outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                            />
                            <span
                                class="text-xs text-slate-500 dark:text-gray-400"
                                >reports</span
                            >
                        </div>
                    </div>

                    <!-- Profanity filter toggle -->
                    <div
                        class="flex items-center justify-between gap-4 rounded-xl border border-slate-100 bg-slate-50/50 p-4 dark:border-gray-800 dark:bg-gray-800/40"
                    >
                        <div>
                            <label
                                class="text-xs font-bold text-slate-900 dark:text-gray-100"
                            >
                                Enable Profanity & Abusive Language Filter
                            </label>
                            <p
                                class="text-xs text-slate-500 dark:text-gray-400"
                            >
                                Screen question titles, bodies, and comments
                                against banned keywords before submission.
                            </p>
                        </div>
                        <input
                            v-model="form.profanity_filter_enabled"
                            type="checkbox"
                            class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-700"
                        />
                    </div>

                    <!-- Banned keywords input -->
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-gray-300"
                        >
                            Banned Keywords (Comma-separated)
                        </label>
                        <textarea
                            v-model="form.banned_words"
                            rows="3"
                            placeholder="e.g. badword1, abusive_term, spam_link"
                            class="w-full rounded-xl border border-slate-200 bg-white p-3 text-xs text-slate-900 shadow-2xs transition outline-none placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                        ></textarea>
                        <p
                            class="mt-1 text-[11px] text-slate-400 dark:text-gray-500"
                        >
                            Shared across Global Chat and Forum modules.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end pt-2">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-xs transition hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-600/20 disabled:opacity-50"
                >
                    <Loader2
                        v-if="form.processing"
                        class="h-4 w-4 animate-spin"
                    />
                    <Save v-else class="h-4 w-4" />
                    <span>{{
                        form.processing ? 'Saving Settings...' : 'Save Settings'
                    }}</span>
                </button>
            </div>
        </form>
    </div>
</template>
