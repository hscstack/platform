<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import {
    MessageCircle,
    Save,
    Clock,
    Users,
    Trash2,
    Loader2,
    Activity,
    Flag,
    ShieldAlert,
    Smile,
    Plus,
    X,
    RotateCcw,
    Bot,
    Gavel,
} from 'lucide-vue-next';
import { ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

interface ChatSettingsProps {
    settings: {
        enabled: boolean;
        audience: string;
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
    };
    totalMessages: number;
    recentMessagesCount: number;
    pendingReportsCount?: number;
}

const props = defineProps<ChatSettingsProps>();

const defaultReactionEmojis = ['👍', '❤️', '🔥', '😂', '🎉', '😮', '😢', '👏'];
const popularReactionEmojis = [
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

const newEmojiInput = ref('');

const form = useForm({
    enabled: props.settings.enabled,
    audience: props.settings.audience,
    disabled_reason: props.settings.disabled_reason ?? '',
    cooldown_seconds: props.settings.cooldown_seconds,
    max_messages: props.settings.max_messages ?? 200,
    max_length: props.settings.max_length ?? 280,
    profanity_filter_enabled: props.settings.profanity_filter_enabled ?? true,
    banned_words: props.settings.banned_words ?? '',
    allowed_emojis:
        props.settings.allowed_emojis &&
        props.settings.allowed_emojis.length > 0
            ? [...props.settings.allowed_emojis]
            : [...defaultReactionEmojis],
    bot_username: props.settings.bot_username ?? 'hscstack',
    auto_ban_enabled: props.settings.auto_ban_enabled ?? true,
    auto_ban_threshold: props.settings.auto_ban_threshold ?? 5,
    auto_ban_duration_minutes: props.settings.auto_ban_duration_minutes ?? 1440,
});

const addEmoji = (emojiToAdd?: string) => {
    const emoji = (emojiToAdd || newEmojiInput.value).trim();

    if (!emoji) {
        return;
    }

    if (!form.allowed_emojis.includes(emoji)) {
        form.allowed_emojis.push(emoji);
    }

    newEmojiInput.value = '';
};

const removeEmoji = (index: number) => {
    form.allowed_emojis.splice(index, 1);
};

const resetDefaultEmojis = () => {
    form.allowed_emojis = [...defaultReactionEmojis];
};

const submitSettings = () => {
    form.post('/admin/chat/settings', {
        preserveScroll: true,
    });
};

const handleClearMessages = () => {
    if (
        confirm(
            'Are you sure you want to permanently clear all global chat messages? This action cannot be undone.',
        )
    ) {
        router.post('/admin/chat/clear', {}, { preserveScroll: true });
    }
};

const cooldownPresets = [
    { label: 'No Delay (0s)', value: 0 },
    { label: '15 Seconds', value: 15 },
    { label: '30 Seconds (Default)', value: 30 },
    { label: '1 Minute (60s)', value: 60 },
    { label: '2 Minutes (120s)', value: 120 },
    { label: '5 Minutes (300s)', value: 300 },
];

const messageLimitPresets = [
    { label: '50 Messages', value: 50 },
    { label: '100 Messages', value: 100 },
    { label: '200 Messages (Default)', value: 200 },
    { label: '500 Messages', value: 500 },
    { label: '1000 Messages', value: 1000 },
];

const lengthPresets = [
    { label: '140 Characters', value: 140 },
    { label: '280 Characters (Default)', value: 280 },
    { label: '500 Characters', value: 500 },
    { label: '1000 Characters', value: 1000 },
];

const reportThresholdPresets = [
    { label: '3 Reports', value: 3 },
    { label: '5 Reports (Default)', value: 5 },
    { label: '10 Reports', value: 10 },
    { label: '15 Reports', value: 15 },
];

const banDurationPresets = [
    { label: '5 Mins', value: 5 },
    { label: '15 Mins', value: 15 },
    { label: '1 Hour', value: 60 },
    { label: '24 Hours (Default)', value: 1440 },
    { label: '3 Days', value: 4320 },
    { label: '7 Days', value: 10080 },
];

const formatDurationText = (minutes: number) => {
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
</script>

<template>
    <Head title="Global Chat Settings - Staff Panel" />

    <div class="space-y-6">
        <!-- Header -->
        <div
            class="flex flex-col gap-3 border-b border-slate-100 pb-5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800"
        >
            <div class="flex items-center gap-3">
                <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                >
                    <MessageCircle class="h-5 w-5" />
                </div>
                <div>
                    <h1
                        class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl dark:text-gray-100"
                    >
                        Global Chat Management
                    </h1>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                        Manage realtime student lounge access, rate limits,
                        cooldown intervals, and moderation.
                    </p>
                </div>
            </div>

            <!-- Chat Live Indicator Badge -->
            <div class="flex items-center gap-2">
                <span
                    class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold"
                    :class="
                        form.audience !== 'disabled'
                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'
                            : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'
                    "
                >
                    <span
                        class="h-2 w-2 rounded-full"
                        :class="
                            form.audience !== 'disabled'
                                ? 'animate-pulse bg-emerald-500'
                                : 'bg-rose-500'
                        "
                    ></span>
                    {{
                        form.audience !== 'disabled'
                            ? 'Chat Live'
                            : 'Chat Inactive'
                    }}
                </span>
            </div>
        </div>

        <!-- Global Chat Management Route Tabs -->
        <div
            class="flex items-center gap-2 border-b border-slate-200 dark:border-gray-800"
        >
            <Link
                href="/admin/chat"
                class="flex items-center gap-2 border-b-2 border-indigo-600 px-4 py-2.5 text-xs font-bold text-indigo-600 transition-all dark:border-indigo-400 dark:text-indigo-400"
            >
                <MessageCircle class="h-4 w-4" />
                <span>Chat Configuration</span>
            </Link>

            <Link
                href="/admin/chat/reports"
                class="flex items-center gap-2 border-b-2 border-transparent px-4 py-2.5 text-xs font-bold text-slate-500 transition-all hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-200"
            >
                <Flag class="h-4 w-4 text-rose-500" />
                <span>Reported Messages</span>
                <span
                    v-if="pendingReportsCount && pendingReportsCount > 0"
                    class="py-0.2 rounded-full bg-rose-500 px-1.5 text-[10px] font-bold text-white"
                >
                    {{ pendingReportsCount }}
                </span>
            </Link>
        </div>

        <!-- Metric Statistics Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div
                class="rounded-2xl border border-slate-100 bg-slate-50/60 p-4 dark:border-gray-800 dark:bg-gray-800/40"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs font-semibold text-slate-500 dark:text-gray-400"
                        >Stored Messages</span
                    >
                    <MessageCircle class="h-4 w-4 text-indigo-500" />
                </div>
                <p
                    class="mt-2 text-2xl font-bold text-slate-900 dark:text-gray-100"
                >
                    {{ totalMessages }}
                </p>
                <span class="text-[11px] text-slate-400 dark:text-gray-500"
                    >Auto-pruning maintains max {{ form.max_messages }}</span
                >
            </div>

            <div
                class="rounded-2xl border border-slate-100 bg-slate-50/60 p-4 dark:border-gray-800 dark:bg-gray-800/40"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs font-semibold text-slate-500 dark:text-gray-400"
                        >Activity (Last 24h)</span
                    >
                    <Activity class="h-4 w-4 text-emerald-500" />
                </div>
                <p
                    class="mt-2 text-2xl font-bold text-slate-900 dark:text-gray-100"
                >
                    {{ recentMessagesCount }}
                </p>
                <span class="text-[11px] text-slate-400 dark:text-gray-500"
                    >Messages sent today</span
                >
            </div>

            <div
                class="rounded-2xl border border-slate-100 bg-slate-50/60 p-4 sm:col-span-2 lg:col-span-1 dark:border-gray-800 dark:bg-gray-800/40"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs font-semibold text-slate-500 dark:text-gray-400"
                        >Current Cooldown</span
                    >
                    <Clock class="h-4 w-4 text-amber-500" />
                </div>
                <p
                    class="mt-2 text-2xl font-bold text-slate-900 dark:text-gray-100"
                >
                    {{ form.cooldown_seconds }}s
                </p>
                <span class="text-[11px] text-slate-400 dark:text-gray-500"
                    >Interval per student</span
                >
            </div>
        </div>

        <!-- Settings Form -->
        <form
            @submit.prevent="submitSettings"
            class="space-y-6 rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs sm:p-8 dark:border-gray-800 dark:bg-gray-900"
        >
            <!-- 1. Audience Eligibility & Status -->
            <div
                class="space-y-3 border-b border-slate-100 pb-6 dark:border-gray-800"
            >
                <div>
                    <label
                        class="flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-gray-100"
                    >
                        <Users class="h-4 w-4 text-indigo-500" />
                        Target Audience & Chat Status
                    </label>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                        Choose who is permitted to send messages in the student
                        lounge, or pause posting.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <!-- Option: Verified Members Only -->
                    <label
                        class="flex cursor-pointer flex-col rounded-2xl border p-4 transition-all"
                        :class="
                            form.audience === 'verified_members'
                                ? 'border-indigo-500 bg-indigo-50/40 ring-2 ring-indigo-500/20 dark:border-indigo-400 dark:bg-indigo-950/30'
                                : 'border-slate-200 hover:border-slate-300 dark:border-gray-700 dark:hover:border-gray-600'
                        "
                    >
                        <div class="mb-1.5 flex items-center justify-between">
                            <span
                                class="text-xs font-bold text-slate-900 dark:text-gray-100"
                                >Verified Members</span
                            >
                            <input
                                type="radio"
                                v-model="form.audience"
                                value="verified_members"
                                class="text-indigo-600 focus:ring-indigo-500"
                            />
                        </div>
                        <p
                            class="text-[11px] leading-relaxed text-slate-500 dark:text-gray-400"
                        >
                            Only contributors, editors, and verified students
                            can post. (Default Beta)
                        </p>
                    </label>

                    <!-- Option: Everyone -->
                    <label
                        class="flex cursor-pointer flex-col rounded-2xl border p-4 transition-all"
                        :class="
                            form.audience === 'all'
                                ? 'border-indigo-500 bg-indigo-50/40 ring-2 ring-indigo-500/20 dark:border-indigo-400 dark:bg-indigo-950/30'
                                : 'border-slate-200 hover:border-slate-300 dark:border-gray-700 dark:hover:border-gray-600'
                        "
                    >
                        <div class="mb-1.5 flex items-center justify-between">
                            <span
                                class="text-xs font-bold text-slate-900 dark:text-gray-100"
                                >Everyone (All Students)</span
                            >
                            <input
                                type="radio"
                                v-model="form.audience"
                                value="all"
                                class="text-indigo-600 focus:ring-indigo-500"
                            />
                        </div>
                        <p
                            class="text-[11px] leading-relaxed text-slate-400 dark:text-gray-500"
                        >
                            Any authenticated student can send messages.
                        </p>
                    </label>

                    <!-- Option: Disabled / Read-only -->
                    <label
                        class="flex cursor-pointer flex-col rounded-2xl border p-4 transition-all"
                        :class="
                            form.audience === 'disabled'
                                ? 'border-rose-500 bg-rose-50/40 ring-2 ring-rose-500/20 dark:border-rose-400 dark:bg-rose-950/30'
                                : 'border-slate-200 hover:border-slate-300 dark:border-gray-700 dark:hover:border-gray-600'
                        "
                    >
                        <div class="mb-1.5 flex items-center justify-between">
                            <span
                                class="text-xs font-bold text-slate-900 dark:text-gray-100"
                                >Disabled (Read-Only)</span
                            >
                            <input
                                type="radio"
                                v-model="form.audience"
                                value="disabled"
                                class="text-rose-600 focus:ring-rose-500"
                            />
                        </div>
                        <p
                            class="text-[11px] leading-relaxed text-slate-500 dark:text-gray-400"
                        >
                            Posting is locked for everyone during maintenance or
                            exams.
                        </p>
                    </label>
                </div>

                <!-- Custom Disabled / Maintenance Notice Input -->
                <div
                    v-if="form.audience === 'disabled'"
                    class="mt-3 rounded-2xl border border-rose-200/80 bg-rose-50/40 p-4 dark:border-rose-900/40 dark:bg-rose-950/20"
                >
                    <label
                        class="block text-xs font-semibold text-rose-900 dark:text-rose-300"
                    >
                        Custom Notice Message for Students (Optional):
                    </label>
                    <input
                        v-model="form.disabled_reason"
                        type="text"
                        placeholder="e.g. Chat is temporarily paused during HSC ICT exam. Will resume at 4:00 PM."
                        class="mt-1.5 w-full rounded-xl border border-rose-200 bg-white px-3.5 py-2 text-xs text-slate-800 focus:border-rose-500 focus:outline-none dark:border-rose-800/80 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-rose-400"
                    />
                    <p
                        class="mt-1 text-[11px] text-rose-700/80 dark:text-rose-400/80"
                    >
                        Leave blank to show the default notice:
                        <em
                            >"Global chat is currently disabled for
                            maintenance."</em
                        >
                    </p>
                </div>
            </div>

            <!-- 3. Cooldown Interval / Rate Limit Configuration -->
            <div
                class="space-y-4 border-b border-slate-100 pb-6 dark:border-gray-800"
            >
                <div>
                    <label
                        class="flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-gray-100"
                    >
                        <Clock class="h-4 w-4 text-indigo-500" />
                        Message Cooldown Interval (Anti-Spam)
                    </label>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                        Specify how many seconds a student must wait before they
                        can send another message.
                    </p>
                </div>

                <!-- Quick Preset Buttons -->
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="preset in cooldownPresets"
                        :key="preset.value"
                        type="button"
                        @click="form.cooldown_seconds = preset.value"
                        class="cursor-pointer rounded-xl border px-3 py-1.5 text-xs font-bold transition-all active:scale-95"
                        :class="
                            form.cooldown_seconds === preset.value
                                ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:border-indigo-400 dark:bg-indigo-950/60 dark:text-indigo-300'
                                : 'border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                        "
                    >
                        {{ preset.label }}
                    </button>
                </div>

                <!-- Custom Interval Input -->
                <div class="max-w-xs">
                    <label
                        class="mb-1 block text-xs font-semibold text-slate-600 dark:text-gray-400"
                    >
                        Custom Interval (Seconds):
                    </label>
                    <div class="relative">
                        <input
                            v-model.number="form.cooldown_seconds"
                            type="number"
                            min="0"
                            max="3600"
                            class="w-full [appearance:textfield] rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 pr-20 text-xs font-bold text-slate-800 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-indigo-400 dark:focus:bg-gray-800 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                        />
                        <span
                            class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-xs font-medium text-slate-400"
                        >
                            seconds
                        </span>
                    </div>
                </div>
            </div>

            <!-- 4. Message Retention Buffer (Rolling Limit) -->
            <div
                class="space-y-4 border-b border-slate-100 pb-6 dark:border-gray-800"
            >
                <div>
                    <label
                        class="flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-gray-100"
                    >
                        <MessageCircle class="h-4 w-4 text-indigo-500" />
                        Rolling Message Buffer (Max Stored Messages)
                    </label>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                        Specify how many recent messages to keep in storage.
                        Older messages beyond this limit are automatically
                        pruned.
                    </p>
                </div>

                <!-- Quick Preset Buttons -->
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="preset in messageLimitPresets"
                        :key="preset.value"
                        type="button"
                        @click="form.max_messages = preset.value"
                        class="cursor-pointer rounded-xl border px-3 py-1.5 text-xs font-bold transition-all active:scale-95"
                        :class="
                            form.max_messages === preset.value
                                ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:border-indigo-400 dark:bg-indigo-950/60 dark:text-indigo-300'
                                : 'border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                        "
                    >
                        {{ preset.label }}
                    </button>
                </div>

                <!-- Custom Limit Input -->
                <div class="max-w-xs">
                    <label
                        class="mb-1 block text-xs font-semibold text-slate-600 dark:text-gray-400"
                    >
                        Custom Retention Limit (20 - 1000 messages):
                    </label>
                    <div class="relative">
                        <input
                            v-model.number="form.max_messages"
                            type="number"
                            min="20"
                            max="1000"
                            class="w-full [appearance:textfield] rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 pr-20 text-xs font-bold text-slate-800 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-indigo-400 dark:focus:bg-gray-800 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                        />
                        <span
                            class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-xs font-medium text-slate-400"
                        >
                            messages
                        </span>
                    </div>
                </div>
            </div>

            <!-- 5. Maximum Message Character Limit -->
            <div
                class="space-y-4 border-b border-slate-100 pb-6 dark:border-gray-800"
            >
                <div>
                    <label
                        class="flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-gray-100"
                    >
                        <MessageCircle class="h-4 w-4 text-indigo-500" />
                        Per-Message Character Length Limit
                    </label>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                        Control the maximum length allowed for any single
                        student message.
                    </p>
                </div>

                <!-- Quick Preset Buttons -->
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="preset in lengthPresets"
                        :key="preset.value"
                        type="button"
                        @click="form.max_length = preset.value"
                        class="cursor-pointer rounded-xl border px-3 py-1.5 text-xs font-bold transition-all active:scale-95"
                        :class="
                            form.max_length === preset.value
                                ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:border-indigo-400 dark:bg-indigo-950/60 dark:text-indigo-300'
                                : 'border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                        "
                    >
                        {{ preset.label }}
                    </button>
                </div>

                <!-- Custom Length Input -->
                <div class="max-w-xs">
                    <label
                        class="mb-1 block text-xs font-semibold text-slate-600 dark:text-gray-400"
                    >
                        Custom Length Limit (50 - 1000 characters):
                    </label>
                    <div class="relative">
                        <input
                            v-model.number="form.max_length"
                            type="number"
                            min="50"
                            max="1000"
                            class="w-full [appearance:textfield] rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 pr-14 text-xs font-bold text-slate-800 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-indigo-400 dark:focus:bg-gray-800 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                        />
                        <span
                            class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-xs font-medium text-slate-400"
                        >
                            chars
                        </span>
                    </div>
                </div>
            </div>

            <!-- 6. Profanity & Abusive Language Filter -->
            <div
                class="space-y-4 border-b border-slate-100 pb-6 dark:border-gray-800"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <label
                            class="flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-gray-100"
                        >
                            <ShieldAlert class="h-4 w-4 text-rose-500" />
                            Profanity & Abusive Language Filter
                        </label>
                        <p
                            class="mt-0.5 text-xs text-slate-500 dark:text-gray-400"
                        >
                            Automatically detects and blocks vulgar, abusive,
                            and inappropriate words before they can be sent.
                        </p>
                    </div>

                    <!-- Toggle Switch -->
                    <button
                        type="button"
                        @click="
                            form.profanity_filter_enabled =
                                !form.profanity_filter_enabled
                        "
                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                        :class="
                            form.profanity_filter_enabled
                                ? 'bg-indigo-600 dark:bg-indigo-500'
                                : 'bg-slate-200 dark:bg-gray-700'
                        "
                    >
                        <span
                            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                            :class="
                                form.profanity_filter_enabled
                                    ? 'translate-x-5'
                                    : 'translate-x-0'
                            "
                        />
                    </button>
                </div>

                <!-- Banned Words List (Textarea) -->
                <div v-if="form.profanity_filter_enabled" class="space-y-2">
                    <label
                        class="block text-xs font-semibold text-slate-600 dark:text-gray-400"
                    >
                        Banned Words & Offensive Roots (separated by commas or
                        newlines):
                    </label>
                    <textarea
                        v-model="form.banned_words"
                        rows="4"
                        placeholder="Enter offensive words or roots separated by commas or new lines..."
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 p-3 font-mono text-xs text-slate-800 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-gray-700 dark:bg-gray-800/80 dark:text-gray-200 dark:focus:border-indigo-400 dark:focus:bg-gray-800"
                    ></textarea>
                    <p class="text-[11px] text-slate-400 dark:text-gray-500">
                        The filter automatically collapses repeated letters and
                        replaces common leet-speak substitutions (e.g. @ &rarr;
                        a, $ &rarr; s, 0 &rarr; o).
                    </p>
                </div>
            </div>

            <!-- 7. Auto-Ban on Community Reports Moderation -->
            <div
                class="space-y-4 border-b border-slate-100 pb-6 dark:border-gray-800"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <label
                            class="flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-gray-100"
                        >
                            <Gavel class="h-4 w-4 text-rose-500" />
                            Auto-Ban on Community Reports
                        </label>
                        <p
                            class="mt-0.5 text-xs text-slate-500 dark:text-gray-400"
                        >
                            Automatically suspend abusive non-staff users from
                            global chat when a specific message receives a
                            threshold of community reports.
                        </p>
                    </div>

                    <!-- Toggle Switch -->
                    <button
                        type="button"
                        @click="form.auto_ban_enabled = !form.auto_ban_enabled"
                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                        :class="
                            form.auto_ban_enabled
                                ? 'bg-indigo-600 dark:bg-indigo-500'
                                : 'bg-slate-200 dark:bg-gray-700'
                        "
                    >
                        <span
                            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                            :class="
                                form.auto_ban_enabled
                                    ? 'translate-x-5'
                                    : 'translate-x-0'
                            "
                        />
                    </button>
                </div>

                <div v-if="form.auto_ban_enabled" class="space-y-4 pt-1">
                    <!-- Report Threshold -->
                    <div class="space-y-2">
                        <label
                            class="block text-xs font-semibold text-slate-600 dark:text-gray-400"
                        >
                            Report Threshold (Trigger auto-ban when a message
                            reaches X distinct reports):
                        </label>

                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="preset in reportThresholdPresets"
                                :key="preset.value"
                                type="button"
                                @click="form.auto_ban_threshold = preset.value"
                                class="cursor-pointer rounded-xl border px-3 py-1.5 text-xs font-bold transition-all active:scale-95"
                                :class="
                                    form.auto_ban_threshold === preset.value
                                        ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:border-indigo-400 dark:bg-indigo-950/60 dark:text-indigo-300'
                                        : 'border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                                "
                            >
                                {{ preset.label }}
                            </button>
                        </div>

                        <div class="max-w-xs pt-1">
                            <div class="relative">
                                <input
                                    v-model.number="form.auto_ban_threshold"
                                    type="number"
                                    min="1"
                                    max="50"
                                    class="w-full [appearance:textfield] rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 pr-20 text-xs font-bold text-slate-800 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-indigo-400 dark:focus:bg-gray-800 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                                />
                                <span
                                    class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-xs font-medium text-slate-400"
                                >
                                    reports
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Ban Duration -->
                    <div class="space-y-2">
                        <label
                            class="block text-xs font-semibold text-slate-600 dark:text-gray-400"
                        >
                            Auto-Ban Suspension Duration (Minutes):
                        </label>

                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="preset in banDurationPresets"
                                :key="preset.value"
                                type="button"
                                @click="
                                    form.auto_ban_duration_minutes =
                                        preset.value
                                "
                                class="cursor-pointer rounded-xl border px-3 py-1.5 text-xs font-bold transition-all active:scale-95"
                                :class="
                                    form.auto_ban_duration_minutes ===
                                    preset.value
                                        ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:border-indigo-400 dark:bg-indigo-950/60 dark:text-indigo-300'
                                        : 'border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                                "
                            >
                                {{ preset.label }}
                            </button>
                        </div>

                        <div class="max-w-xs pt-1">
                            <div class="relative">
                                <input
                                    v-model.number="
                                        form.auto_ban_duration_minutes
                                    "
                                    type="number"
                                    min="1"
                                    max="43200"
                                    class="w-full [appearance:textfield] rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 pr-18 text-xs font-bold text-slate-800 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-indigo-400 dark:focus:bg-gray-800 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                                />
                                <span
                                    class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-xs font-medium text-slate-400"
                                >
                                    minutes
                                </span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="rounded-xl border border-slate-200 bg-slate-50/70 p-3 text-xs text-slate-500 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-400"
                    >
                        When a non-staff student's message accumulates
                        <strong>{{ form.auto_ban_threshold }}</strong> distinct
                        reports, the system automatically bans them for
                        <strong>{{
                            formatDurationText(form.auto_ban_duration_minutes)
                        }}</strong>
                        and broadcasts an automated moderation notice in the
                        chat.
                    </div>
                </div>
            </div>

            <!-- 8. Allowed Reaction Emojis -->
            <div
                class="space-y-4 border-b border-slate-100 pb-6 dark:border-gray-800"
            >
                <div
                    class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <label
                            class="flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-gray-100"
                        >
                            <Smile class="h-4 w-4 text-amber-500" />
                            Allowed Message Reaction Emojis
                        </label>
                        <p
                            class="mt-0.5 text-xs text-slate-500 dark:text-gray-400"
                        >
                            Configure the emojis students and members can use to
                            react to chat messages.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="resetDefaultEmojis"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 active:scale-95 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <RotateCcw class="h-3.5 w-3.5" />
                        <span>Reset Defaults</span>
                    </button>
                </div>

                <!-- Current Active Emojis List (Pills) -->
                <div>
                    <span
                        class="mb-2 block text-xs font-semibold text-slate-600 dark:text-gray-400"
                    >
                        Active Reaction Emojis ({{ form.allowed_emojis.length }}
                        enabled):
                    </span>
                    <div class="flex flex-wrap items-center gap-2">
                        <div
                            v-for="(emoji, idx) in form.allowed_emojis"
                            :key="emoji"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm shadow-2xs dark:border-gray-700 dark:bg-gray-800"
                        >
                            <span class="text-base">{{ emoji }}</span>
                            <button
                                type="button"
                                @click="removeEmoji(idx)"
                                class="cursor-pointer rounded-full p-0.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/50 dark:hover:text-rose-400"
                                title="Remove emoji"
                            >
                                <X class="h-3 w-3" />
                            </button>
                        </div>

                        <span
                            v-if="form.allowed_emojis.length === 0"
                            class="text-xs text-amber-600 italic dark:text-amber-400"
                        >
                            No emojis active. Reactions will be disabled until
                            emojis are added.
                        </span>
                    </div>
                </div>

                <!-- Add Custom Emoji Input -->
                <div class="flex flex-wrap items-center gap-2 pt-1">
                    <div class="relative w-48">
                        <input
                            v-model="newEmojiInput"
                            type="text"
                            placeholder="Type or paste emoji..."
                            maxlength="32"
                            @keydown.enter.prevent="addEmoji()"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs text-slate-800 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-indigo-400 dark:focus:bg-gray-800"
                        />
                    </div>
                    <button
                        type="button"
                        @click="addEmoji()"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-indigo-50 px-3.5 py-2 text-xs font-bold text-indigo-700 transition hover:bg-indigo-100 active:scale-95 dark:bg-indigo-950/70 dark:text-indigo-300 dark:hover:bg-indigo-900"
                    >
                        <Plus class="h-3.5 w-3.5" />
                        <span>Add Emoji</span>
                    </button>
                </div>

                <!-- Quick Presets Picker -->
                <div class="space-y-1.5 pt-1">
                    <span
                        class="block text-[11px] font-semibold text-slate-500 dark:text-gray-400"
                    >
                        Quick Add Popular Emojis:
                    </span>
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="emoji in popularReactionEmojis"
                            :key="emoji"
                            type="button"
                            @click="addEmoji(emoji)"
                            :disabled="form.allowed_emojis.includes(emoji)"
                            class="cursor-pointer rounded-lg border px-2 py-1 text-sm transition hover:scale-110 active:scale-95 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:scale-100"
                            :class="
                                form.allowed_emojis.includes(emoji)
                                    ? 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300'
                                    : 'border-slate-200 bg-slate-50 hover:bg-slate-100 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700'
                            "
                            :title="
                                form.allowed_emojis.includes(emoji)
                                    ? 'Already added'
                                    : `Add ${emoji}`
                            "
                        >
                            {{ emoji }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- 8. System Chat Bot Account -->
            <div
                class="space-y-4 border-b border-slate-100 pb-6 dark:border-gray-800"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <label
                            class="flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-gray-100"
                        >
                            <Bot
                                class="h-4 w-4 text-purple-600 dark:text-purple-400"
                            />
                            System Chat Bot Account
                        </label>
                        <p
                            class="mt-0.5 text-xs text-slate-500 dark:text-gray-400"
                        >
                            Specify the username of the account that will post
                            automated system announcements, ban notices, and
                            moderation alerts.
                        </p>
                    </div>
                </div>

                <div class="max-w-xs space-y-1.5">
                    <label
                        class="block text-xs font-semibold text-slate-600 dark:text-gray-400"
                    >
                        Bot Account Username:
                    </label>
                    <div class="relative">
                        <span
                            class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-xs font-bold text-slate-400"
                        >
                            @
                        </span>
                        <input
                            v-model="form.bot_username"
                            type="text"
                            placeholder="hscstack"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pr-3 pl-8 text-xs font-bold text-slate-800 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-indigo-400 dark:focus:bg-gray-800"
                        />
                    </div>
                    <p class="text-[11px] text-slate-400 dark:text-gray-500">
                        Enter the username of an existing user account to post
                        automated moderation notices and ban announcements.
                    </p>
                    <p
                        v-if="form.errors.bot_username"
                        class="text-xs font-medium text-rose-500"
                    >
                        {{ form.errors.bot_username }}
                    </p>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex items-center justify-between pt-2">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-xs transition-all hover:bg-indigo-700 active:scale-95 disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                >
                    <Loader2
                        v-if="form.processing"
                        class="h-4 w-4 animate-spin"
                    />
                    <Save v-else class="h-4 w-4" />
                    <span>Save Chat Configuration</span>
                </button>

                <!-- Danger Zone: Purge Chat -->
                <button
                    type="button"
                    @click="handleClearMessages"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50/60 px-3.5 py-2 text-xs font-bold text-rose-600 transition hover:bg-rose-100 active:scale-95 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-400 dark:hover:bg-rose-950/80"
                >
                    <Trash2 class="h-3.5 w-3.5" />
                    <span>Clear All Messages</span>
                </button>
            </div>
        </form>
    </div>
</template>
