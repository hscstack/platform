<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Settings, Flag, Save, Loader2 } from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps<{
    settings: {
        approval_mode: string;
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
    approval_mode: props.settings.approval_mode || 'auto',
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
    <Head title="Forum Settings - Admin" />

    <div class="max-w-4xl space-y-5">
        <!-- Minimal Header -->
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1
                    class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl dark:text-gray-100"
                >
                    Forum Settings
                </h1>
                <p class="text-xs text-slate-500 dark:text-gray-400">
                    Configure question approval mode, availability, and
                    moderation triggers.
                </p>
            </div>

            <!-- Header Quick Tabs -->
            <div
                class="flex max-w-full items-center gap-1.5 overflow-x-auto rounded-xl border border-slate-200 bg-white p-1 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
            >
                <Link
                    href="/admin/forums"
                    class="shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                >
                    Discussions
                </Link>

                <Link
                    href="/admin/forums/reports"
                    class="flex shrink-0 items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                >
                    <Flag class="h-3.5 w-3.5 text-rose-500" />
                    <span>Reports</span>
                    <span
                        v-if="pendingReportsCount > 0"
                        class="py-0.2 rounded-full bg-rose-500 px-1.5 text-[10px] font-bold text-white"
                    >
                        {{ pendingReportsCount }}
                    </span>
                </Link>

                <Link
                    href="/admin/forums/settings"
                    class="flex shrink-0 items-center gap-1 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                >
                    <Settings class="h-3.5 w-3.5" />
                    <span>Settings</span>
                </Link>
            </div>
        </div>

        <form @submit.prevent="submitSettings" class="space-y-4">
            <!-- 1. Approval Mode -->
            <div
                class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
            >
                <h2
                    class="text-xs font-bold tracking-wider text-slate-900 uppercase dark:text-gray-100"
                >
                    Post Approval Workflow
                </h2>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                    Choose whether new questions go live automatically or
                    require moderator review first.
                </p>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <label
                        class="flex cursor-pointer items-start gap-3 rounded-xl border p-3.5 transition"
                        :class="[
                            form.approval_mode === 'auto'
                                ? 'border-indigo-500 bg-indigo-50/40 dark:border-indigo-500/70 dark:bg-indigo-950/20'
                                : 'border-slate-200 bg-white hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-900 dark:hover:bg-gray-800/50',
                        ]"
                    >
                        <input
                            v-model="form.approval_mode"
                            type="radio"
                            value="auto"
                            class="mt-0.5 h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <div class="space-y-0.5">
                            <div
                                class="text-xs font-bold text-slate-900 dark:text-gray-100"
                            >
                                Automatic (Default)
                            </div>
                            <p
                                class="text-[11px] text-slate-500 dark:text-gray-400"
                            >
                                Questions go live immediately after submission.
                            </p>
                        </div>
                    </label>

                    <label
                        class="flex cursor-pointer items-start gap-3 rounded-xl border p-3.5 transition"
                        :class="[
                            form.approval_mode === 'manual'
                                ? 'border-indigo-500 bg-indigo-50/40 dark:border-indigo-500/70 dark:bg-indigo-950/20'
                                : 'border-slate-200 bg-white hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-900 dark:hover:bg-gray-800/50',
                        ]"
                    >
                        <input
                            v-model="form.approval_mode"
                            type="radio"
                            value="manual"
                            class="mt-0.5 h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <div class="space-y-0.5">
                            <div
                                class="text-xs font-bold text-slate-900 dark:text-gray-100"
                            >
                                Manual Review
                            </div>
                            <p
                                class="text-[11px] text-slate-500 dark:text-gray-400"
                            >
                                New posts stay pending until approved by a
                                moderator.
                            </p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- 2. Availability Toggles -->
            <div
                class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
            >
                <h2
                    class="text-xs font-bold tracking-wider text-slate-900 uppercase dark:text-gray-100"
                >
                    Forum Availability
                </h2>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                    Toggle community participation or post a temporary
                    maintenance reason.
                </p>

                <div class="mt-4 space-y-3">
                    <div
                        class="flex items-center justify-between gap-4 rounded-xl border border-slate-100 bg-slate-50/50 p-3.5 dark:border-gray-800 dark:bg-gray-800/30"
                    >
                        <div>
                            <span
                                class="text-xs font-bold text-slate-800 dark:text-gray-200"
                                >Allow New Questions</span
                            >
                            <p class="text-[11px] text-slate-400">
                                Permit students to ask new questions.
                            </p>
                        </div>
                        <input
                            v-model="form.posting_enabled"
                            type="checkbox"
                            class="h-4.5 w-4.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        />
                    </div>

                    <div
                        class="flex items-center justify-between gap-4 rounded-xl border border-slate-100 bg-slate-50/50 p-3.5 dark:border-gray-800 dark:bg-gray-800/30"
                    >
                        <div>
                            <span
                                class="text-xs font-bold text-slate-800 dark:text-gray-200"
                                >Allow Answers & Comments</span
                            >
                            <p class="text-[11px] text-slate-400">
                                Permit replies and discussion solutions.
                            </p>
                        </div>
                        <input
                            v-model="form.comments_enabled"
                            type="checkbox"
                            class="h-4.5 w-4.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        />
                    </div>

                    <div class="pt-1">
                        <label
                            class="mb-1 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                        >
                            Pause Notice (Optional)
                        </label>
                        <input
                            v-model="form.disabled_reason"
                            type="text"
                            placeholder="e.g. Discussions temporarily paused during maintenance."
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 transition outline-none placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                        />
                    </div>
                </div>
            </div>

            <!-- 3. Moderation & Thresholds -->
            <div
                class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
            >
                <h2
                    class="text-xs font-bold tracking-wider text-slate-900 uppercase dark:text-gray-100"
                >
                    Auto-Moderation & Screening
                </h2>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                    Automated triggers to unpublish reported content and screen
                    keywords.
                </p>

                <div class="mt-4 space-y-3">
                    <div
                        class="flex items-center justify-between gap-4 rounded-xl border border-slate-100 bg-slate-50/50 p-3.5 dark:border-gray-800 dark:bg-gray-800/30"
                    >
                        <div>
                            <span
                                class="text-xs font-bold text-slate-800 dark:text-gray-200"
                                >Auto-Unpublish Threshold</span
                            >
                            <p class="text-[11px] text-slate-400">
                                Hide questions when reports reach this count (0
                                to disable).
                            </p>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <input
                                v-model.number="form.auto_unpublish_threshold"
                                type="number"
                                min="0"
                                max="50"
                                required
                                class="w-16 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-center text-xs font-bold text-slate-900 outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                            />
                            <span class="text-xs text-slate-500">reports</span>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-between gap-4 rounded-xl border border-slate-100 bg-slate-50/50 p-3.5 dark:border-gray-800 dark:bg-gray-800/30"
                    >
                        <div>
                            <span
                                class="text-xs font-bold text-slate-800 dark:text-gray-200"
                                >Profanity Filter</span
                            >
                            <p class="text-[11px] text-slate-400">
                                Screen question and answer text against banned
                                keywords.
                            </p>
                        </div>
                        <input
                            v-model="form.profanity_filter_enabled"
                            type="checkbox"
                            class="h-4.5 w-4.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        />
                    </div>

                    <div class="pt-1">
                        <label
                            class="mb-1 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                        >
                            Banned Keywords (Comma-separated)
                        </label>
                        <textarea
                            v-model="form.banned_words"
                            rows="2"
                            placeholder="e.g. abusive_word, spam_link"
                            class="w-full rounded-xl border border-slate-200 bg-white p-2.5 text-xs text-slate-900 transition outline-none placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- Save Action Button -->
            <div class="flex justify-end pt-2">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-indigo-700 disabled:opacity-50"
                >
                    <Loader2
                        v-if="form.processing"
                        class="h-3.5 w-3.5 animate-spin"
                    />
                    <Save v-else class="h-3.5 w-3.5" />
                    <span>{{
                        form.processing ? 'Saving...' : 'Save Settings'
                    }}</span>
                </button>
            </div>
        </form>
    </div>
</template>
