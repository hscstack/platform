<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Flag,
    CheckCircle,
    XCircle,
    Trash2,
    Settings,
    Ban,
    ExternalLink,
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import ChatBanModal from '@/components/ChatBanModal.vue';
import type { ChatBanUser } from '@/components/ChatBanModal.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { usePermissions } from '@/lib/usePermissions';

defineOptions({ layout: AdminLayout });

const { can } = usePermissions();

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
        banned_until: string | null;
        is_banned: boolean;
    } | null;
    reportable_type: string;
    reportable_id: number | null;
    post_slug?: string | null;
    post_title?: string | null;
    content_snapshot: string;
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

const currentFilter = ref<'all' | 'pending' | 'reviewed' | 'dismissed'>(
    'pending',
);

const filteredReports = computed(() => {
    if (currentFilter.value === 'all') {
        return props.reports;
    }

    return props.reports.filter((r) => r.status === currentFilter.value);
});

const updateReportStatus = (reportId: number, status: string) => {
    router.patch(
        `/admin/forums/reports/${reportId}/status`,
        { status },
        { preserveScroll: true },
    );
};

const deleteReport = (reportId: number) => {
    if (
        confirm(
            'Are you sure you want to permanently delete this report record?',
        )
    ) {
        router.delete(`/admin/forums/reports/${reportId}`, {
            preserveScroll: true,
        });
    }
};

const handleClearReports = (statusFilter?: string) => {
    const isFiltered = statusFilter && statusFilter !== 'all';
    const confirmMessage = isFiltered
        ? `Are you sure you want to permanently delete all ${statusFilter} forum report records?`
        : 'Are you sure you want to permanently delete ALL forum report records?';

    if (confirm(confirmMessage)) {
        router.delete('/admin/forums/reports/clear', {
            data: isFiltered ? { status: statusFilter } : {},
            preserveScroll: true,
        });
    }
};

const isBanModalOpen = ref(false);
const selectedUser = ref<ChatBanUser | null>(null);

const openBanModal = (report: ReportItem) => {
    if (!report.reported_user_id) {
        return;
    }

    selectedUser.value = {
        id: report.reported_user_id,
        name: report.reported_user?.name || report.reported_user_name || 'User',
        username:
            report.reported_user?.username ||
            report.reported_user_username ||
            null,
        banned_until: report.reported_user?.banned_until || null,
        is_banned: report.reported_user?.is_banned ?? false,
    };

    isBanModalOpen.value = true;
};

const formatDate = (isoString?: string | null) => {
    if (!isoString) {
        return '';
    }

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
    <Head title="Reported Content - Forum Admin" />

    <div class="space-y-5">
        <!-- Minimal Top Header -->
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1
                    class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl dark:text-gray-100"
                >
                    Reported Forum Content
                </h1>
                <p class="text-xs text-slate-500 dark:text-gray-400">
                    Review and act on community reports against forum questions
                    and answers.
                </p>
            </div>

            <!-- Header Quick Tabs & Clear Action -->
            <div class="flex max-w-full flex-wrap items-center gap-2">
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
                        class="flex shrink-0 items-center gap-1.5 rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-600 dark:bg-rose-950/60 dark:text-rose-400"
                    >
                        <Flag class="h-3.5 w-3.5" />
                        <span>Reports</span>
                        <span
                            v-if="pendingCount > 0"
                            class="py-0.2 rounded-full bg-rose-500 px-1.5 text-[10px] font-bold text-white"
                        >
                            {{ pendingCount }}
                        </span>
                    </Link>

                    <Link
                        href="/admin/forums/settings"
                        class="flex shrink-0 items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                    >
                        <Settings class="h-3.5 w-3.5" />
                        <span>Settings</span>
                    </Link>
                </div>

                <button
                    v-if="reports.length > 0"
                    type="button"
                    @click="
                        handleClearReports(
                            currentFilter === 'all' ? undefined : currentFilter,
                        )
                    "
                    class="inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-600 transition hover:bg-rose-100 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-400"
                >
                    <Trash2 class="h-3.5 w-3.5" />
                    <span>Clear</span>
                </button>
            </div>
        </div>

        <!-- Filter Status Pills -->
        <div
            class="flex flex-wrap items-center gap-1.5 rounded-2xl border border-slate-200/90 bg-white p-3 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
        >
            <button
                type="button"
                @click="currentFilter = 'pending'"
                class="cursor-pointer rounded-xl px-3 py-1.5 text-xs font-bold transition"
                :class="[
                    currentFilter === 'pending'
                        ? 'bg-rose-600 text-white'
                        : 'bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300',
                ]"
            >
                Pending ({{ pendingCount }})
            </button>

            <button
                type="button"
                @click="currentFilter = 'reviewed'"
                class="cursor-pointer rounded-xl px-3 py-1.5 text-xs font-bold transition"
                :class="[
                    currentFilter === 'reviewed'
                        ? 'bg-emerald-600 text-white'
                        : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300',
                ]"
            >
                Resolved ({{ reviewedCount }})
            </button>

            <button
                type="button"
                @click="currentFilter = 'dismissed'"
                class="cursor-pointer rounded-xl px-3 py-1.5 text-xs font-bold transition"
                :class="[
                    currentFilter === 'dismissed'
                        ? 'bg-slate-800 text-white dark:bg-gray-700'
                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300',
                ]"
            >
                Dismissed ({{ dismissedCount }})
            </button>

            <button
                type="button"
                @click="currentFilter = 'all'"
                class="cursor-pointer rounded-xl px-3 py-1.5 text-xs font-bold transition"
                :class="[
                    currentFilter === 'all'
                        ? 'bg-slate-900 text-white dark:bg-gray-100 dark:text-gray-900'
                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300',
                ]"
            >
                All ({{ reports.length }})
            </button>
        </div>

        <!-- Reports List -->
        <div class="space-y-4">
            <div
                v-if="filteredReports.length === 0"
                class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 py-16 text-center dark:border-gray-800 dark:bg-gray-900/40"
            >
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-white shadow-2xs dark:bg-gray-800 dark:text-gray-400"
                >
                    <Flag class="h-6 w-6 text-slate-400 dark:text-gray-500" />
                </div>
                <h3
                    class="mt-3 text-sm font-bold text-slate-800 dark:text-gray-200"
                >
                    No {{ currentFilter !== 'all' ? currentFilter : '' }} forum
                    reports found
                </h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">
                    The forum is clear of flagged discussions or answers under
                    this filter.
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
                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                    >
                        <!-- Left Info -->
                        <div class="space-y-1.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-bold tracking-wider uppercase"
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

                                <span
                                    class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-700 dark:bg-indigo-950/70 dark:text-indigo-300"
                                >
                                    {{
                                        report.reportable_type === 'ForumPost'
                                            ? 'Forum Question'
                                            : 'Forum Answer'
                                    }}
                                </span>

                                <span
                                    class="text-xs text-slate-400 dark:text-gray-500"
                                >
                                    Reported {{ formatDate(report.created_at) }}
                                </span>

                                <span
                                    v-if="report.reporter"
                                    class="text-xs text-slate-500 dark:text-gray-400"
                                >
                                    by
                                    <strong
                                        class="text-slate-700 dark:text-gray-300"
                                        >{{ report.reporter.name }}</strong
                                    >
                                    <span
                                        v-if="report.reporter.username"
                                        class="text-slate-400"
                                    >
                                        (@{{ report.reporter.username }})
                                    </span>
                                </span>
                            </div>

                            <div class="flex items-center gap-2 pt-0.5">
                                <span
                                    class="text-xs font-semibold text-slate-500 dark:text-gray-400"
                                    >Reason:</span
                                >
                                <span
                                    class="rounded-md bg-amber-50 px-2 py-0.5 text-xs font-bold text-amber-800 dark:bg-amber-950/50 dark:text-amber-300"
                                >
                                    {{
                                        report.reason ||
                                        'Flagged for moderation'
                                    }}
                                </span>
                            </div>
                        </div>

                        <!-- Right Actions -->
                        <div class="flex flex-wrap items-center gap-2">
                            <!-- Ban Modal trigger (requires chat or forum management permission) -->
                            <button
                                v-if="
                                    report.reported_user_id &&
                                    (can('manage chat') || can('manage forums'))
                                "
                                type="button"
                                @click="openBanModal(report)"
                                class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold transition"
                                :class="
                                    report.reported_user?.is_banned
                                        ? 'bg-amber-50 text-amber-800 hover:bg-amber-100 dark:bg-amber-950/60 dark:text-amber-300'
                                        : 'bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/60 dark:text-rose-300'
                                "
                            >
                                <Ban class="h-3 w-3" />
                                <span>{{
                                    report.reported_user?.is_banned
                                        ? 'Edit Suspension'
                                        : 'Suspend Author'
                                }}</span>
                            </button>

                            <!-- Resolve -->
                            <button
                                v-if="report.status !== 'reviewed'"
                                type="button"
                                @click="
                                    updateReportStatus(report.id, 'reviewed')
                                "
                                class="inline-flex cursor-pointer items-center gap-1 rounded-xl bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100 dark:bg-emerald-950/60 dark:text-emerald-300"
                            >
                                <CheckCircle class="h-3 w-3" />
                                <span>Resolve</span>
                            </button>

                            <!-- Dismiss -->
                            <button
                                v-if="report.status !== 'dismissed'"
                                type="button"
                                @click="
                                    updateReportStatus(report.id, 'dismissed')
                                "
                                class="inline-flex cursor-pointer items-center gap-1 rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300"
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

                    <!-- Snapshot Box -->
                    <div
                        class="mt-3.5 rounded-xl border border-slate-200/80 bg-slate-50/70 p-3.5 dark:border-gray-800 dark:bg-gray-800/60"
                    >
                        <div
                            class="flex flex-col gap-1.5 border-b border-slate-200/60 pb-2 text-[11px] text-slate-500 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700/60 dark:text-gray-400"
                        >
                            <div>
                                <span
                                    class="font-bold text-slate-800 dark:text-gray-200"
                                >
                                    Author:
                                    {{
                                        report.reported_user_name ||
                                        report.reported_user?.name ||
                                        'Unknown'
                                    }}
                                </span>
                                <span
                                    v-if="
                                        report.reported_user_username ||
                                        report.reported_user?.username
                                    "
                                    class="ml-1 text-slate-400"
                                >
                                    (@{{
                                        report.reported_user_username ||
                                        report.reported_user?.username
                                    }})
                                </span>
                                <span
                                    v-if="report.reported_user?.is_banned"
                                    class="ml-2 rounded-sm bg-rose-100 px-1.5 py-0.5 text-[9px] font-bold text-rose-700 dark:bg-rose-900/60 dark:text-rose-300"
                                >
                                    Suspended
                                </span>
                            </div>

                            <a
                                v-if="report.post_slug"
                                :href="`/forum/questions/${report.post_slug}`"
                                target="_blank"
                                class="inline-flex items-center gap-1 font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                            >
                                <span>View Discussion</span>
                                <ExternalLink class="h-3 w-3" />
                            </a>
                        </div>

                        <p
                            class="mt-2 text-xs leading-relaxed font-medium break-words whitespace-pre-wrap text-slate-900 dark:text-gray-100"
                        >
                            {{ report.content_snapshot }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Suspension Modal -->
        <ChatBanModal
            :is-open="isBanModalOpen"
            :user="selectedUser"
            @close="isBanModalOpen = false"
        />
    </div>
</template>
