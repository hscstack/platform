<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    Flag,
    CheckCircle,
    Trash2,
    Ban,
    MessageSquare,
    MessageCircle,
    FileText,
    BookOpen,
    Filter,
    Eye,
    ShieldAlert,
    Save,
    Loader2,
} from 'lucide-vue-next';
import { ref } from 'vue';
import BaseModal from '@/components/BaseModal.vue';
import ChatBanModal from '@/components/ChatBanModal.vue';
import type { ChatBanUser } from '@/components/ChatBanModal.vue';
import EmptyState from '@/components/EmptyState.vue';
import Pagination from '@/components/Pagination.vue';
import { formatDateTime } from '@/lib/useDate';

interface ReportItem {
    id: number;
    reporter_id: number | null;
    reporter: {
        id: number;
        name: string;
        username: string;
        image_path?: string | null;
    } | null;
    reported_user_id: number | null;
    reported_user_name: string | null;
    reported_user_username: string | null;
    reported_user: {
        id: number;
        name: string;
        username: string;
        image_path?: string | null;
        banned_until: string | null;
        is_verified?: boolean;
    } | null;
    reportable_type: string;
    reportable_id: number;
    content_snapshot: string;
    message_sent_at: string | null;
    reason: string | null;
    details: string | null;
    status: 'pending' | 'resolved' | 'dismissed';
    reviewed_by: number | null;
    reviewer?: {
        id: number;
        name: string;
        username: string;
    } | null;
    reviewed_at: string | null;
    action_taken: string | null;
    created_at: string;
}

const props = defineProps<{
    reports: {
        data: ReportItem[];
        links: any[];
        current_page: number;
        last_page: number;
    };
    counts: {
        all: number;
        pending: number;
        resolved: number;
        dismissed: number;
    };
    filters: {
        status: string;
        type: string;
    };
    safety_settings?: {
        banned_words: string;
        profanity_filter_enabled: boolean;
    };
}>();

const isBannedWordsModalOpen = ref(false);

const bannedWordsForm = useForm({
    banned_words: props.safety_settings?.banned_words ?? '',
    profanity_filter_enabled:
        props.safety_settings?.profanity_filter_enabled ?? true,
});

const submitBannedWords = () => {
    bannedWordsForm.post('/admin/reports/banned-words', {
        preserveScroll: true,
        onSuccess: () => {
            isBannedWordsModalOpen.value = false;
        },
    });
};

const currentStatus = ref(props.filters.status || 'pending');
const currentType = ref(props.filters.type || 'all');

const applyFilters = () => {
    router.get(
        '/admin/reports',
        {
            status:
                currentStatus.value !== 'all' ? currentStatus.value : undefined,
            type: currentType.value !== 'all' ? currentType.value : undefined,
        },
        { preserveState: true, replace: true },
    );
};

const updateStatus = (reportId: number, status: 'resolved' | 'dismissed') => {
    router.patch(
        `/admin/reports/${reportId}/status`,
        { status },
        { preserveScroll: true },
    );
};

const deleteReport = (reportId: number) => {
    if (confirm('Are you sure you want to delete this report?')) {
        router.delete(`/admin/reports/${reportId}`, { preserveScroll: true });
    }
};

const clearReports = () => {
    const label = currentStatus.value !== 'all' ? currentStatus.value : 'all';

    if (confirm(`Are you sure you want to clear all ${label} reports?`)) {
        router.delete('/admin/reports/clear', {
            data: {
                status:
                    currentStatus.value !== 'all'
                        ? currentStatus.value
                        : undefined,
            },
            preserveScroll: true,
        });
    }
};

// Details Modal State
const viewingReport = ref<ReportItem | null>(null);

const openDetailsModal = (report: ReportItem) => {
    viewingReport.value = report;
};

// Suspension Modal State
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
            report.reported_user?.username || report.reported_user_username,
        banned_until: report.reported_user?.banned_until || null,
        is_banned: Boolean(report.reported_user?.banned_until),
    };
    isBanModalOpen.value = true;
};

const getTypeMeta = (type: string) => {
    if (type.includes('ChatMessage')) {
        return {
            label: 'Chat',
            icon: MessageCircle,
            color: 'text-sky-600 bg-sky-50 dark:bg-sky-950/40 dark:text-sky-400',
        };
    }

    if (type.includes('ForumPost')) {
        return {
            label: 'Question',
            icon: MessageSquare,
            color: 'text-indigo-600 bg-indigo-50 dark:bg-indigo-950/40 dark:text-indigo-400',
        };
    }

    if (type.includes('ForumAnswer')) {
        return {
            label: 'Answer',
            icon: MessageSquare,
            color: 'text-purple-600 bg-purple-50 dark:bg-purple-950/40 dark:text-purple-400',
        };
    }

    if (type.includes('ResourceComment')) {
        return {
            label: 'Resource Comment',
            icon: FileText,
            color: 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40 dark:text-emerald-400',
        };
    }

    if (type.includes('BlogComment')) {
        return {
            label: 'Blog Comment',
            icon: BookOpen,
            color: 'text-rose-600 bg-rose-50 dark:bg-rose-950/40 dark:text-rose-400',
        };
    }

    return {
        label: 'Content',
        icon: Flag,
        color: 'text-slate-600 bg-slate-50 dark:bg-slate-800 dark:text-slate-400',
    };
};
</script>

<template>
    <Head title="Reports - Admin" />

    <div class="w-full space-y-5">
        <!-- Header -->
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1
                    class="flex items-center gap-2 text-xl font-bold tracking-tight text-slate-900 sm:text-2xl dark:text-white"
                >
                    <Flag class="h-5 w-5 text-rose-500" />
                    <span>Reports & Safety</span>
                </h1>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                    Review and act on reported community messages, forum
                    discussions, and comments.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="isBannedWordsModalOpen = true"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
                >
                    <ShieldAlert class="h-3.5 w-3.5 text-amber-500" />
                    <span>Banned Keywords</span>
                </button>

                <button
                    v-if="reports.data.length > 0"
                    type="button"
                    @click="clearReports"
                    class="inline-flex cursor-pointer items-center gap-1.5 self-start rounded-xl border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-600 shadow-2xs hover:bg-rose-50 sm:self-auto dark:border-rose-900/60 dark:bg-gray-900 dark:text-rose-400 dark:hover:bg-rose-950/40"
                >
                    <Trash2 class="h-3.5 w-3.5" />
                    <span
                        >Clear
                        {{
                            currentStatus !== 'all' ? currentStatus : 'All'
                        }}</span
                    >
                </button>
            </div>
        </div>

        <!-- Filter Bar -->
        <div
            class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200/80 bg-white p-3 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
        >
            <!-- Status Tabs -->
            <div class="flex flex-wrap items-center gap-1">
                <button
                    type="button"
                    @click="
                        currentStatus = 'all';
                        applyFilters();
                    "
                    class="rounded-xl px-3 py-1.5 text-xs font-bold transition"
                    :class="[
                        currentStatus === 'all'
                            ? 'bg-indigo-600 text-white shadow-2xs'
                            : 'text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800',
                    ]"
                >
                    All ({{ counts.all }})
                </button>
                <button
                    type="button"
                    @click="
                        currentStatus = 'pending';
                        applyFilters();
                    "
                    class="rounded-xl px-3 py-1.5 text-xs font-bold transition"
                    :class="[
                        currentStatus === 'pending'
                            ? 'bg-amber-500 text-white shadow-2xs'
                            : 'text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800',
                    ]"
                >
                    Pending ({{ counts.pending }})
                </button>
                <button
                    type="button"
                    @click="
                        currentStatus = 'resolved';
                        applyFilters();
                    "
                    class="rounded-xl px-3 py-1.5 text-xs font-bold transition"
                    :class="[
                        currentStatus === 'resolved'
                            ? 'bg-emerald-600 text-white shadow-2xs'
                            : 'text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800',
                    ]"
                >
                    Resolved ({{ counts.resolved }})
                </button>
                <button
                    type="button"
                    @click="
                        currentStatus = 'dismissed';
                        applyFilters();
                    "
                    class="rounded-xl px-3 py-1.5 text-xs font-bold transition"
                    :class="[
                        currentStatus === 'dismissed'
                            ? 'bg-slate-700 text-white shadow-2xs dark:bg-gray-700'
                            : 'text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800',
                    ]"
                >
                    Dismissed ({{ counts.dismissed }})
                </button>
            </div>

            <!-- Content Type Filter -->
            <div class="flex items-center gap-2">
                <Filter class="h-3.5 w-3.5 text-slate-400" />
                <select
                    v-model="currentType"
                    @change="applyFilters"
                    class="h-8 rounded-xl border border-slate-200 bg-slate-50 px-2.5 text-xs font-semibold text-slate-700 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-200"
                >
                    <option value="all">All Content</option>
                    <option value="chat">Chat</option>
                    <option value="forum_post">Forum Questions</option>
                    <option value="forum_answer">Forum Answers</option>
                    <option value="resource_comment">Resource Comments</option>
                    <option value="blog_comment">Blog Comments</option>
                </select>
            </div>
        </div>

        <!-- Clean Reports Table / Feed -->
        <div
            class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-2xs dark:border-gray-800 dark:bg-gray-900"
        >
            <EmptyState
                v-if="reports.data.length === 0"
                :icon="Flag"
                title="No reports found"
                description="All clean! No community reports match your current filters."
            />

            <div v-else class="divide-y divide-slate-100 dark:divide-gray-800">
                <div
                    v-for="report in reports.data"
                    :key="report.id"
                    class="flex flex-col gap-3 p-3.5 transition-colors hover:bg-slate-50/70 sm:flex-row sm:items-center sm:justify-between dark:hover:bg-gray-800/50"
                >
                    <!-- Left: Metadata, Snippet & Offender -->
                    <div class="min-w-0 flex-1 space-y-1.5">
                        <div class="flex flex-wrap items-center gap-2">
                            <!-- Status Badge -->
                            <span
                                class="rounded-md px-2 py-0.5 text-[10px] font-bold capitalize"
                                :class="[
                                    report.status === 'pending'
                                        ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300'
                                        : report.status === 'resolved'
                                          ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300'
                                          : 'bg-slate-100 text-slate-700 dark:bg-gray-800 dark:text-gray-300',
                                ]"
                            >
                                {{ report.status }}
                            </span>

                            <!-- Reason Badge -->
                            <span
                                class="rounded-md bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-700 dark:bg-rose-950/40 dark:text-rose-300"
                            >
                                {{ report.reason }}
                            </span>

                            <span class="text-[11px] text-slate-400">
                                {{ formatDateTime(report.created_at) }}
                            </span>
                        </div>

                        <!-- Content Preview Line (Truncated cleanly) -->
                        <p
                            class="truncate text-xs font-medium text-slate-800 dark:text-gray-200"
                        >
                            "{{
                                report.content_snapshot ||
                                '(Empty content snapshot)'
                            }}"
                        </p>

                        <!-- Offender Only -->
                        <div
                            class="flex flex-wrap items-center gap-x-2 text-xs text-slate-500 dark:text-gray-400"
                        >
                            <span class="inline-flex items-center gap-1">
                                <span class="font-bold text-slate-400"
                                    >Offender:</span
                                >
                                <strong
                                    class="text-slate-800 dark:text-gray-200"
                                    >{{
                                        report.reported_user?.name ||
                                        report.reported_user_name ||
                                        'User'
                                    }}</strong
                                >
                                <span
                                    v-if="report.reported_user_username"
                                    class="text-slate-400"
                                    >(@{{
                                        report.reported_user_username
                                    }})</span
                                >
                                <span
                                    v-if="report.reported_user?.banned_until"
                                    class="ml-1 rounded-sm bg-rose-100 px-1 text-[9px] font-bold text-rose-700 dark:bg-rose-950 dark:text-rose-300"
                                >
                                    Suspended
                                </span>
                            </span>
                        </div>
                    </div>

                    <!-- Right: Actions (Details & Delete only) -->
                    <div
                        class="flex shrink-0 items-center gap-1.5 self-end sm:self-center"
                    >
                        <!-- View Details Modal Trigger -->
                        <button
                            type="button"
                            @click="openDetailsModal(report)"
                            class="inline-flex cursor-pointer items-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                            title="View Full Report Details"
                        >
                            <Eye class="h-3.5 w-3.5 text-indigo-500" />
                            <span>Details</span>
                        </button>

                        <!-- Delete Button -->
                        <button
                            type="button"
                            @click="deleteReport(report.id)"
                            class="cursor-pointer rounded-xl p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40"
                            title="Delete Report"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div
                v-if="reports.links && reports.links.length > 3"
                class="border-t border-slate-100 bg-slate-50/40 px-4 py-2 dark:border-gray-800 dark:bg-gray-900"
            >
                <Pagination
                    :links="reports.links"
                    :current-page="reports.current_page"
                    :last-page="reports.last_page"
                />
            </div>
        </div>

        <!-- Full Report Details Modal -->
        <BaseModal
            :is-open="Boolean(viewingReport)"
            title="Report Details"
            subtitle="Full context and audit information for this report."
            max-width="lg"
            @close="viewingReport = null"
        >
            <div v-if="viewingReport" class="space-y-4 p-4 text-xs sm:p-6">
                <!-- Meta tags -->
                <div
                    class="flex flex-wrap items-center gap-2 border-b border-slate-100 pb-3 dark:border-gray-800"
                >
                    <span
                        class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-bold"
                        :class="
                            getTypeMeta(viewingReport.reportable_type).color
                        "
                    >
                        <component
                            :is="
                                getTypeMeta(viewingReport.reportable_type).icon
                            "
                            class="h-3.5 w-3.5"
                        />
                        <span>{{
                            getTypeMeta(viewingReport.reportable_type).label
                        }}</span>
                    </span>

                    <span
                        class="rounded-md bg-rose-50 px-2 py-0.5 text-xs font-bold text-rose-700 dark:bg-rose-950/40 dark:text-rose-300"
                    >
                        Reason: {{ viewingReport.reason }}
                    </span>

                    <span
                        class="rounded-md px-2 py-0.5 text-xs font-bold capitalize"
                        :class="[
                            viewingReport.status === 'pending'
                                ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300'
                                : viewingReport.status === 'resolved'
                                  ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300'
                                  : 'bg-slate-100 text-slate-700 dark:bg-gray-800 dark:text-gray-300',
                        ]"
                    >
                        Status: {{ viewingReport.status }}
                    </span>
                </div>

                <!-- Full Snapshot Box -->
                <div>
                    <span
                        class="mb-1 block text-[11px] font-bold text-slate-400 uppercase"
                        >Reported Content Snapshot:</span
                    >
                    <div
                        class="max-h-60 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50/70 p-3 font-sans leading-relaxed whitespace-pre-wrap text-slate-800 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-200"
                    >
                        {{
                            viewingReport.content_snapshot ||
                            '(No snapshot content available)'
                        }}
                    </div>
                </div>

                <!-- Reporter Details Note -->
                <div v-if="viewingReport.details">
                    <span
                        class="mb-1 block text-[11px] font-bold text-slate-400 uppercase"
                        >Reporter Explanation:</span
                    >
                    <div
                        class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-3 text-indigo-950 dark:border-indigo-950/40 dark:bg-indigo-950/30 dark:text-indigo-300"
                    >
                        {{ viewingReport.details }}
                    </div>
                </div>

                <!-- User Details Grid -->
                <div
                    class="grid grid-cols-2 gap-3 rounded-xl border border-slate-100 bg-slate-50/40 p-3 dark:border-gray-800/60 dark:bg-gray-800/30"
                >
                    <div>
                        <span
                            class="block text-[10px] font-bold text-slate-400 uppercase"
                            >Reported User:</span
                        >
                        <p
                            class="mt-0.5 font-bold text-slate-900 dark:text-white"
                        >
                            {{
                                viewingReport.reported_user?.name ||
                                viewingReport.reported_user_name ||
                                'User'
                            }}
                        </p>
                        <p
                            v-if="viewingReport.reported_user_username"
                            class="text-slate-400"
                        >
                            @{{ viewingReport.reported_user_username }}
                        </p>
                        <p
                            v-if="viewingReport.reported_user?.banned_until"
                            class="mt-1 font-semibold text-rose-600"
                        >
                            Currently Suspended until
                            {{
                                formatDateTime(
                                    viewingReport.reported_user.banned_until,
                                )
                            }}
                        </p>
                    </div>

                    <div>
                        <span
                            class="block text-[10px] font-bold text-slate-400 uppercase"
                            >Reported By:</span
                        >
                        <p
                            class="mt-0.5 font-bold text-slate-800 dark:text-gray-200"
                        >
                            {{ viewingReport.reporter?.name || 'Unknown' }}
                        </p>
                        <p
                            v-if="viewingReport.reporter?.username"
                            class="text-slate-400"
                        >
                            @{{ viewingReport.reporter.username }}
                        </p>
                        <p class="mt-1 text-[11px] text-slate-400">
                            {{ formatDateTime(viewingReport.created_at) }}
                        </p>
                    </div>
                </div>

                <!-- Review Audit -->
                <div
                    v-if="viewingReport.reviewer"
                    class="rounded-xl bg-emerald-50/60 p-2.5 text-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300"
                >
                    <span
                        >Reviewed by
                        <strong>@{{ viewingReport.reviewer.username }}</strong>
                        on {{ formatDateTime(viewingReport.reviewed_at) }}</span
                    >
                </div>

                <!-- Modal Actions -->
                <div
                    class="flex items-center justify-between border-t border-slate-100 pt-3 dark:border-gray-800"
                >
                    <button
                        v-if="viewingReport.reported_user_id"
                        type="button"
                        @click="
                            openBanModal(viewingReport);
                            viewingReport = null;
                        "
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-rose-200 bg-white px-3 py-1.5 text-xs font-bold text-rose-600 shadow-2xs hover:bg-rose-50 dark:border-rose-900/60 dark:bg-gray-800 dark:text-rose-400 dark:hover:bg-rose-950/40"
                    >
                        <Ban class="h-3.5 w-3.5" />
                        <span>{{
                            viewingReport.reported_user?.banned_until
                                ? 'Edit Suspension'
                                : 'Suspend User'
                        }}</span>
                    </button>
                    <div v-else></div>

                    <div class="flex items-center gap-2">
                        <button
                            v-if="viewingReport.status !== 'resolved'"
                            type="button"
                            @click="
                                updateStatus(viewingReport.id, 'resolved');
                                viewingReport = null;
                            "
                            class="inline-flex cursor-pointer items-center gap-1 rounded-xl bg-emerald-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-2xs hover:bg-emerald-700"
                        >
                            <CheckCircle class="h-3.5 w-3.5" />
                            <span>Resolve</span>
                        </button>
                        <button
                            v-if="viewingReport.status !== 'dismissed'"
                            type="button"
                            @click="
                                updateStatus(viewingReport.id, 'dismissed');
                                viewingReport = null;
                            "
                            class="cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-200"
                        >
                            <span>Dismiss</span>
                        </button>
                    </div>
                </div>
            </div>
        </BaseModal>

        <!-- Centralized Suspension Modal -->
        <ChatBanModal
            :is-open="isBanModalOpen"
            :user="selectedUser"
            @close="isBanModalOpen = false"
        />

        <!-- Platform Banned Keywords Modal -->
        <BaseModal
            :is-open="isBannedWordsModalOpen"
            title="Platform Banned Keywords & Safety"
            subtitle="Centralized profanity filter for global chat, forum discussions, and comments."
            max-width="lg"
            @close="isBannedWordsModalOpen = false"
        >
            <form
                @submit.prevent="submitBannedWords"
                class="space-y-4 p-4 text-xs sm:p-6"
            >
                <!-- Profanity filter toggle -->
                <div
                    class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/60 p-3 dark:border-gray-800 dark:bg-gray-800/40"
                >
                    <div>
                        <span
                            class="block font-bold text-slate-800 dark:text-gray-200"
                        >
                            Automated Profanity & Safety Filter
                        </span>
                        <p class="text-[11px] text-slate-400">
                            Automatically block or flag messages and comments
                            containing prohibited words.
                        </p>
                    </div>
                    <input
                        v-model="bannedWordsForm.profanity_filter_enabled"
                        type="checkbox"
                        class="h-4.5 w-4.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                    />
                </div>

                <!-- Textarea -->
                <div class="space-y-1.5">
                    <label
                        class="block font-bold text-slate-700 dark:text-gray-300"
                    >
                        Prohibited Words & Offensive Roots (separated by commas
                        or newlines)
                    </label>
                    <textarea
                        v-model="bannedWordsForm.banned_words"
                        rows="6"
                        placeholder="e.g. abusive_word, spam_domain.com, scam_keyword"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 p-3 font-mono text-xs text-slate-800 outline-none focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                    ></textarea>
                    <p class="text-[11px] text-slate-400">
                        Applies across Global Chat, Forum posts/answers,
                        Resource comments, and Blog comments.
                    </p>
                </div>

                <!-- Action buttons -->
                <div
                    class="flex items-center justify-end gap-2 border-t border-slate-100 pt-3 dark:border-gray-800"
                >
                    <button
                        type="button"
                        @click="isBannedWordsModalOpen = false"
                        class="cursor-pointer rounded-xl border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-200"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="bannedWordsForm.processing"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-1.5 text-xs font-bold text-white shadow-2xs hover:bg-indigo-700 disabled:opacity-50"
                    >
                        <Loader2
                            v-if="bannedWordsForm.processing"
                            class="h-3.5 w-3.5 animate-spin"
                        />
                        <Save v-else class="h-3.5 w-3.5" />
                        <span>{{
                            bannedWordsForm.processing
                                ? 'Saving...'
                                : 'Save Keywords'
                        }}</span>
                    </button>
                </div>
            </form>
        </BaseModal>
    </div>
</template>
