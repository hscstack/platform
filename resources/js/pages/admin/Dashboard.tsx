/**
 * AdminDashboard — TSX port of `Dashboard.vue`.
 *
 * Management tools grid + on-demand analytics, rewritten as a `.tsx`
 * `defineComponent` render function. Resolved via the explicit
 * dual-extension (`*.vue` + `*.tsx`) page resolver in `resources/js/app.ts`.
 *
 * Ported 1:1 from the SFC (parity is the reviewable contract); shared
 * admin chrome (headers/buttons) comes from `@/components/admin/ui`.
 */
import { Head, Link } from '@inertiajs/vue3';
import {
    BarChart3,
    Bell,
    BookOpen,
    CheckSquare,
    Eye,
    LayoutGrid,
    LifeBuoy,
    Mail,
    MessageCircle,
    MessageSquare,
    Radio,
    RefreshCw,
    Share2,
    UserCheck,
    Users,
} from 'lucide-vue-next';
import { computed, defineComponent, ref } from 'vue';
import type { Component } from 'vue';
import type { PropType } from 'vue';

import {
    adminListClass,
    adminPageClass,
    adminSectionTitleClass,
    adminPageDescriptionClass,
    adminPageHeaderClass,
    adminPageTitleClass,
    adminPrimaryBtnClass,
    adminSecondaryBtnClass,
} from '@/components/admin/ui';
import EmptyState from '@/components/EmptyState.vue';
import { usePermissions } from '@/lib/usePermissions';

interface AnalyticsSource {
    source: string;
    visits: number;
}

interface DashboardAnalytics {
    total_accounts?: number;
    realtime_users?: number;
    total_visits?: number;
    total_users?: number;
    top_sources?: AnalyticsSource[];
}

interface AdminTool {
    name: string;
    href: string;
    icon: Component;
    color: string;
    permission?: string;
}

const adminTools: AdminTool[] = [
    {
        name: 'Resource Moderation',
        href: '/admin/moderation/resources',
        icon: CheckSquare,
        color: 'text-emerald-600 bg-emerald-50 dark:bg-emerald-500/10 dark:text-emerald-400',
        permission: 'moderate resources',
    },
    {
        name: 'Manage Contents',
        href: '/admin/subjects',
        icon: BookOpen,
        color: 'text-indigo-600 bg-indigo-50 dark:bg-indigo-500/10 dark:text-indigo-400',
    },
    {
        name: 'Manage Forum',
        href: '/admin/forums',
        icon: MessageSquare,
        color: 'text-blue-600 bg-blue-50 dark:bg-blue-500/10 dark:text-blue-400',
        permission: 'manage forums',
    },
    {
        name: 'Support Tickets',
        href: '/admin/tickets',
        icon: LifeBuoy,
        color: 'text-amber-600 bg-amber-50 dark:bg-amber-500/10 dark:text-amber-400',
        permission: 'manage tickets',
    },
    {
        name: 'Site Notice',
        href: '/admin/notice',
        icon: Bell,
        color: 'text-purple-600 bg-purple-50 dark:bg-purple-500/10 dark:text-purple-400',
        permission: 'edit notice',
    },
    {
        name: 'Global Chat',
        href: '/admin/chat',
        icon: MessageCircle,
        color: 'text-teal-600 bg-teal-50 dark:bg-teal-500/10 dark:text-teal-400',
        permission: 'manage chat',
    },
    {
        name: 'Users & Roles',
        href: '/admin/users',
        icon: UserCheck,
        color: 'text-sky-600 bg-sky-50 dark:bg-sky-500/10 dark:text-sky-400',
        permission: 'view users',
    },
    {
        name: 'Peer & Pokes',
        href: '/admin/peers/settings',
        icon: Radio,
        color: 'text-rose-600 bg-rose-50 dark:bg-rose-500/10 dark:text-rose-400',
        permission: 'manage peers',
    },
    {
        name: 'Send Emails',
        href: '/admin/emails/send',
        icon: Mail,
        color: 'text-orange-600 bg-orange-50 dark:bg-orange-500/10 dark:text-orange-400',
        permission: 'send email',
    },
];

export default defineComponent({
    name: 'AdminDashboard',
    props: {
        totalAccounts: { type: Number as PropType<number>, default: undefined },
    },
    setup(props) {
        const { can } = usePermissions();

        const stats = ref<DashboardAnalytics | null>(null);
        const isLoading = ref<boolean>(false);
        const hasFetched = ref<boolean>(false);
        const errorMsg = ref<string | null>(null);

        const visibleTools = computed(() =>
            adminTools.filter(
                (tool) => !tool.permission || can(tool.permission),
            ),
        );

        const fetchAnalytics = async (
            refresh: boolean = false,
        ): Promise<void> => {
            isLoading.value = true;
            errorMsg.value = null;

            try {
                const res = await fetch(
                    `/admin/analytics${refresh ? '?refresh=1' : ''}`,
                );

                if (!res.ok) {
                    throw new Error('Analytics could not be loaded');
                }

                const data: unknown = await res.json();
                stats.value = data as DashboardAnalytics;
                hasFetched.value = true;
            } catch (e: unknown) {
                if (e instanceof Error) {
                    errorMsg.value = e.message || 'Failed to load analytics';
                } else {
                    errorMsg.value = 'Failed to load analytics';
                }
            } finally {
                isLoading.value = false;
            }
        };

        return () => {
            const totalAccounts =
                stats.value?.total_accounts ?? props.totalAccounts ?? 0;

            return (
                <>
                    <Head title="Staff Dashboard" />

                    <div class={[adminPageClass, 'animate-fade-in']}>
                        {/* Header — description carries the account counter */}
                        <div class={adminPageHeaderClass}>
                            <div>
                                <h1 class={adminPageTitleClass}>Dashboard</h1>
                                <p class={adminPageDescriptionClass}>
                                    Staff workspace &bull;{' '}
                                    <span class="font-medium text-slate-700 dark:text-gray-300">
                                        {totalAccounts.toLocaleString()} Total
                                        Accounts
                                    </span>
                                </p>
                            </div>

                            {/* Reload affordance lives here AFTER the first
                                fetch; before that the single loader is the
                                in-context button in the empty state below
                                (never two load buttons on one screen). */}
                            {hasFetched.value && (
                                <button
                                    type="button"
                                    onClick={() => fetchAnalytics(true)}
                                    disabled={isLoading.value}
                                    class={[
                                        adminSecondaryBtnClass,
                                        'w-fit max-sm:justify-center',
                                    ]}
                                >
                                    <RefreshCw
                                        class={[
                                            'h-3.5 w-3.5',
                                            isLoading.value
                                                ? 'animate-spin'
                                                : '',
                                        ]}
                                    />
                                    <span>
                                        {isLoading.value
                                            ? 'Refreshing...'
                                            : 'Reload Analytics'}
                                    </span>
                                </button>
                            )}
                        </div>

                        {/* Management Tools Grid */}
                        <div>
                            <h2 class={adminSectionTitleClass}>
                                Management Tools
                            </h2>

                            <div class="mt-3 grid grid-cols-2 gap-2.5 sm:grid-cols-3 sm:gap-3 lg:grid-cols-3">
                                {visibleTools.value.map((tool) => {
                                    const Icon = tool.icon;

                                    return (
                                        <Link
                                            key={tool.href}
                                            href={tool.href}
                                            class="group flex items-center gap-2.5 rounded-xl bg-white/70 p-2.5 ring-1 ring-slate-200/60 backdrop-blur-xl transition-all duration-150 hover:bg-white/85 hover:ring-indigo-300/70 sm:p-3 dark:bg-slate-900/60 dark:ring-slate-800/60 dark:hover:bg-slate-900/75 dark:hover:ring-indigo-500/40"
                                        >
                                            <div
                                                class={[
                                                    'flex h-8 w-8 shrink-0 items-center justify-center rounded-lg transition-colors',
                                                    tool.color,
                                                ]}
                                            >
                                                <Icon class="h-4 w-4 stroke-[2]" />
                                            </div>
                                            <span class="text-xs leading-tight font-semibold text-slate-800 transition-colors group-hover:text-indigo-600 sm:text-sm dark:text-gray-200 dark:group-hover:text-indigo-400">
                                                {tool.name}
                                            </span>
                                        </Link>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Analytics Section */}
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h2 class={adminSectionTitleClass}>
                                    Live Traffic & Analytics
                                </h2>
                                {hasFetched.value && (
                                    <span class="text-xs text-slate-400 dark:text-gray-500">
                                        Last 30 days
                                    </span>
                                )}
                            </div>

                            {/* Load errors render above every branch so a
                                failed fetch is never silent. */}
                            {errorMsg.value && (
                                <p class="text-center text-xs font-medium text-rose-600 dark:text-rose-400">
                                    {errorMsg.value}
                                </p>
                            )}

                            {/* Unloaded State Placeholder */}
                            {!hasFetched.value && !isLoading.value ? (
                                <EmptyState
                                    icon={BarChart3}
                                    variant="dashed"
                                    title="Live analytics ready on demand"
                                    description="Metrics are fetched on demand to keep page load instantaneous. Click below to load."
                                >
                                    <button
                                        type="button"
                                        onClick={() => fetchAnalytics(false)}
                                        class={[
                                            adminPrimaryBtnClass,
                                            'cursor-pointer',
                                        ]}
                                    >
                                        <RefreshCw class="h-3.5 w-3.5" />
                                        <span>Load Analytics</span>
                                    </button>
                                </EmptyState>
                            ) : isLoading.value && !hasFetched.value ? (
                                <div class="mt-3 flex flex-col items-center justify-center rounded-2xl bg-white/70 py-14 text-center ring-1 ring-slate-200/60 backdrop-blur-xl dark:bg-slate-900/60 dark:ring-slate-800/60">
                                    <RefreshCw class="h-7 w-7 animate-spin text-indigo-600 dark:text-indigo-400" />
                                    <p class="mt-2.5 text-xs font-medium text-slate-500 dark:text-gray-400">
                                        Loading metrics...
                                    </p>
                                </div>
                            ) : (
                                <div class="mt-3 grid gap-4 md:grid-cols-2">
                                    <div class="space-y-3">
                                        {/* Realtime Active */}
                                        <div class="rounded-xl bg-emerald-50/60 p-4 ring-1 ring-emerald-200/70 backdrop-blur-xl dark:bg-emerald-500/10 dark:ring-emerald-500/20">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <span class="relative flex h-2 w-2">
                                                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                                        <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                                                    </span>
                                                    <p class="text-xs font-semibold tracking-wider text-emerald-900 uppercase dark:text-emerald-300">
                                                        Active Now
                                                    </p>
                                                </div>
                                                <span class="text-xs text-slate-500 dark:text-gray-400">
                                                    Past 5 mins
                                                </span>
                                            </div>
                                            <div class="mt-2">
                                                <h3 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl dark:text-gray-100">
                                                    {stats.value
                                                        ?.realtime_users ?? 0}
                                                </h3>
                                            </div>
                                        </div>

                                        {/* Total Visits */}
                                        <div class="rounded-xl bg-white/70 p-4 ring-1 ring-slate-200/60 backdrop-blur-xl dark:bg-slate-900/60 dark:ring-slate-800/60">
                                            <div class="flex items-center justify-between">
                                                <p
                                                    class={
                                                        adminSectionTitleClass
                                                    }
                                                >
                                                    Total Visits
                                                </p>
                                                <Eye class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                                            </div>
                                            <div class="mt-2">
                                                <h3 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl dark:text-gray-100">
                                                    {stats.value?.total_visits?.toLocaleString() ??
                                                        0}
                                                </h3>
                                                <p class="mt-0.5 text-xs text-slate-400 dark:text-gray-500">
                                                    Pageviews recorded
                                                </p>
                                            </div>
                                        </div>

                                        {/* Unique Visitors */}
                                        <div class="rounded-xl bg-white/70 p-4 ring-1 ring-slate-200/60 backdrop-blur-xl dark:bg-slate-900/60 dark:ring-slate-800/60">
                                            <div class="flex items-center justify-between">
                                                <p
                                                    class={
                                                        adminSectionTitleClass
                                                    }
                                                >
                                                    Unique Visitors
                                                </p>
                                                <Users class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                            </div>
                                            <div class="mt-2">
                                                <h3 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl dark:text-gray-100">
                                                    {stats.value?.total_users?.toLocaleString() ??
                                                        0}
                                                </h3>
                                                <p class="mt-0.5 text-xs text-slate-400 dark:text-gray-500">
                                                    Distinct visitors
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Top Traffic Sources */}
                                    <div class="flex flex-col rounded-xl bg-white/70 p-4 ring-1 ring-slate-200/60 backdrop-blur-xl dark:bg-slate-900/60 dark:ring-slate-800/60">
                                        <div class="flex items-center gap-2 border-b border-slate-200/60 pb-3 dark:border-slate-800/60">
                                            <Share2 class="h-4 w-4 text-slate-500 dark:text-gray-400" />
                                            <h3 class={adminSectionTitleClass}>
                                                Top Traffic Sources
                                            </h3>
                                        </div>

                                        <div class="mt-3 flex-1">
                                            {stats.value?.top_sources
                                                ?.length ? (
                                                <div class={adminListClass}>
                                                    {stats.value.top_sources.map(
                                                        (
                                                            source,
                                                            index: number,
                                                        ) => (
                                                            <div
                                                                key={index}
                                                                class="flex items-center justify-between py-2 text-xs"
                                                            >
                                                                <span
                                                                    class="truncate font-medium text-slate-700 dark:text-gray-300"
                                                                    title={
                                                                        source.source
                                                                    }
                                                                >
                                                                    {source.source ||
                                                                        'Direct / Unknown'}
                                                                </span>
                                                                <span class="ml-2 shrink-0 rounded-md bg-slate-100 px-2 py-0.5 font-semibold text-slate-700 dark:bg-gray-800 dark:text-gray-300">
                                                                    {source.visits?.toLocaleString()}
                                                                </span>
                                                            </div>
                                                        ),
                                                    )}
                                                </div>
                                            ) : (
                                                <div class="flex h-full flex-col items-center justify-center py-10 text-center">
                                                    <LayoutGrid class="h-6 w-6 text-slate-300 dark:text-gray-600" />
                                                    <p class="mt-1.5 text-xs text-slate-400 dark:text-gray-500">
                                                        No source data available
                                                        yet.
                                                    </p>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </>
            );
        };
    },
});
