import { Head, Link, useForm } from '@inertiajs/vue3';
import { Flag, Loader2, Save, Settings } from 'lucide-vue-next';
import { defineComponent } from 'vue';
import type { PropType } from 'vue';

type ForumApprovalMode = 'auto' | 'manual';

interface ForumSettingsData {
    approval_mode: string;
    posting_enabled: boolean;
    comments_enabled: boolean;
    disabled_reason: string;
    auto_unpublish_threshold: number;
    profanity_filter_enabled: boolean;
    banned_words: string;
}

interface ForumSettingsProps {
    settings: ForumSettingsData;
    pendingReportsCount: number;
}

interface ForumSettingsFormFields {
    approval_mode: ForumApprovalMode;
    posting_enabled: boolean;
    comments_enabled: boolean;
    disabled_reason: string;
    auto_unpublish_threshold: number;
    profanity_filter_enabled: boolean;
    banned_words: string;
}

export default defineComponent({
    name: 'AdminForumsSettings',
    props: {
        settings: {
            type: Object as PropType<ForumSettingsData>,
            required: true,
        },
        pendingReportsCount: {
            type: Number as PropType<number>,
            required: true,
        },
    },
    setup(props: ForumSettingsProps) {
        const form = useForm<ForumSettingsFormFields>({
            approval_mode:
                (props.settings.approval_mode as ForumApprovalMode) || 'auto',
            posting_enabled: props.settings.posting_enabled,
            comments_enabled: props.settings.comments_enabled,
            disabled_reason: props.settings.disabled_reason,
            auto_unpublish_threshold: props.settings.auto_unpublish_threshold,
            profanity_filter_enabled: props.settings.profanity_filter_enabled,
            banned_words: props.settings.banned_words,
        });

        const submitSettings = (e: Event) => {
            e.preventDefault();
            form.post('/admin/forums/settings', {
                preserveScroll: true,
            });
        };

        const onApprovalModeChange = (mode: ForumApprovalMode) => () => {
            form.approval_mode = mode;
        };

        const onPostingEnabledChange = (e: Event) => {
            form.posting_enabled = (e.target as HTMLInputElement).checked;
        };

        const onCommentsEnabledChange = (e: Event) => {
            form.comments_enabled = (e.target as HTMLInputElement).checked;
        };

        const onDisabledReasonInput = (e: Event) => {
            form.disabled_reason = (e.target as HTMLInputElement).value;
        };

        const onThresholdInput = (e: Event) => {
            form.auto_unpublish_threshold = Number(
                (e.target as HTMLInputElement).value,
            );
        };

        const onProfanityFilterChange = (e: Event) => {
            form.profanity_filter_enabled = (
                e.target as HTMLInputElement
            ).checked;
        };

        const onBannedWordsInput = (e: Event) => {
            form.banned_words = (e.target as HTMLTextAreaElement).value;
        };

        const saveButtonContent = () =>
            form.processing ? (
                <>
                    <Loader2 class="h-3.5 w-3.5 animate-spin" />
                    <span>Saving...</span>
                </>
            ) : (
                <>
                    <Save class="h-3.5 w-3.5" />
                    <span>Save Settings</span>
                </>
            );

        return () => (
            <>
                <Head title="Forum Settings - Admin" />

                <div class="max-w-4xl space-y-6">
                    {/* Page header with Save action */}
                    <div class="flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-gray-100">
                                    Forum Settings
                                </h1>
                                {props.pendingReportsCount > 0 && (
                                    <span class="inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 text-[11px] font-bold text-rose-700 dark:bg-rose-500/10 dark:text-rose-400">
                                        {props.pendingReportsCount} reports
                                        pending
                                    </span>
                                )}
                            </div>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                                Configure question approval mode, availability,
                                and moderation triggers.
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <button
                                type="button"
                                onClick={submitSettings}
                                disabled={form.processing}
                                class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 text-xs font-semibold text-white shadow-2xs transition hover:bg-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                            >
                                {saveButtonContent()}
                            </button>
                        </div>
                    </div>

                    {/* Slim text tabs with active underline */}
                    <nav class="flex max-w-full items-center gap-1 overflow-x-auto">
                        <Link
                            href="/admin/forums"
                            class="shrink-0 cursor-pointer px-2.5 py-1.5 text-xs font-semibold text-slate-500 transition hover:text-slate-800 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-gray-400 dark:hover:text-gray-200"
                        >
                            Discussions
                        </Link>
                        <Link
                            href="/admin/forums/reports"
                            class="flex shrink-0 cursor-pointer items-center gap-1.5 px-2.5 py-1.5 text-xs font-semibold text-slate-500 transition hover:text-slate-800 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-gray-400 dark:hover:text-gray-200"
                        >
                            <Flag class="h-3.5 w-3.5 text-rose-500" />
                            <span>Reports</span>
                            {props.pendingReportsCount > 0 && (
                                <span class="rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] leading-none font-bold text-white">
                                    {props.pendingReportsCount}
                                </span>
                            )}
                        </Link>
                        <Link
                            href="/admin/forums/settings"
                            class="flex shrink-0 cursor-pointer items-center gap-1 px-2.5 py-1.5 text-xs font-bold text-indigo-600 underline decoration-2 underline-offset-4 transition focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-indigo-400"
                        >
                            <Settings class="h-3.5 w-3.5" />
                            <span>Settings</span>
                        </Link>
                    </nav>

                    <form onSubmit={submitSettings} class="space-y-5">
                        {/* 1. Approval */}
                        <section class="space-y-3">
                            <div>
                                <h2 class="text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400">
                                    Approval
                                </h2>
                                <p class="mt-0.5 text-[11px] text-slate-500 dark:text-gray-400">
                                    Choose whether new questions go live
                                    automatically or wait for moderator review.
                                </p>
                            </div>

                            <div
                                role="radiogroup"
                                aria-label="Post approval mode"
                                class="inline-flex items-center gap-0.5 rounded-xl bg-slate-100 p-1 dark:bg-gray-800"
                            >
                                <label
                                    class={[
                                        'cursor-pointer rounded-lg px-2.5 py-1 text-xs font-semibold transition-all peer-focus-visible:outline-2 peer-focus-visible:outline-indigo-500 active:scale-95',
                                        form.approval_mode === 'auto'
                                            ? 'bg-white text-slate-900 shadow-2xs dark:bg-gray-800 dark:text-gray-100'
                                            : 'text-slate-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200',
                                    ]}
                                >
                                    <input
                                        value="auto"
                                        checked={form.approval_mode === 'auto'}
                                        onChange={onApprovalModeChange('auto')}
                                        type="radio"
                                        class="peer sr-only"
                                    />
                                    Automatic
                                </label>
                                <label
                                    class={[
                                        'cursor-pointer rounded-lg px-2.5 py-1 text-xs font-semibold transition-all peer-focus-visible:outline-2 peer-focus-visible:outline-indigo-500 active:scale-95',
                                        form.approval_mode === 'manual'
                                            ? 'bg-white text-slate-900 shadow-2xs dark:bg-gray-800 dark:text-gray-100'
                                            : 'text-slate-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200',
                                    ]}
                                >
                                    <input
                                        value="manual"
                                        checked={
                                            form.approval_mode === 'manual'
                                        }
                                        onChange={onApprovalModeChange(
                                            'manual',
                                        )}
                                        type="radio"
                                        class="peer sr-only"
                                    />
                                    Manual review
                                </label>
                            </div>
                            <p class="text-[11px] text-slate-400 dark:text-gray-500">
                                {form.approval_mode === 'manual'
                                    ? 'New posts stay pending until approved by a moderator.'
                                    : 'Questions go live immediately after submission.'}
                            </p>
                            {form.errors.approval_mode && (
                                <p class="text-xs text-rose-600 dark:text-rose-400">
                                    {form.errors.approval_mode}
                                </p>
                            )}
                        </section>

                        {/* 2. Availability */}
                        <section class="space-y-3 border-t border-slate-100 pt-5 dark:border-gray-800">
                            <div>
                                <h2 class="text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400">
                                    Availability
                                </h2>
                                <p class="mt-0.5 text-[11px] text-slate-500 dark:text-gray-400">
                                    Toggle community participation or post a
                                    temporary pause notice.
                                </p>
                            </div>

                            <div class="divide-y divide-slate-100 dark:divide-gray-800">
                                <div class="flex items-center justify-between gap-4 py-3">
                                    <div>
                                        <p class="text-xs font-semibold text-slate-800 dark:text-gray-200">
                                            Allow new questions
                                        </p>
                                        <p class="mt-0.5 text-[11px] text-slate-400 dark:text-gray-500">
                                            Permit students to ask new
                                            questions.
                                        </p>
                                    </div>
                                    <input
                                        checked={form.posting_enabled}
                                        onChange={onPostingEnabledChange}
                                        type="checkbox"
                                        class="h-4 w-4 shrink-0 cursor-pointer rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/20 dark:border-gray-600"
                                    />
                                </div>
                                <div class="flex items-center justify-between gap-4 py-3">
                                    <div>
                                        <p class="text-xs font-semibold text-slate-800 dark:text-gray-200">
                                            Allow answers & comments
                                        </p>
                                        <p class="mt-0.5 text-[11px] text-slate-400 dark:text-gray-500">
                                            Permit replies and discussion
                                            solutions.
                                        </p>
                                    </div>
                                    <input
                                        checked={form.comments_enabled}
                                        onChange={onCommentsEnabledChange}
                                        type="checkbox"
                                        class="h-4 w-4 shrink-0 cursor-pointer rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/20 dark:border-gray-600"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-gray-300">
                                    Pause notice (optional)
                                </label>
                                <input
                                    value={form.disabled_reason}
                                    onInput={onDisabledReasonInput}
                                    type="text"
                                    placeholder="e.g. Discussions temporarily paused during maintenance."
                                    class="h-9 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-900 transition outline-none placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:border-indigo-400"
                                />
                                <p class="mt-1 text-[11px] text-slate-400 dark:text-gray-500">
                                    Shown to students when posting or commenting
                                    is turned off.
                                </p>
                                {form.errors.disabled_reason && (
                                    <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                                        {form.errors.disabled_reason}
                                    </p>
                                )}
                            </div>
                        </section>

                        {/* 3. Moderation */}
                        <section class="space-y-3 border-t border-slate-100 pt-5 dark:border-gray-800">
                            <div>
                                <h2 class="text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400">
                                    Moderation
                                </h2>
                                <p class="mt-0.5 text-[11px] text-slate-500 dark:text-gray-400">
                                    Automated triggers to unpublish reported
                                    content and screen keywords.
                                </p>
                            </div>

                            <div class="flex items-center justify-between gap-4 py-3">
                                <div>
                                    <p class="text-xs font-semibold text-slate-800 dark:text-gray-200">
                                        Auto-unpublish threshold
                                    </p>
                                    <p class="mt-0.5 text-[11px] text-slate-400 dark:text-gray-500">
                                        Hide questions when reports reach this
                                        count (0 disables).
                                    </p>
                                </div>
                                <div class="flex shrink-0 items-center gap-1.5">
                                    <input
                                        value={form.auto_unpublish_threshold}
                                        onInput={onThresholdInput}
                                        type="number"
                                        min="0"
                                        max="50"
                                        required
                                        class="h-9 w-16 rounded-xl border border-slate-200 bg-white px-2.5 text-center text-xs font-semibold text-slate-900 transition outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-indigo-400"
                                    />
                                    <span class="text-xs text-slate-500 dark:text-gray-400">
                                        reports
                                    </span>
                                </div>
                            </div>
                            {form.errors.auto_unpublish_threshold && (
                                <p class="text-xs text-rose-600 dark:text-rose-400">
                                    {form.errors.auto_unpublish_threshold}
                                </p>
                            )}

                            <div class="flex items-center justify-between gap-4 py-3">
                                <div>
                                    <p class="text-xs font-semibold text-slate-800 dark:text-gray-200">
                                        Profanity filter
                                    </p>
                                    <p class="mt-0.5 text-[11px] text-slate-400 dark:text-gray-500">
                                        Screen question and answer text against
                                        banned keywords.
                                    </p>
                                </div>
                                <input
                                    checked={form.profanity_filter_enabled}
                                    onChange={onProfanityFilterChange}
                                    type="checkbox"
                                    class="h-4 w-4 shrink-0 cursor-pointer rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/20 dark:border-gray-600"
                                />
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-gray-300">
                                    Banned keywords (comma-separated)
                                </label>
                                <textarea
                                    value={form.banned_words}
                                    onInput={onBannedWordsInput}
                                    rows={2}
                                    placeholder="e.g. abusive_word, spam_link"
                                    class="w-full rounded-xl border border-slate-200 bg-white p-2.5 text-xs text-slate-900 transition outline-none placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:border-indigo-400"
                                ></textarea>
                                <p class="mt-1 text-[11px] text-slate-400 dark:text-gray-500">
                                    Matches are screened before posts go live.
                                </p>
                                {form.errors.banned_words && (
                                    <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                                        {form.errors.banned_words}
                                    </p>
                                )}
                            </div>
                        </section>

                        <div class="flex justify-end border-t border-slate-100 pt-4 dark:border-gray-800">
                            <button
                                type="submit"
                                disabled={form.processing}
                                class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-xl bg-indigo-600 px-4 text-xs font-semibold text-white shadow-2xs transition hover:bg-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                            >
                                {saveButtonContent()}
                            </button>
                        </div>
                    </form>
                </div>
            </>
        );
    },
});
