<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Flag,
    ShieldAlert,
    Pencil,
    CheckCircle,
    XCircle,
    Trash2,
    Clock,
    CheckCheck,
    MessageCircle,
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

interface ReportItem {
    id: number;
    reporter_id: number | null;
    reporter: {
        id: number;
        name: string;
        username: string;
    } | null;
    reported_user_id: number | null;
    reported_user_name: string | null;
    reported_user_username: string | null;
    reported_user: {
        id: number;
        name: string;
        username: string;
        chat_banned_until: string | null;
        is_chat_banned: boolean;
    } | null;
    message_content: string;
    message_sent_at: string | null;
    reason: string | null;
    status: 'pending' | 'reviewed' | 'dismissed';
    created_at: string;
}

const props = defineProps<{
    reports: ReportItem[];
    pendingCount: number;
    reviewedCount: number;
    dismissedCount: number;
}>();

const currentFilter = ref<'all' | 'pending' | 'reviewed' | 'dismissed'>('pending');

const filteredReports = computed(() => {
    if (currentFilter.value === 'all') {
        return props.reports;
    }
    return props.reports.filter((r) => r.status === currentFilter.value);
});

const updateReportStatus = (reportId: number, status: string) => {
    router.patch(
        `/admin/chat/reports/${reportId}/status`,
        { status },
        { preserveScroll: true },
    );
};

const deleteReport = (reportId: number) => {
    if (confirm('Are you sure you want to permanently delete this report record?')) {
        router.delete(`/admin/chat/reports/${reportId}`, {
            preserveScroll: true,
        });
    }
};

const formatDate = (isoString?: string | null) => {
    if (!isoString) return '';
    try {
        const d = new Date(isoString);
        return d.toLocaleString([], {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return '';
    }
};
</script>

<template>
    <Head title="Reported Chat Messages - Staff Panel" />

    <div class="space-y-6">
        <!-- Header -->
        <div
            class="flex flex-col gap-3 border-b border-slate-100 pb-5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800"
        >
            <div class="flex items-center gap-3">
                <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400"
                >
                    <Flag class="h-5 w-5" />
                </div>
                <div>
                    <h1
                        class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl dark:text-gray-100"
                    >
                        Reported Chat Messages
                    </h1>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                        Review community reports, snapshot of reported messages, and moderate disruptive accounts.
                    </p>
                </div>
            </div>

        </div>

        <!-- Global Chat Management Route Tabs -->
        <div class="flex items-center gap-2 border-b border-slate-200 dark:border-gray-800">
            <Link
                href="/admin/chat"
                class="flex items-center gap-2 border-b-2 border-transparent px-4 py-2.5 text-xs font-bold text-slate-500 transition-all hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-200"
            >
                <MessageCircle class="h-4 w-4" />
                <span>Chat Configuration</span>
            </Link>

            <Link
                href="/admin/chat/reports"
                class="flex items-center gap-2 border-b-2 border-rose-600 px-4 py-2.5 text-xs font-bold text-rose-600 transition-all dark:border-rose-400 dark:text-rose-400"
            >
                <Flag class="h-4 w-4 text-rose-500" />
                <span>Reported Messages</span>
                <span
                    v-if="pendingCount > 0"
                    class="rounded-full bg-rose-500 px-1.5 py-0.2 text-[10px] font-bold text-white"
                >
                    {{ pendingCount }}
                </span>
            </Link>
        </div>

        <!-- Metric Statistics Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div
                @click="currentFilter = 'pending'"
                class="cursor-pointer rounded-2xl border p-4 transition-all"
                :class="
                    currentFilter === 'pending'
                        ? 'border-rose-300 bg-rose-50/50 ring-2 ring-rose-500/20 dark:border-rose-800 dark:bg-rose-950/30'
                        : 'border-slate-100 bg-white hover:border-slate-200 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-gray-700'
                "
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-gray-400">
                        Pending Review
                    </span>
                    <Clock class="h-4 w-4 text-rose-500" />
                </div>
                <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-gray-100">
                    {{ pendingCount }}
                </p>
                <span class="text-[11px] text-slate-400 dark:text-gray-500">Requires moderation action</span>
            </div>

            <div
                @click="currentFilter = 'reviewed'"
                class="cursor-pointer rounded-2xl border p-4 transition-all"
                :class="
                    currentFilter === 'reviewed'
                        ? 'border-emerald-300 bg-emerald-50/50 ring-2 ring-emerald-500/20 dark:border-emerald-800 dark:bg-emerald-950/30'
                        : 'border-slate-100 bg-white hover:border-slate-200 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-gray-700'
                "
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-gray-400">
                        Resolved
                    </span>
                    <CheckCheck class="h-4 w-4 text-emerald-500" />
                </div>
                <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-gray-100">
                    {{ reviewedCount }}
                </p>
                <span class="text-[11px] text-slate-400 dark:text-gray-500">Reviewed & resolved reports</span>
            </div>

            <div
                @click="currentFilter = 'dismissed'"
                class="cursor-pointer rounded-2xl border p-4 transition-all"
                :class="
                    currentFilter === 'dismissed'
                        ? 'border-slate-400 bg-slate-100/70 ring-2 ring-slate-400/20 dark:border-gray-600 dark:bg-gray-800'
                        : 'border-slate-100 bg-white hover:border-slate-200 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-gray-700'
                "
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-gray-400">
                        Dismissed
                    </span>
                    <XCircle class="h-4 w-4 text-slate-400 dark:text-gray-400" />
                </div>
                <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-gray-100">
                    {{ dismissedCount }}
                </p>
                <span class="text-[11px] text-slate-400 dark:text-gray-500">Ignored or invalid reports</span>
            </div>
        </div>

        <!-- Filter Sub-Tabs -->
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 pb-3 dark:border-gray-800">
            <button
                type="button"
                @click="currentFilter = 'all'"
                class="cursor-pointer rounded-xl px-3 py-1.5 text-xs font-bold transition-all"
                :class="
                    currentFilter === 'all'
                        ? 'bg-indigo-600 text-white dark:bg-indigo-500'
                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                "
            >
                All ({{ reports.length }})
            </button>

            <button
                type="button"
                @click="currentFilter = 'pending'"
                class="cursor-pointer rounded-xl px-3 py-1.5 text-xs font-bold transition-all"
                :class="
                    currentFilter === 'pending'
                        ? 'bg-rose-600 text-white dark:bg-rose-500'
                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                "
            >
                Pending ({{ pendingCount }})
            </button>

            <button
                type="button"
                @click="currentFilter = 'reviewed'"
                class="cursor-pointer rounded-xl px-3 py-1.5 text-xs font-bold transition-all"
                :class="
                    currentFilter === 'reviewed'
                        ? 'bg-emerald-600 text-white dark:bg-emerald-500'
                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                "
            >
                Resolved ({{ reviewedCount }})
            </button>

            <button
                type="button"
                @click="currentFilter = 'dismissed'"
                class="cursor-pointer rounded-xl px-3 py-1.5 text-xs font-bold transition-all"
                :class="
                    currentFilter === 'dismissed'
                        ? 'bg-slate-700 text-white dark:bg-gray-700'
                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                "
            >
                Dismissed ({{ dismissedCount }})
            </button>
        </div>

        <!-- Reports List Feed -->
        <div class="space-y-4">
            <div
                v-if="filteredReports.length === 0"
                class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 py-16 text-center dark:border-gray-800 dark:bg-gray-900/40"
            >
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white shadow-2xs dark:bg-gray-800 dark:text-gray-400">
                    <Flag class="h-6 w-6 text-slate-400 dark:text-gray-500" />
                </div>
                <h3 class="mt-3 text-sm font-bold text-slate-800 dark:text-gray-200">
                    No {{ currentFilter !== 'all' ? currentFilter : '' }} reports found
                </h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">
                    Everything looks clean for this filter criteria.
                </p>
            </div>

            <div v-else class="space-y-3.5">
                <div
                    v-for="report in filteredReports"
                    :key="report.id"
                    class="rounded-2xl border p-4.5 transition-colors sm:p-5"
                    :class="[
                        report.status === 'pending'
                            ? 'border-rose-200 bg-white shadow-2xs dark:border-rose-900/50 dark:bg-gray-900'
                            : 'border-slate-200/80 bg-slate-50/60 dark:border-gray-800 dark:bg-gray-900/40',
                    ]"
                >
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <!-- Left: Reporter & Reported User info -->
                        <div class="space-y-1.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                    :class="
                                        report.status === 'pending'
                                            ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300'
                                            : report.status === 'reviewed'
                                              ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300'
                                              : 'bg-slate-200 text-slate-700 dark:bg-gray-800 dark:text-gray-300'
                                    "
                                >
                                    {{ report.status }}
                                </span>

                                <span class="text-xs text-slate-400 dark:text-gray-500">
                                    Reported {{ formatDate(report.created_at) }}
                                </span>

                                <span v-if="report.reporter" class="text-xs text-slate-500 dark:text-gray-400">
                                    by <strong class="text-slate-700 dark:text-gray-300">{{ report.reporter.name }}</strong>
                                    <span v-if="report.reporter.username" class="text-slate-400"> (@{{ report.reporter.username }})</span>
                                </span>
                            </div>

                            <div class="flex items-center gap-2 pt-0.5">
                                <span class="text-xs font-semibold text-slate-500 dark:text-gray-400">Reason:</span>
                                <span class="rounded-md bg-amber-50 px-2 py-0.5 text-xs font-bold text-amber-800 dark:bg-amber-950/50 dark:text-amber-300">
                                    {{ report.reason || 'Not specified' }}
                                </span>
                            </div>
                        </div>

                        <!-- Right: Action buttons & Moderation shortcut -->
                        <div class="flex flex-wrap items-center gap-2">
                            <!-- Direct link to edit/ban reported user -->
                            <Link
                                v-if="report.reported_user_id"
                                :href="`/admin/users/edit/${report.reported_user_id}`"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-700 transition hover:bg-rose-100 dark:bg-rose-950/60 dark:text-rose-300 dark:hover:bg-rose-900/60"
                                title="Open user profile to ban or moderate"
                            >
                                <Pencil class="h-3 w-3" />
                                <span>Edit / Ban Author</span>
                            </Link>

                            <!-- Mark Status Buttons -->
                            <button
                                v-if="report.status !== 'reviewed'"
                                type="button"
                                @click="updateReportStatus(report.id, 'reviewed')"
                                class="inline-flex cursor-pointer items-center gap-1 rounded-xl bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100 dark:bg-emerald-950/60 dark:text-emerald-300 dark:hover:bg-emerald-900/60"
                            >
                                <CheckCircle class="h-3 w-3" />
                                <span>Resolve</span>
                            </button>

                            <button
                                v-if="report.status !== 'dismissed'"
                                type="button"
                                @click="updateReportStatus(report.id, 'dismissed')"
                                class="inline-flex cursor-pointer items-center gap-1 rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                <XCircle class="h-3 w-3" />
                                <span>Dismiss</span>
                            </button>

                            <!-- Delete report record -->
                            <button
                                type="button"
                                @click="deleteReport(report.id)"
                                class="cursor-pointer rounded-xl p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 dark:hover:text-rose-400"
                                title="Delete Report Record"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>

                    <!-- Message Snapshot Content Box -->
                    <div class="mt-3.5 rounded-xl border border-slate-200/80 bg-slate-50/70 p-3.5 dark:border-gray-800 dark:bg-gray-800/60">
                        <div class="flex items-center justify-between border-b border-slate-200/60 pb-2 text-[11px] text-slate-500 dark:border-gray-700/60 dark:text-gray-400">
                            <div>
                                <span class="font-bold text-slate-800 dark:text-gray-200">
                                    Message Author: {{ report.reported_user_name || report.reported_user?.name || 'Unknown' }}
                                </span>
                                <span v-if="report.reported_user_username || report.reported_user?.username" class="ml-1 text-slate-400">
                                    (@{{ report.reported_user_username || report.reported_user?.username }})
                                </span>
                                <span
                                    v-if="report.reported_user?.is_chat_banned"
                                    class="ml-2 rounded-sm bg-rose-100 px-1.5 py-0.5 text-[9px] font-bold text-rose-700 dark:bg-rose-900/60 dark:text-rose-300"
                                >
                                    Currently Banned
                                </span>
                            </div>

                            <span v-if="report.message_sent_at">
                                Sent {{ formatDate(report.message_sent_at) }}
                            </span>
                        </div>

                        <p class="mt-2 text-xs leading-relaxed font-medium text-slate-900 break-words dark:text-gray-100">
                            "{{ report.message_content }}"
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
