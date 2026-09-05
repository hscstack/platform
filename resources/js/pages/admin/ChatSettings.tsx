/**
 * AdminChatSettings — TSX port of the former `ChatSettings.vue`.
 *
 * Same UI/behavior as the SFC (flat, decardified), rewritten as
 * a `.tsx` `defineComponent` render function. Resolved via the explicit
 * dual-extension (`*.vue` + `*.tsx`) page resolver in `resources/js/app.ts`.
 */
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Activity,
    Bot,
    Clock,
    Flag,
    Gavel,
    Loader2,
    MessageCircle,
    Plus,
    RotateCcw,
    Save,
    ShieldAlert,
    SlidersHorizontal,
    Smile,
    Trash2,
    Users,
    X,
} from 'lucide-vue-next';
import { defineComponent, ref } from 'vue';
import type { PropType } from 'vue';

type ChatAudience = 'verified_members' | 'all' | 'disabled';

interface ChatSettings {
    enabled: boolean;
    audience: ChatAudience;
    disabled_reason?: string;
    cooldown_seconds: number;
    max_messages: number;
    max_length: number;
    profanity_filter_enabled: boolean;
    banned_words: string;
    allowed_emojis?: string[];
    bot_username?: string;
    auto_ban_enabled: boolean;
    auto_ban_threshold: number;
    auto_ban_duration_minutes: number;
}

interface PresetOption {
    label: string;
    value: number;
}

interface AudienceOption {
    label: string;
    value: ChatAudience;
    description: string;
}

export default defineComponent({
    name: 'AdminChatSettings',
    props: {
        settings: { type: Object as PropType<ChatSettings>, required: true },
        totalMessages: { type: Number as PropType<number>, required: true },
        recentMessagesCount: {
            type: Number as PropType<number>,
            required: true,
        },
        pendingReportsCount: {
            type: Number as PropType<number>,
            default: undefined,
        },
    },
    setup(props) {
        const defaultReactionEmojis: string[] = [
            '👍',
            '❤️',
            '🔥',
            '😂',
            '🎉',
            '😮',
            '😢',
            '👏',
        ];
        const popularReactionEmojis: string[] = [
            '👍',
            '❤️',
            '🔥',
            '😂',
            '🎉',
            '😮',
            '😢',
            '👏',
            '🚀',
            '💯',
            '👀',
            '🙏',
            '💡',
            '✨',
            '🤝',
            '🥳',
        ];

        const newEmojiInput = ref<string>('');

        const form = useForm({
            enabled: props.settings.enabled,
            audience: props.settings.audience as ChatAudience,
            disabled_reason: props.settings.disabled_reason ?? '',
            cooldown_seconds: props.settings.cooldown_seconds,
            max_messages: props.settings.max_messages ?? 200,
            max_length: props.settings.max_length ?? 280,
            profanity_filter_enabled:
                props.settings.profanity_filter_enabled ?? true,
            banned_words: props.settings.banned_words ?? '',
            allowed_emojis:
                props.settings.allowed_emojis &&
                props.settings.allowed_emojis.length > 0
                    ? [...props.settings.allowed_emojis]
                    : [...defaultReactionEmojis],
            bot_username: props.settings.bot_username ?? 'hscstack',
            auto_ban_enabled: props.settings.auto_ban_enabled ?? true,
            auto_ban_threshold: props.settings.auto_ban_threshold ?? 5,
            auto_ban_duration_minutes:
                props.settings.auto_ban_duration_minutes ?? 1440,
        });

        const addEmoji = (emojiToAdd?: string): void => {
            const emoji = (emojiToAdd || newEmojiInput.value).trim();

            if (!emoji) {
                return;
            }

            if (!form.allowed_emojis.includes(emoji)) {
                form.allowed_emojis.push(emoji);
            }

            newEmojiInput.value = '';
        };

        const removeEmoji = (index: number): void => {
            form.allowed_emojis.splice(index, 1);
        };

        const resetDefaultEmojis = (): void => {
            form.allowed_emojis = [...defaultReactionEmojis];
        };

        const submitSettings = (): void => {
            form.post('/admin/chat/settings', {
                preserveScroll: true,
            });
        };

        const handleClearMessages = (): void => {
            if (
                confirm(
                    'Are you sure you want to permanently clear all global chat messages? This action cannot be undone.',
                )
            ) {
                router.post('/admin/chat/clear', {}, { preserveScroll: true });
            }
        };

        const cooldownPresets: PresetOption[] = [
            { label: 'No Delay (0s)', value: 0 },
            { label: '15 Seconds', value: 15 },
            { label: '30 Seconds (Default)', value: 30 },
            { label: '1 Minute (60s)', value: 60 },
            { label: '2 Minutes (120s)', value: 120 },
            { label: '5 Minutes (300s)', value: 300 },
        ];

        const messageLimitPresets: PresetOption[] = [
            { label: '50 Messages', value: 50 },
            { label: '100 Messages', value: 100 },
            { label: '200 Messages (Default)', value: 200 },
            { label: '500 Messages', value: 500 },
            { label: '1000 Messages', value: 1000 },
        ];

        const lengthPresets: PresetOption[] = [
            { label: '140 Characters', value: 140 },
            { label: '280 Characters (Default)', value: 280 },
            { label: '500 Characters', value: 500 },
            { label: '1000 Characters', value: 1000 },
        ];

        const reportThresholdPresets: PresetOption[] = [
            { label: '3 Reports', value: 3 },
            { label: '5 Reports (Default)', value: 5 },
            { label: '10 Reports', value: 10 },
            { label: '15 Reports', value: 15 },
        ];

        const banDurationPresets: PresetOption[] = [
            { label: '5 Mins', value: 5 },
            { label: '15 Mins', value: 15 },
            { label: '1 Hour', value: 60 },
            { label: '24 Hours (Default)', value: 1440 },
            { label: '3 Days', value: 4320 },
            { label: '7 Days', value: 10080 },
        ];

        const audienceOptions: AudienceOption[] = [
            {
                label: 'Verified Members',
                value: 'verified_members',
                description:
                    'Only contributors, editors, and verified students can post. (Default Beta)',
            },
            {
                label: 'Everyone',
                value: 'all',
                description:
                    'Any authenticated student can send messages. (All Students)',
            },
            {
                label: 'Disabled',
                value: 'disabled',
                description:
                    'Posting is locked for everyone during maintenance or exams. (Read-Only)',
            },
        ];

        const formatDurationText = (minutes: number): string => {
            if (!minutes || minutes <= 0) {
                return '0 minutes';
            }

            if (minutes >= 1440 && minutes % 1440 === 0) {
                const days = minutes / 1440;

                return `${days} day${days > 1 ? 's' : ''}`;
            }

            if (minutes >= 60 && minutes % 60 === 0) {
                const hours = minutes / 60;

                return `${hours} hour${hours > 1 ? 's' : ''}`;
            }

            return `${minutes} minute${minutes > 1 ? 's' : ''}`;
        };

        // One consistent pill style shared by every numeric preset group.
        const presetButtonClass = (isActive: boolean): string =>
            isActive
                ? 'inline-flex h-8 cursor-pointer items-center rounded-full border border-indigo-500 bg-indigo-50 px-3 text-[11px] font-bold text-indigo-700 transition focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:border-indigo-400 dark:bg-indigo-950/60 dark:text-indigo-300'
                : 'inline-flex h-8 cursor-pointer items-center rounded-full border border-slate-200 bg-white px-3 text-[11px] font-semibold text-slate-600 transition hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700';

        const customNumberInputClass =
            'h-9 w-24 rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-800 transition [appearance:textfield] focus:border-indigo-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-indigo-400 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none';

        return () => (
            <>
                <Head title="Global Chat Settings - Staff Panel" />

                <div class="space-y-6">
                    {/* Header */}
                    <div class="flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                        <div>
                            <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-gray-100">
                                Global Chat Management
                            </h1>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                                Manage realtime student lounge access, rate
                                limits, cooldown intervals, and moderation.
                            </p>
                        </div>

                        {/* Chat Live Indicator Badge */}
                        <div class="flex items-center gap-2">
                            <span
                                class={[
                                    'inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold',
                                    form.audience !== 'disabled'
                                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'
                                        : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300',
                                ]}
                            >
                                <span
                                    class={[
                                        'h-2 w-2 rounded-full',
                                        form.audience !== 'disabled'
                                            ? 'animate-pulse bg-emerald-500'
                                            : 'bg-rose-500',
                                    ]}
                                ></span>
                                {form.audience !== 'disabled'
                                    ? 'Chat Live'
                                    : 'Chat Inactive'}
                            </span>
                        </div>
                    </div>

                    {/* Global Chat Management Route Tabs */}
                    <div class="flex items-center gap-4">
                        <Link
                            href="/admin/chat"
                            class="flex cursor-pointer items-center gap-1.5 px-1 py-2 text-xs font-semibold text-indigo-600 underline decoration-2 underline-offset-4 transition focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-indigo-400"
                        >
                            <MessageCircle class="h-4 w-4" />
                            <span>Chat Configuration</span>
                        </Link>

                        <Link
                            href="/admin/chat/reports"
                            class="flex cursor-pointer items-center gap-1.5 px-1 py-2 text-xs font-semibold text-slate-500 transition hover:text-slate-800 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-gray-400 dark:hover:text-gray-200"
                        >
                            <Flag class="h-4 w-4 text-rose-500" />
                            <span>Reported Messages</span>
                            {props.pendingReportsCount &&
                                props.pendingReportsCount > 0 && (
                                    <span class="py-0.2 rounded-full bg-rose-500 px-1.5 text-[11px] font-bold text-white">
                                        {props.pendingReportsCount}
                                    </span>
                                )}
                        </Link>
                    </div>

                    {/* Metric Statistics */}
                    <div>
                        <div class="divide-y divide-slate-100 dark:divide-gray-800">
                            <div class="flex items-center justify-between gap-3 py-2.5">
                                <div class="flex items-center gap-2.5">
                                    <MessageCircle class="h-4 w-4 shrink-0 text-indigo-500" />
                                    <div>
                                        <p class="text-xs font-semibold text-slate-700 dark:text-gray-300">
                                            Stored Messages
                                        </p>
                                        <span class="text-[11px] text-slate-500 dark:text-gray-400">
                                            Auto-pruning maintains max{' '}
                                            {form.max_messages}
                                        </span>
                                    </div>
                                </div>
                                <p class="text-xl font-bold text-slate-900 tabular-nums dark:text-gray-100">
                                    {props.totalMessages}
                                </p>
                            </div>

                            <div class="flex items-center justify-between gap-3 py-2.5">
                                <div class="flex items-center gap-2.5">
                                    <Activity class="h-4 w-4 shrink-0 text-emerald-500" />
                                    <div>
                                        <p class="text-xs font-semibold text-slate-700 dark:text-gray-300">
                                            Activity (Last 24h)
                                        </p>
                                        <span class="text-[11px] text-slate-500 dark:text-gray-400">
                                            Messages sent today
                                        </span>
                                    </div>
                                </div>
                                <p class="text-xl font-bold text-slate-900 tabular-nums dark:text-gray-100">
                                    {props.recentMessagesCount}
                                </p>
                            </div>

                            <div class="flex items-center justify-between gap-3 py-2.5">
                                <div class="flex items-center gap-2.5">
                                    <Clock class="h-4 w-4 shrink-0 text-amber-500" />
                                    <div>
                                        <p class="text-xs font-semibold text-slate-700 dark:text-gray-300">
                                            Current Cooldown
                                        </p>
                                        <span class="text-[11px] text-slate-500 dark:text-gray-400">
                                            Interval per student
                                        </span>
                                    </div>
                                </div>
                                <p class="text-xl font-bold text-slate-900 tabular-nums dark:text-gray-100">
                                    {form.cooldown_seconds}s
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Settings Form */}
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            submitSettings();
                        }}
                        class="space-y-8"
                    >
                        {/* Group 1 — Access */}
                        <section aria-label="Access">
                            <h2 class="flex items-center gap-2 text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400">
                                <Users class="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400" />
                                Access
                            </h2>
                            <p class="mt-1 text-[11px] text-slate-500 dark:text-gray-400">
                                Choose who is permitted to send messages in the
                                student lounge, or pause posting.
                            </p>

                            {/* Audience segmented control */}
                            <div
                                role="group"
                                aria-label="Chat audience"
                                class="mt-3 inline-flex items-center gap-0.5 rounded-xl bg-slate-100 p-1 dark:bg-gray-800"
                            >
                                {audienceOptions.map((option) => {
                                    const isActive =
                                        form.audience === option.value;
                                    const isDisabledOption =
                                        option.value === 'disabled';

                                    return (
                                        <button
                                            key={option.value}
                                            type="button"
                                            aria-pressed={isActive}
                                            onClick={() => {
                                                form.audience = option.value;
                                            }}
                                            class={[
                                                'inline-flex h-9 cursor-pointer items-center justify-center rounded-lg px-2 text-center text-[11px] leading-tight font-bold transition focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 sm:text-xs',
                                                isActive
                                                    ? isDisabledOption
                                                        ? 'bg-white text-rose-600 shadow-2xs dark:bg-gray-800 dark:text-rose-400'
                                                        : 'bg-white text-slate-900 shadow-2xs dark:bg-gray-800 dark:text-gray-100'
                                                    : 'text-slate-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200',
                                            ]}
                                        >
                                            {option.label}
                                        </button>
                                    );
                                })}
                            </div>
                            <p class="mt-2 text-[11px] text-slate-500 dark:text-gray-400">
                                {
                                    audienceOptions.find(
                                        (option) =>
                                            option.value === form.audience,
                                    )?.description
                                }
                            </p>

                            {/* Custom Disabled / Maintenance Notice Input */}
                            {form.audience === 'disabled' && (
                                <div class="mt-3 border-t border-slate-100 pt-3 dark:border-gray-800">
                                    <label class="block text-[11px] font-semibold text-rose-700 dark:text-rose-300">
                                        Custom Notice Message for Students
                                        (Optional):
                                    </label>
                                    <input
                                        value={form.disabled_reason}
                                        onInput={(e) => {
                                            form.disabled_reason = (
                                                e.target as HTMLInputElement
                                            ).value;
                                        }}
                                        type="text"
                                        placeholder="e.g. Chat is temporarily paused during HSC ICT exam. Will resume at 4:00 PM."
                                        class="mt-1.5 w-full rounded-xl border border-rose-200 bg-white px-3.5 py-2 text-xs text-slate-800 transition focus:border-rose-500 focus:outline-none dark:border-rose-800/80 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-rose-400"
                                    />
                                    <p class="mt-1 text-[11px] text-rose-700/80 dark:text-rose-400/80">
                                        Leave blank to show the default notice:{' '}
                                        <em>
                                            "Global chat is currently disabled
                                            for maintenance."
                                        </em>
                                    </p>
                                </div>
                            )}
                        </section>

                        {/* Group 2 — Limits & Safety */}
                        <section aria-label="Limits and safety">
                            <h2 class="flex items-center gap-2 text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400">
                                <SlidersHorizontal class="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400" />
                                Limits & Safety
                            </h2>
                            <p class="mt-1 text-[11px] text-slate-500 dark:text-gray-400">
                                Rate limits, content filters, and automated
                                moderation.
                            </p>

                            <div class="divide-y divide-slate-100 dark:divide-gray-800">
                                {/* Cooldown Interval / Rate Limit Configuration */}
                                <div class="py-4">
                                    <label class="flex items-center gap-2 text-xs font-bold text-slate-900 dark:text-gray-100">
                                        <Clock class="h-4 w-4 shrink-0 text-indigo-600 dark:text-indigo-400" />
                                        Message Cooldown Interval (Anti-Spam)
                                    </label>
                                    <p class="mt-1 text-[11px] text-slate-500 dark:text-gray-400">
                                        Specify how many seconds a student must
                                        wait before they can send another
                                        message.
                                    </p>

                                    {/* Quick Preset Buttons */}
                                    <div class="mt-2.5 flex flex-wrap gap-1.5">
                                        {cooldownPresets.map((preset) => (
                                            <button
                                                key={preset.value}
                                                type="button"
                                                onClick={() => {
                                                    form.cooldown_seconds =
                                                        preset.value;
                                                }}
                                                class={presetButtonClass(
                                                    form.cooldown_seconds ===
                                                        preset.value,
                                                )}
                                            >
                                                {preset.label}
                                            </button>
                                        ))}
                                    </div>

                                    {/* Custom Interval Input */}
                                    <div class="mt-2.5 flex flex-wrap items-center gap-2">
                                        <label class="text-[11px] font-semibold text-slate-500 dark:text-gray-400">
                                            Custom Interval (Seconds):
                                        </label>
                                        <input
                                            value={form.cooldown_seconds}
                                            onInput={(e) => {
                                                form.cooldown_seconds = Number(
                                                    (
                                                        e.target as HTMLInputElement
                                                    ).value,
                                                );
                                            }}
                                            type="number"
                                            min="0"
                                            max="3600"
                                            class={customNumberInputClass}
                                        />
                                        <span class="text-[11px] text-slate-400 dark:text-gray-500">
                                            seconds
                                        </span>
                                    </div>
                                </div>

                                {/* Message Retention Buffer (Rolling Limit) */}
                                <div class="py-4">
                                    <label class="flex items-center gap-2 text-xs font-bold text-slate-900 dark:text-gray-100">
                                        <MessageCircle class="h-4 w-4 shrink-0 text-indigo-600 dark:text-indigo-400" />
                                        Rolling Message Buffer (Max Stored
                                        Messages)
                                    </label>
                                    <p class="mt-1 text-[11px] text-slate-500 dark:text-gray-400">
                                        Specify how many recent messages to keep
                                        in storage. Older messages beyond this
                                        limit are automatically pruned.
                                    </p>

                                    {/* Quick Preset Buttons */}
                                    <div class="mt-2.5 flex flex-wrap gap-1.5">
                                        {messageLimitPresets.map((preset) => (
                                            <button
                                                key={preset.value}
                                                type="button"
                                                onClick={() => {
                                                    form.max_messages =
                                                        preset.value;
                                                }}
                                                class={presetButtonClass(
                                                    form.max_messages ===
                                                        preset.value,
                                                )}
                                            >
                                                {preset.label}
                                            </button>
                                        ))}
                                    </div>

                                    {/* Custom Limit Input */}
                                    <div class="mt-2.5 flex flex-wrap items-center gap-2">
                                        <label class="text-[11px] font-semibold text-slate-500 dark:text-gray-400">
                                            Custom Retention Limit (20 - 1000
                                            messages):
                                        </label>
                                        <input
                                            value={form.max_messages}
                                            onInput={(e) => {
                                                form.max_messages = Number(
                                                    (
                                                        e.target as HTMLInputElement
                                                    ).value,
                                                );
                                            }}
                                            type="number"
                                            min="20"
                                            max="1000"
                                            class={customNumberInputClass}
                                        />
                                        <span class="text-[11px] text-slate-400 dark:text-gray-500">
                                            messages
                                        </span>
                                    </div>
                                </div>

                                {/* Maximum Message Character Limit */}
                                <div class="py-4">
                                    <label class="flex items-center gap-2 text-xs font-bold text-slate-900 dark:text-gray-100">
                                        <MessageCircle class="h-4 w-4 shrink-0 text-indigo-600 dark:text-indigo-400" />
                                        Per-Message Character Length Limit
                                    </label>
                                    <p class="mt-1 text-[11px] text-slate-500 dark:text-gray-400">
                                        Control the maximum length allowed for
                                        any single student message.
                                    </p>

                                    {/* Quick Preset Buttons */}
                                    <div class="mt-2.5 flex flex-wrap gap-1.5">
                                        {lengthPresets.map((preset) => (
                                            <button
                                                key={preset.value}
                                                type="button"
                                                onClick={() => {
                                                    form.max_length =
                                                        preset.value;
                                                }}
                                                class={presetButtonClass(
                                                    form.max_length ===
                                                        preset.value,
                                                )}
                                            >
                                                {preset.label}
                                            </button>
                                        ))}
                                    </div>

                                    {/* Custom Length Input */}
                                    <div class="mt-2.5 flex flex-wrap items-center gap-2">
                                        <label class="text-[11px] font-semibold text-slate-500 dark:text-gray-400">
                                            Custom Length Limit (50 - 1000
                                            characters):
                                        </label>
                                        <input
                                            value={form.max_length}
                                            onInput={(e) => {
                                                form.max_length = Number(
                                                    (
                                                        e.target as HTMLInputElement
                                                    ).value,
                                                );
                                            }}
                                            type="number"
                                            min="50"
                                            max="1000"
                                            class={customNumberInputClass}
                                        />
                                        <span class="text-[11px] text-slate-400 dark:text-gray-500">
                                            chars
                                        </span>
                                    </div>
                                </div>

                                {/* Profanity & Abusive Language Filter */}
                                <div class="py-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <label class="flex items-center gap-2 text-xs font-bold text-slate-900 dark:text-gray-100">
                                                <ShieldAlert class="h-4 w-4 shrink-0 text-rose-500" />
                                                Profanity & Abusive Language
                                                Filter
                                            </label>
                                            <p class="mt-1 text-[11px] text-slate-500 dark:text-gray-400">
                                                Automatically detects and blocks
                                                vulgar, abusive, and
                                                inappropriate words before they
                                                can be sent.
                                            </p>
                                        </div>

                                        {/* Toggle Switch */}
                                        <button
                                            type="button"
                                            onClick={() => {
                                                form.profanity_filter_enabled =
                                                    !form.profanity_filter_enabled;
                                            }}
                                            class={[
                                                'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500',
                                                form.profanity_filter_enabled
                                                    ? 'bg-indigo-600 dark:bg-indigo-500'
                                                    : 'bg-slate-200 dark:bg-gray-700',
                                            ]}
                                        >
                                            <span
                                                class={[
                                                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out',
                                                    form.profanity_filter_enabled
                                                        ? 'translate-x-5'
                                                        : 'translate-x-0',
                                                ]}
                                            />
                                        </button>
                                    </div>

                                    {/* Banned Words List (Textarea) */}
                                    {form.profanity_filter_enabled && (
                                        <div class="mt-2.5 space-y-1.5">
                                            <label class="block text-[11px] font-semibold text-slate-500 dark:text-gray-400">
                                                Banned Words & Offensive Roots
                                                (separated by commas or
                                                newlines):
                                            </label>
                                            <textarea
                                                value={form.banned_words}
                                                onInput={(e) => {
                                                    form.banned_words = (
                                                        e.target as HTMLTextAreaElement
                                                    ).value;
                                                }}
                                                rows={4}
                                                placeholder="Enter offensive words or roots separated by commas or new lines..."
                                                class="w-full rounded-xl border border-slate-200 bg-white p-3 font-mono text-xs text-slate-800 transition focus:border-indigo-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-indigo-400"
                                            />
                                            <p class="text-[11px] text-slate-400 dark:text-gray-500">
                                                The filter automatically
                                                collapses repeated letters and
                                                replaces common leet-speak
                                                substitutions (e.g. @ &rarr; a,
                                                $ &rarr; s, 0 &rarr; o).
                                            </p>
                                        </div>
                                    )}
                                </div>

                                {/* Auto-Ban on Community Reports Moderation */}
                                <div class="py-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <label class="flex items-center gap-2 text-xs font-bold text-slate-900 dark:text-gray-100">
                                                <Gavel class="h-4 w-4 shrink-0 text-rose-500" />
                                                Auto-Ban on Community Reports
                                            </label>
                                            <p class="mt-1 text-[11px] text-slate-500 dark:text-gray-400">
                                                Automatically suspend abusive
                                                non-staff users from global chat
                                                when a specific message receives
                                                a threshold of community
                                                reports.
                                            </p>
                                        </div>

                                        {/* Toggle Switch */}
                                        <button
                                            type="button"
                                            onClick={() => {
                                                form.auto_ban_enabled =
                                                    !form.auto_ban_enabled;
                                            }}
                                            class={[
                                                'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500',
                                                form.auto_ban_enabled
                                                    ? 'bg-indigo-600 dark:bg-indigo-500'
                                                    : 'bg-slate-200 dark:bg-gray-700',
                                            ]}
                                        >
                                            <span
                                                class={[
                                                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out',
                                                    form.auto_ban_enabled
                                                        ? 'translate-x-5'
                                                        : 'translate-x-0',
                                                ]}
                                            />
                                        </button>
                                    </div>

                                    {form.auto_ban_enabled && (
                                        <div class="mt-2.5 space-y-3">
                                            {/* Report Threshold */}
                                            <div>
                                                <label class="block text-[11px] font-semibold text-slate-500 dark:text-gray-400">
                                                    Report Threshold (Trigger
                                                    auto-ban when a message
                                                    reaches X distinct reports):
                                                </label>

                                                <div class="mt-2 flex flex-wrap gap-1.5">
                                                    {reportThresholdPresets.map(
                                                        (preset) => (
                                                            <button
                                                                key={
                                                                    preset.value
                                                                }
                                                                type="button"
                                                                onClick={() => {
                                                                    form.auto_ban_threshold =
                                                                        preset.value;
                                                                }}
                                                                class={presetButtonClass(
                                                                    form.auto_ban_threshold ===
                                                                        preset.value,
                                                                )}
                                                            >
                                                                {preset.label}
                                                            </button>
                                                        ),
                                                    )}
                                                </div>

                                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                                    <input
                                                        value={
                                                            form.auto_ban_threshold
                                                        }
                                                        onInput={(e) => {
                                                            form.auto_ban_threshold =
                                                                Number(
                                                                    (
                                                                        e.target as HTMLInputElement
                                                                    ).value,
                                                                );
                                                        }}
                                                        type="number"
                                                        min="1"
                                                        max="50"
                                                        aria-label="Custom report threshold"
                                                        class={
                                                            customNumberInputClass
                                                        }
                                                    />
                                                    <span class="text-[11px] text-slate-400 dark:text-gray-500">
                                                        reports
                                                    </span>
                                                </div>
                                            </div>

                                            {/* Ban Duration */}
                                            <div>
                                                <label class="block text-[11px] font-semibold text-slate-500 dark:text-gray-400">
                                                    Auto-Ban Suspension Duration
                                                    (Minutes):
                                                </label>

                                                <div class="mt-2 flex flex-wrap gap-1.5">
                                                    {banDurationPresets.map(
                                                        (preset) => (
                                                            <button
                                                                key={
                                                                    preset.value
                                                                }
                                                                type="button"
                                                                onClick={() => {
                                                                    form.auto_ban_duration_minutes =
                                                                        preset.value;
                                                                }}
                                                                class={presetButtonClass(
                                                                    form.auto_ban_duration_minutes ===
                                                                        preset.value,
                                                                )}
                                                            >
                                                                {preset.label}
                                                            </button>
                                                        ),
                                                    )}
                                                </div>

                                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                                    <input
                                                        value={
                                                            form.auto_ban_duration_minutes
                                                        }
                                                        onInput={(e) => {
                                                            form.auto_ban_duration_minutes =
                                                                Number(
                                                                    (
                                                                        e.target as HTMLInputElement
                                                                    ).value,
                                                                );
                                                        }}
                                                        type="number"
                                                        min="1"
                                                        max="43200"
                                                        aria-label="Custom auto-ban duration in minutes"
                                                        class={
                                                            customNumberInputClass
                                                        }
                                                    />
                                                    <span class="text-[11px] text-slate-400 dark:text-gray-500">
                                                        minutes
                                                    </span>
                                                </div>
                                            </div>

                                            <p class="text-[11px] text-slate-500 dark:text-gray-400">
                                                When a non-staff student's
                                                message accumulates{' '}
                                                <strong>
                                                    {form.auto_ban_threshold}
                                                </strong>{' '}
                                                distinct reports, the system
                                                automatically bans them for{' '}
                                                <strong>
                                                    {formatDurationText(
                                                        form.auto_ban_duration_minutes,
                                                    )}
                                                </strong>{' '}
                                                and broadcasts an automated
                                                moderation notice in the chat.
                                            </p>
                                        </div>
                                    )}
                                </div>

                                {/* Allowed Reaction Emojis */}
                                <div class="py-4">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <label class="flex items-center gap-2 text-xs font-bold text-slate-900 dark:text-gray-100">
                                                <Smile class="h-4 w-4 shrink-0 text-amber-500" />
                                                Allowed Message Reaction Emojis
                                            </label>
                                            <p class="mt-1 text-[11px] text-slate-500 dark:text-gray-400">
                                                Configure the emojis students
                                                and members can use to react to
                                                chat messages.
                                            </p>
                                        </div>

                                        <button
                                            type="button"
                                            onClick={resetDefaultEmojis}
                                            class="inline-flex h-8 shrink-0 cursor-pointer items-center justify-center gap-1.5 rounded-xl border border-slate-200 px-3 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500 active:scale-95 max-sm:w-full dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                                        >
                                            <RotateCcw class="h-3.5 w-3.5" />
                                            <span class="max-sm:sr-only">
                                                Reset Defaults
                                            </span>
                                            <span class="sm:hidden">Reset</span>
                                        </button>
                                    </div>

                                    {/* Current Active Emojis List (Pills) */}
                                    <p class="mt-2.5 text-[11px] font-semibold text-slate-500 dark:text-gray-400">
                                        Active Reaction Emojis (
                                        {form.allowed_emojis.length} enabled):
                                    </p>
                                    <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                        {form.allowed_emojis.map(
                                            (emoji, idx) => (
                                                <span
                                                    key={`${emoji}-${idx}`}
                                                    class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white py-1 pr-1.5 pl-2.5 text-sm dark:border-gray-700 dark:bg-gray-800"
                                                >
                                                    <span class="text-base leading-none">
                                                        {emoji}
                                                    </span>
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            removeEmoji(idx)
                                                        }
                                                        class="cursor-pointer rounded-full p-1 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:hover:bg-rose-950/50 dark:hover:text-rose-400"
                                                        title="Remove emoji"
                                                        aria-label={`Remove ${emoji} emoji`}
                                                    >
                                                        <X class="h-3 w-3" />
                                                    </button>
                                                </span>
                                            ),
                                        )}

                                        {form.allowed_emojis.length === 0 && (
                                            <span class="text-xs text-amber-600 italic dark:text-amber-400">
                                                No emojis active. Reactions will
                                                be disabled until emojis are
                                                added.
                                            </span>
                                        )}
                                    </div>

                                    {/* Add Custom Emoji Input */}
                                    <div class="mt-2.5 flex flex-wrap items-center gap-2">
                                        <input
                                            value={newEmojiInput.value}
                                            onInput={(e) => {
                                                newEmojiInput.value = (
                                                    e.target as HTMLInputElement
                                                ).value;
                                            }}
                                            type="text"
                                            placeholder="Type or paste emoji..."
                                            maxLength={32}
                                            onKeydown={(e) => {
                                                if (e.key === 'Enter') {
                                                    e.preventDefault();
                                                    addEmoji();
                                                }
                                            }}
                                            class="h-9 w-48 rounded-xl border border-slate-200 bg-white px-3.5 text-xs text-slate-800 transition focus:border-indigo-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-indigo-400"
                                        />
                                        <button
                                            type="button"
                                            onClick={() => addEmoji()}
                                            class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-xl bg-indigo-50 px-3.5 text-xs font-bold text-indigo-700 transition hover:bg-indigo-100 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:bg-indigo-950/70 dark:text-indigo-300 dark:hover:bg-indigo-900"
                                        >
                                            <Plus class="h-3.5 w-3.5" />
                                            <span>Add Emoji</span>
                                        </button>
                                    </div>

                                    {/* Quick Presets Picker */}
                                    <p class="mt-2.5 text-[11px] font-semibold text-slate-500 dark:text-gray-400">
                                        Quick Add Popular Emojis:
                                    </p>
                                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                                        {popularReactionEmojis.map((emoji) => (
                                            <button
                                                key={emoji}
                                                type="button"
                                                onClick={() => addEmoji(emoji)}
                                                disabled={form.allowed_emojis.includes(
                                                    emoji,
                                                )}
                                                class={[
                                                    'inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-full border text-sm transition hover:scale-110 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:scale-100',
                                                    form.allowed_emojis.includes(
                                                        emoji,
                                                    )
                                                        ? 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300'
                                                        : 'border-slate-200 bg-white hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700',
                                                ]}
                                                title={
                                                    form.allowed_emojis.includes(
                                                        emoji,
                                                    )
                                                        ? 'Already added'
                                                        : `Add ${emoji}`
                                                }
                                            >
                                                {emoji}
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </section>

                        {/* Group 3 — Bot & Danger Zone */}
                        <section aria-label="Bot and danger zone">
                            <h2 class="flex items-center gap-2 text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400">
                                <Bot class="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400" />
                                Bot & Danger Zone
                            </h2>
                            <p class="mt-1 text-[11px] text-slate-500 dark:text-gray-400">
                                Automated posting identity and irreversible
                                actions.
                            </p>

                            <div class="divide-y divide-slate-100 dark:divide-gray-800">
                                {/* System Chat Bot Account */}
                                <div class="py-4">
                                    <label class="flex items-center gap-2 text-xs font-bold text-slate-900 dark:text-gray-100">
                                        <Bot class="h-4 w-4 shrink-0 text-purple-600 dark:text-purple-400" />
                                        System Chat Bot Account
                                    </label>
                                    <p class="mt-1 text-[11px] text-slate-500 dark:text-gray-400">
                                        Specify the username of the account that
                                        will post automated system
                                        announcements, ban notices, and
                                        moderation alerts.
                                    </p>

                                    <div class="mt-2.5 max-w-xs">
                                        <label class="block text-[11px] font-semibold text-slate-500 dark:text-gray-400">
                                            Bot Account Username:
                                        </label>
                                        <div class="relative mt-1">
                                            <span class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-xs font-bold text-slate-400">
                                                @
                                            </span>
                                            <input
                                                value={form.bot_username}
                                                onInput={(e) => {
                                                    form.bot_username = (
                                                        e.target as HTMLInputElement
                                                    ).value;
                                                }}
                                                type="text"
                                                placeholder="hscstack"
                                                class="h-9 w-full rounded-xl border border-slate-200 bg-white py-2 pr-3 pl-8 text-xs font-bold text-slate-800 transition focus:border-indigo-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-indigo-400"
                                            />
                                        </div>
                                        <p class="mt-1 text-[11px] text-slate-400 dark:text-gray-500">
                                            Enter the username of an existing
                                            user account to post automated
                                            moderation notices and ban
                                            announcements.
                                        </p>
                                        {form.errors.bot_username && (
                                            <p class="mt-1 text-xs font-medium text-rose-500">
                                                {form.errors.bot_username}
                                            </p>
                                        )}
                                    </div>
                                </div>

                                {/* Danger Zone: Purge Chat */}
                                <div class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="flex items-center gap-2 text-xs font-bold text-rose-600 dark:text-rose-400">
                                            <Trash2 class="h-4 w-4 shrink-0" />
                                            Clear all messages
                                        </p>
                                        <p class="mt-1 text-[11px] text-slate-500 dark:text-gray-400">
                                            Permanently deletes every stored
                                            global chat message. This cannot be
                                            undone.
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={handleClearMessages}
                                        class="inline-flex h-9 shrink-0 cursor-pointer items-center justify-center gap-1.5 rounded-xl border border-rose-300 bg-rose-50 px-3.5 text-xs font-bold text-rose-700 transition hover:bg-rose-100 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 max-sm:w-full dark:border-rose-800 dark:bg-rose-950/50 dark:text-rose-300 dark:hover:bg-rose-950"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                        <span>Clear All Messages</span>
                                    </button>
                                </div>
                            </div>
                        </section>

                        {/* Submit Button */}
                        <div class="flex flex-col gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:items-center dark:border-gray-800">
                            <button
                                type="submit"
                                disabled={form.processing}
                                class="inline-flex h-9 cursor-pointer items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 text-xs font-bold text-white transition hover:bg-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 max-sm:w-full dark:bg-indigo-500 dark:hover:bg-indigo-600"
                            >
                                {form.processing ? (
                                    <Loader2 class="h-3.5 w-3.5 animate-spin" />
                                ) : (
                                    <Save class="h-3.5 w-3.5" />
                                )}
                                <span>Save Chat Configuration</span>
                            </button>
                        </div>
                    </form>
                </div>
            </>
        );
    },
});
