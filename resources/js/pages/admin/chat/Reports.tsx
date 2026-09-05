import { Head, Link, router } from '@inertiajs/vue3';
import { Ban, Flag, MessageCircle, Trash2 } from 'lucide-vue-next';
import { computed, defineComponent, ref } from 'vue';
import type { PropType } from 'vue';

import ChatBanModal from '@/components/ChatBanModal.vue';
import type { ChatBanUser } from '@/components/ChatBanModal.vue';
import EmptyState from '@/components/EmptyState.vue';
import { formatDateTime } from '@/lib/useDate';

interface ReportReporter {
    id: number;
    name: string;
    username: string;
}

interface ReportReportedUser {
    id: number;
    name: string;
    username: string;
    banned_until: string | null;
    is_banned: boolean;
}

type ReportStatus = 'pending' | 'reviewed' | 'dismissed';
type ReportFilter = 'all' | ReportStatus;

interface ReportItem {
    id: number;
    reporter_id: number | null;
    reporter: ReportReporter | null;
    reported_user_id: number | null;
    reported_user_name: string | null;
    reported_user_username: string | null;
    reported_user: ReportReportedUser | null;
    message_content: string;
    message_sent_at: string | null;
    reason: string | null;
    status: ReportStatus;
    created_at: string;
}

export default defineComponent({
    name: 'AdminChatReports',
    props: {
        reports: { type: Array as PropType<ReportItem[]>, required: true },
        pendingCount: { type: Number, required: true },
        reviewedCount: { type: Number, required: true },
        dismissedCount: { type: Number, required: true },
    },
    setup(props) {
        const currentFilter = ref<ReportFilter>('pending');

        const filteredReports = computed<ReportItem[]>(() => {
            if (currentFilter.value === 'all') {
                return props.reports;
            }

            return props.reports.filter(
                (r) => r.status === currentFilter.value,
            );
        });

        const updateReportStatus = (
            reportId: number,
            status: ReportStatus,
        ): void => {
            router.patch(
                `/admin/chat/reports/${reportId}/status`,
                { status },
                { preserveScroll: true },
            );
        };

        const deleteReport = (reportId: number): void => {
            if (
                confirm(
                    'Are you sure you want to permanently delete this report record?',
                )
            ) {
                router.delete(`/admin/chat/reports/${reportId}`, {
                    preserveScroll: true,
                });
            }
        };

        const handleClearReports = (statusFilter?: ReportFilter): void => {
            const isFiltered = statusFilter && statusFilter !== 'all';
            const confirmMessage = isFiltered
                ? `Are you sure you want to permanently delete all ${statusFilter} report records? This action cannot be undone.`
                : 'Are you sure you want to permanently delete ALL report records? This action cannot be undone.';

            if (confirm(confirmMessage)) {
                router.delete('/admin/chat/reports/clear', {
                    data: isFiltered ? { status: statusFilter } : {},
                    preserveScroll: true,
                });
            }
        };

        const isBanModalOpen = ref<boolean>(false);
        const selectedUser = ref<ChatBanUser | null>(null);

        const openBanModal = (report: ReportItem): void => {
            if (!report.reported_user_id) {
                return;
            }

            selectedUser.value = {
                id: report.reported_user_id,
                name:
                    report.reported_user?.name ||
                    report.reported_user_name ||
                    'User',
                username:
                    report.reported_user?.username ||
                    report.reported_user_username ||
                    null,
                banned_until: report.reported_user?.banned_until || null,
                is_banned: report.reported_user?.is_banned ?? false,
            };

            isBanModalOpen.value = true;
        };

        const closeBanModal = (): void => {
            isBanModalOpen.value = false;
        };

        const formatDate = formatDateTime;

        const getAuthorName = (report: ReportItem): string =>
            report.reported_user_name ||
            report.reported_user?.name ||
            'Unknown';

        const getReporterName = (report: ReportItem): string =>
            report.reporter?.name || 'Someone';

        const statusDotClass = (status: ReportStatus): string => {
            if (status === 'pending') {
                return 'bg-rose-500';
            }

            if (status === 'reviewed') {
                return 'bg-emerald-500';
            }

            return 'bg-slate-300 dark:bg-gray-600';
        };

        const tabClass = (isActive: boolean): string =>
            isActive
                ? 'bg-white text-slate-900 shadow-2xs dark:bg-gray-800 dark:text-gray-100'
                : 'text-slate-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200';

        return () => (
            <>
                <Head title="Reported Chat Messages - Staff Panel" />

                <div class="space-y-6">
                    {/* Page header */}
                    <div class="flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                        <div>
                            <div class="flex items-center gap-1.5">
                                <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-gray-100">
                                    Reported Chat Messages
                                </h1>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                                Review reported messages and moderate disruptive
                                accounts.
                            </p>
                        </div>
                        {props.reports.length > 0 ? (
                            <button
                                type="button"
                                onClick={() =>
                                    handleClearReports(
                                        currentFilter.value === 'all'
                                            ? undefined
                                            : currentFilter.value,
                                    )
                                }
                                title={
                                    currentFilter.value === 'all'
                                        ? 'Delete all reports'
                                        : `Delete all ${currentFilter.value} reports`
                                }
                                class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-xl px-3 text-xs font-semibold text-rose-600 transition hover:bg-rose-50 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-rose-400 dark:hover:bg-rose-500/10"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                                <span>
                                    {currentFilter.value === 'all'
                                        ? 'Clear all'
                                        : `Clear ${currentFilter.value}`}
                                </span>
                            </button>
                        ) : null}
                    </div>

                    {/* Chat management nav */}
                    <div class="flex items-center gap-4">
                        <Link
                            href="/admin/chat"
                            class="flex cursor-pointer items-center gap-1.5 px-1 py-2 text-xs font-semibold text-slate-500 transition hover:text-slate-800 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-gray-400 dark:hover:text-gray-200"
                        >
                            <MessageCircle class="h-3.5 w-3.5" />
                            <span>Chat configuration</span>
                        </Link>
                        <Link
                            href="/admin/chat/reports"
                            class="flex cursor-pointer items-center gap-1.5 px-1 py-2 text-xs font-semibold text-indigo-600 underline decoration-2 underline-offset-4 transition focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-indigo-400"
                        >
                            <Flag class="h-3.5 w-3.5" />
                            <span>Reported messages</span>
                            {props.pendingCount > 0 ? (
                                <span class="rounded-full bg-rose-500 px-1.5 text-[11px] font-bold text-white">
                                    {props.pendingCount}
                                </span>
                            ) : null}
                        </Link>
                    </div>

                    {/* Filter tabs */}
                    <div class="inline-flex items-center gap-0.5 rounded-xl bg-slate-100 p-1 dark:bg-gray-800">
                        <button
                            type="button"
                            onClick={() => {
                                currentFilter.value = 'all';
                            }}
                            class={`cursor-pointer rounded-lg px-2.5 py-1 text-xs font-semibold transition-all focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 ${tabClass(currentFilter.value === 'all')}`}
                        >
                            All ({props.reports.length})
                        </button>
                        <button
                            type="button"
                            onClick={() => {
                                currentFilter.value = 'pending';
                            }}
                            class={`cursor-pointer rounded-lg px-2.5 py-1 text-xs font-semibold transition-all focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 ${tabClass(currentFilter.value === 'pending')}`}
                        >
                            Pending ({props.pendingCount})
                        </button>
                        <button
                            type="button"
                            onClick={() => {
                                currentFilter.value = 'reviewed';
                            }}
                            class={`cursor-pointer rounded-lg px-2.5 py-1 text-xs font-semibold transition-all focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 ${tabClass(currentFilter.value === 'reviewed')}`}
                        >
                            Resolved ({props.reviewedCount})
                        </button>
                        <button
                            type="button"
                            onClick={() => {
                                currentFilter.value = 'dismissed';
                            }}
                            class={`cursor-pointer rounded-lg px-2.5 py-1 text-xs font-semibold transition-all focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 ${tabClass(currentFilter.value === 'dismissed')}`}
                        >
                            Dismissed ({props.dismissedCount})
                        </button>
                    </div>

                    {/* Reports list */}
                    <div>
                        {filteredReports.value.length === 0 ? (
                            <EmptyState
                                icon={Flag}
                                variant="dashed"
                                title={`No ${currentFilter.value !== 'all' ? currentFilter.value : ''} reports found`}
                                description="Everything looks clean for this filter criteria."
                            />
                        ) : (
                            <div class="divide-y divide-slate-100 dark:divide-gray-800">
                                {filteredReports.value.map((report) => (
                                    <div
                                        key={report.id}
                                        class="flex items-start gap-3 py-3.5"
                                    >
                                        <span
                                            class={`mt-1.5 h-2 w-2 shrink-0 rounded-full ${statusDotClass(report.status)}`}
                                        />
                                        <div class="min-w-0 flex-1 space-y-1">
                                            <div class="flex flex-wrap items-center gap-x-1.5 gap-y-0.5">
                                                <p class="text-sm font-semibold text-slate-900 dark:text-gray-100">
                                                    {getReporterName(report)}{' '}
                                                    <span class="font-normal text-slate-500 dark:text-gray-400">
                                                        reported
                                                    </span>{' '}
                                                    {getAuthorName(report)}
                                                </p>
                                                <span class="rounded bg-amber-50 px-1.5 py-0.5 text-[11px] font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                                                    {report.reason ||
                                                        'Not specified'}
                                                </span>
                                            </div>
                                            <p class="text-[11px] text-slate-500 dark:text-gray-400">
                                                <span class="font-medium tracking-wide uppercase">
                                                    {report.status}
                                                </span>
                                                <span>
                                                    {' '}
                                                    · Reported{' '}
                                                    {formatDate(
                                                        report.created_at,
                                                    )}
                                                </span>
                                                {report.message_sent_at ? (
                                                    <span>
                                                        {' '}
                                                        · Sent{' '}
                                                        {formatDate(
                                                            report.message_sent_at,
                                                        )}
                                                    </span>
                                                ) : null}
                                                {report.reported_user
                                                    ?.is_banned ? (
                                                    <span>
                                                        {' '}
                                                        · Currently banned
                                                    </span>
                                                ) : null}
                                            </p>
                                            <div class="mt-1.5 border-l-2 border-slate-200 pl-3 dark:border-gray-700">
                                                <p class="text-xs leading-relaxed break-words text-slate-700 dark:text-gray-300">
                                                    &quot;
                                                    {report.message_content}
                                                    &quot;
                                                </p>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-1 pt-1">
                                                {report.reported_user_id ? (
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            openBanModal(report)
                                                        }
                                                        title={
                                                            report.reported_user
                                                                ?.is_banned
                                                                ? 'Edit suspension or unban user'
                                                                : 'Suspend user'
                                                        }
                                                        class="inline-flex cursor-pointer items-center gap-1 rounded-md px-2 py-1 text-xs font-semibold text-rose-600 transition hover:bg-rose-50 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-rose-400 dark:hover:bg-rose-500/10"
                                                    >
                                                        <Ban class="h-3.5 w-3.5" />
                                                        <span>
                                                            {report
                                                                .reported_user
                                                                ?.is_banned
                                                                ? 'Edit ban'
                                                                : 'Suspend'}
                                                        </span>
                                                    </button>
                                                ) : null}
                                                {report.status !==
                                                'reviewed' ? (
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            updateReportStatus(
                                                                report.id,
                                                                'reviewed',
                                                            )
                                                        }
                                                        class="cursor-pointer rounded-md px-2 py-1 text-xs font-semibold text-emerald-600 transition hover:bg-emerald-50 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-emerald-400 dark:hover:bg-emerald-500/10"
                                                    >
                                                        Resolve
                                                    </button>
                                                ) : null}
                                                {report.status !==
                                                'dismissed' ? (
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            updateReportStatus(
                                                                report.id,
                                                                'dismissed',
                                                            )
                                                        }
                                                        class="cursor-pointer rounded-md px-2 py-1 text-xs font-semibold text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                                                    >
                                                        Dismiss
                                                    </button>
                                                ) : null}
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        deleteReport(report.id)
                                                    }
                                                    title="Delete report"
                                                    aria-label="Delete report"
                                                    class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-gray-500 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                                                >
                                                    <Trash2 class="h-4 w-4" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Reusable Quick Chat Ban Modal */}
                    <ChatBanModal
                        isOpen={isBanModalOpen.value}
                        user={selectedUser.value}
                        onClose={closeBanModal}
                    />
                </div>
            </>
        );
    },
});
