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
} from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps<{
    settings: {
        enabled: boolean;
        audience: string;
        cooldown_seconds: number;
    };
    totalMessages: number;
    recentMessagesCount: number;
    pendingReportsCount?: number;
}>();

const form = useForm({
    enabled: props.settings.enabled,
    audience: props.settings.audience,
    cooldown_seconds: props.settings.cooldown_seconds,
});

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
                        form.enabled && form.audience !== 'disabled'
                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'
                            : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'
                    "
                >
                    <span
                        class="h-2 w-2 rounded-full"
                        :class="
                            form.enabled && form.audience !== 'disabled'
                                ? 'animate-pulse bg-emerald-500'
                                : 'bg-rose-500'
                        "
                    ></span>
                    {{
                        form.enabled && form.audience !== 'disabled'
                            ? 'Chat Live'
                            : 'Chat Inactive'
                    }}
                </span>
            </div>
        </div>

        <!-- Global Chat Management Route Tabs -->
        <div class="flex items-center gap-2 border-b border-slate-200 dark:border-gray-800">
            <Link
                href="/admin/chat"
                class="flex items-center gap-2 border-b-2 px-4 py-2.5 text-xs font-bold transition-all border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400"
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
                    class="rounded-full bg-rose-500 px-1.5 py-0.2 text-[10px] font-bold text-white"
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
                    >Auto-pruning maintains max 200</span
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
                    >Per-user delay between messages</span
                >
            </div>
        </div>

        <!-- Chat Configuration Form -->
        <form
            @submit.prevent="submitSettings"
            class="space-y-6 rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-7 dark:border-gray-800 dark:bg-gray-900"
        >
            <!-- 1. Master Toggle -->
            <div
                class="flex flex-col gap-4 border-b border-slate-100 pb-5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800"
            >
                <div>
                    <label
                        class="text-sm font-bold text-slate-900 dark:text-gray-100"
                    >
                        Enable Global Chat Service
                    </label>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                        Turn the global student chat completely on or off
                        globally.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="form.enabled = !form.enabled"
                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                        :class="
                            form.enabled
                                ? 'bg-indigo-600 dark:bg-indigo-500'
                                : 'bg-slate-200 dark:bg-gray-700'
                        "
                    >
                        <span
                            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                            :class="
                                form.enabled ? 'translate-x-5' : 'translate-x-0'
                            "
                        ></span>
                    </button>
                    <span
                        class="text-xs font-bold text-slate-700 dark:text-gray-300"
                    >
                        {{ form.enabled ? 'Enabled' : 'Disabled' }}
                    </span>
                </div>
            </div>

            <!-- 2. Audience Eligibility -->
            <div
                class="space-y-3 border-b border-slate-100 pb-6 dark:border-gray-800"
            >
                <div>
                    <label
                        class="flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-gray-100"
                    >
                        <Users class="h-4 w-4 text-indigo-500" />
                        Target Audience & Rollout Eligibility
                    </label>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                        Choose who is permitted to send messages in the student
                        lounge without code changes.
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
                                >Read-Only Mode</span
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
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-bold text-slate-800 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-indigo-400"
                        />
                        <span
                            class="absolute top-1/2 right-3 -translate-y-1/2 text-xs font-medium text-slate-400"
                        >
                            seconds
                        </span>
                    </div>
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
