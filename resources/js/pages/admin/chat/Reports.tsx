import { Head, Link, router } from '@inertiajs/vue3';
import { Ban, Flag, MessageCircle, Trash2 } from 'lucide-vue-next';
import { computed, defineComponent, ref } from 'vue';
import type { PropType } from 'vue';

import {
    AdminPageHeader,
    SegmentedTabs,
    adminDangerIconBtnClass,
    adminDangerTextBtnClass,
    adminHoverListClass,
    adminMutedTextBtnClass,
    adminPageClass,
    adminSubNavBadgeClass,
    adminSubNavClass,
    adminSubNavLinkClass,
    adminSuccessTextBtnClass,
} from '@/components/admin/ui';
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

        const tabOptions = computed(() => [
            { value: 'all', label: `All (${props.reports.length})` },
            { value: 'pending', label: `Pending (${props.pendingCount})` },
            {
                value: 'reviewed',
                label: `Resolved (${props.reviewedCount})`,
            },
            {
                value: 'dismissed',
                label: `Dismissed (${props.dismissedCount})`,
            },
        ]);

        return () => (
            <>
                <Head title="Reported Chat Messages - Staff Panel" />

                <div class={adminPageClass}>
                    {/* Page header */}
                    <AdminPageHeader
                        title="Reported Chat Messages"
                        description="Review reported messages and moderate disruptive accounts."
                    >
                        {{
                            actions: () =>
                                props.reports.length > 0 && (
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
                                        class={adminDangerTextBtnClass}
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                        <span>
                                            {currentFilter.value === 'all'
                                                ? 'Clear all'
                                                : `Clear ${currentFilter.value}`}
                                        </span>
                                    </button>
                                ),
                        }}
                    </AdminPageHeader>

                    {/* Chat management nav */}
                    <nav class={adminSubNavClass}>
                        <Link
                            href="/admin/chat"
                            class={adminSubNavLinkClass(false)}
                        >
                            <MessageCircle class="h-3.5 w-3.5" />
                            <span>Chat configuration</span>
                        </Link>
                        <Link
                            href="/admin/chat/reports"
                            class={adminSubNavLinkClass(true)}
                        >
                            <Flag class="h-3.5 w-3.5" />
                            <span>Reported messages</span>
                            {props.pendingCount > 0 ? (
                                <span class={adminSubNavBadgeClass}>
                                    {props.pendingCount}
                                </span>
                            ) : null}
                        </Link>
                    </nav>

                    {/* Filter tabs */}
                    <SegmentedTabs
                        tabs={tabOptions.value}
                        active={currentFilter.value}
                        onSelect={(value: string) =>
                            (currentFilter.value =
                                value as typeof currentFilter.value)
                        }
                    />

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
                            <div class={adminHoverListClass}>
                                {filteredReports.value.map((report) => (
                                    <div
                                        key={report.id}
                                        class="flex items-start gap-3 rounded-xl px-3 py-3.5 transition hover:bg-slate-50 dark:hover:bg-gray-800/50"
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
                                                        class={
                                                            adminDangerTextBtnClass
                                                        }
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
                                                        class={
                                                            adminSuccessTextBtnClass
                                                        }
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
                                                        class={
                                                            adminMutedTextBtnClass
                                                        }
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
                                                    class={
                                                        adminDangerIconBtnClass
                                                    }
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
