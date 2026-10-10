<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    LayoutGrid,
    Users,
    Eye,
    Share2,
    Zap,
    RefreshCw,
    BarChart3,
    CheckSquare,
    BookOpen,
    MessageSquare,
    LifeBuoy,
    Bell,
    MessageCircle,
    UserCheck,
    Radio,
    Mail,
    FileText,
    Boxes,
    ArrowUpRight,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import { usePermissions } from '@/lib/usePermissions';

const { can } = usePermissions();

const props = defineProps<{
    totalAccounts?: number;
}>();

const stats = ref<any>(null);
const isLoading = ref(false);
const hasFetched = ref(false);
const errorMsg = ref<string | null>(null);

const adminTools = [
    {
        name: 'Resource Moderation',
        description: 'Review and approve community submitted study materials',
        href: '/admin/moderation/resources',
        icon: CheckSquare,
        color: 'text-emerald-600 bg-emerald-50 dark:bg-emerald-500/10 dark:text-emerald-400',
        permission: 'moderate resources',
    },
    {
        name: 'Manage Contents',
        description: 'Syllabus, subjects, chapters and learning resources',
        href: '/admin/subjects',
        icon: BookOpen,
        color: 'text-indigo-600 bg-indigo-50 dark:bg-indigo-500/10 dark:text-indigo-400',
    },
    {
        name: 'Manage Forum',
        description: 'Categories, pinned questions, and moderation',
        href: '/admin/forums',
        icon: MessageSquare,
        color: 'text-blue-600 bg-blue-50 dark:bg-blue-500/10 dark:text-blue-400',
        permission: 'manage forums',
    },
    {
        name: 'Support Tickets',
        description: 'View and respond to student help tickets',
        href: '/admin/tickets',
        icon: LifeBuoy,
        color: 'text-amber-600 bg-amber-50 dark:bg-amber-500/10 dark:text-amber-400',
        permission: 'manage tickets',
    },
    {
        name: 'Site Notice',
        description: 'Broadcast system banner and maintenance alerts',
        href: '/admin/notice',
        icon: Bell,
        color: 'text-purple-600 bg-purple-50 dark:bg-purple-500/10 dark:text-purple-400',
        permission: 'edit notice',
    },
    {
        name: 'Global Chat',
        description: 'Live chat room moderation and flagged messages',
        href: '/admin/chat',
        icon: MessageCircle,
        color: 'text-teal-600 bg-teal-50 dark:bg-teal-500/10 dark:text-teal-400',
        permission: 'manage chat',
    },
    {
        name: 'Users & Roles',
        description: 'Directory, verification badges, and permissions',
        href: '/admin/users',
        icon: UserCheck,
        color: 'text-sky-600 bg-sky-50 dark:bg-sky-500/10 dark:text-sky-400',
        permission: 'view users',
    },
    {
        name: 'Peer & Pokes',
        description: 'Community interactions and study buddy settings',
        href: '/admin/peers/settings',
        icon: Radio,
        color: 'text-rose-600 bg-rose-50 dark:bg-rose-500/10 dark:text-rose-400',
        permission: 'manage peers',
    },
    {
        name: 'Send Emails',
        description: 'Compose announcements and system updates',
        href: '/admin/emails/send',
        icon: Mail,
        color: 'text-orange-600 bg-orange-50 dark:bg-orange-500/10 dark:text-orange-400',
        permission: 'send email',
    },
    {
        name: 'Blogs & Articles',
        description: 'Public authoring, editorial drafts, and posts',
        href: '/blogs',
        icon: FileText,
        color: 'text-indigo-600 bg-indigo-50 dark:bg-indigo-500/10 dark:text-indigo-400',
        permission: 'manage blogs',
    },
    {
        name: 'Products & Projects',
        description: 'Public showcase platform apps and tools',
        href: '/products',
        icon: Boxes,
        color: 'text-cyan-600 bg-cyan-50 dark:bg-cyan-500/10 dark:text-cyan-400',
        permission: 'manage products',
    },
];

const visibleTools = computed(() =>
    adminTools.filter((tool) => !tool.permission || can(tool.permission)),
);

const fetchAnalytics = async (refresh = false) => {
    isLoading.value = true;
    errorMsg.value = null;

    try {
        const res = await fetch(
            `/admin/analytics${refresh ? '?refresh=1' : ''}`,
        );

        if (!res.ok) {
            throw new Error('Analytics could not be loaded');
        }

        stats.value = await res.json();
        hasFetched.value = true;
    } catch (e: any) {
        errorMsg.value = e.message || 'Failed to load analytics';
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <Head title="Staff Dashboard" />

    <div class="animate-fade-in mx-auto max-w-7xl space-y-6">
        <!-- Header -->
        <div
            class="flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800"
        >
            <div>
                <h1
                    class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl dark:text-gray-100"
                >
                    Dashboard
                </h1>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                    Staff workspace and management portal
                </p>
            </div>

            <!-- On-Demand Load / Reload Button -->
            <button
                type="button"
                @click="fetchAnalytics(hasFetched)"
                :disabled="isLoading"
                class="inline-flex w-fit items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-2xs transition-colors hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-600 active:scale-95 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-indigo-500/30 dark:hover:bg-indigo-500/10 dark:hover:text-indigo-400"
            >
                <RefreshCw
                    class="h-3.5 w-3.5"
                    :class="{ 'animate-spin': isLoading }"
                />
                <span>{{
                    hasFetched
                        ? isLoading
                            ? 'Refreshing...'
                            : 'Reload Analytics'
                        : isLoading
                          ? 'Loading...'
                          : 'Load Analytics'
                }}</span>
            </button>
        </div>

        <!-- Management Tools Grid -->
        <div>
            <div class="mb-3 flex items-center justify-between">
                <h2
                    class="text-xs font-bold tracking-wider text-slate-400 uppercase dark:text-gray-500"
                >
                    Management Tools
                </h2>
                <span
                    class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600 dark:bg-gray-800 dark:text-gray-400"
                >
                    {{ visibleTools.length }} Available
                </span>
            </div>

            <div
                class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                <Link
                    v-for="tool in visibleTools"
                    :key="tool.href"
                    :href="tool.href"
                    class="group relative flex flex-col justify-between rounded-xl border border-slate-200/80 bg-white p-4 shadow-2xs transition-all duration-150 hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-xs dark:border-gray-800 dark:bg-gray-900 dark:hover:border-indigo-500/40"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div
                            :class="[
                                'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl transition-colors',
                                tool.color,
                            ]"
                        >
                            <component
                                :is="tool.icon"
                                class="h-5 w-5 stroke-[1.9]"
                            />
                        </div>
                        <ArrowUpRight
                            class="h-4 w-4 text-slate-400 opacity-0 transition-all duration-150 group-hover:text-indigo-600 group-hover:opacity-100 dark:text-gray-500 dark:group-hover:text-indigo-400"
                        />
                    </div>

                    <div class="mt-3.5">
                        <h3
                            class="text-sm font-bold text-slate-900 transition-colors group-hover:text-indigo-600 dark:text-gray-100 dark:group-hover:text-indigo-400"
                        >
                            {{ tool.name }}
                        </h3>
                        <p
                            class="mt-1 line-clamp-2 text-xs text-slate-500 dark:text-gray-400"
                        >
                            {{ tool.description }}
                        </p>
                    </div>
                </Link>
            </div>
        </div>

        <!-- Overview Quick Stats -->
        <div class="grid gap-3 sm:grid-cols-3">
            <div
                class="rounded-xl border border-slate-200 bg-white p-4 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p
                            class="text-[11px] font-bold tracking-wider text-slate-400 uppercase dark:text-gray-500"
                        >
                            Total Accounts
                        </p>
                        <h3
                            class="mt-1 text-xl font-bold tracking-tight text-slate-900 sm:text-2xl dark:text-gray-100"
                        >
                            {{
                                (
                                    stats?.total_accounts ??
                                    props.totalAccounts ??
                                    0
                                ).toLocaleString()
                            }}
                        </h3>
                    </div>
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                    >
                        <Users class="h-4.5 w-4.5" />
                    </div>
                </div>
            </div>

            <div
                class="rounded-xl border border-slate-200 bg-white p-4 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p
                            class="text-[11px] font-bold tracking-wider text-slate-400 uppercase dark:text-gray-500"
                        >
                            Active Now (5m)
                        </p>
                        <h3
                            class="mt-1 text-xl font-bold tracking-tight text-slate-900 sm:text-2xl dark:text-gray-100"
                        >
                            {{
                                stats?.realtime_users ?? (hasFetched ? 0 : '—')
                            }}
                        </h3>
                    </div>
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400"
                    >
                        <Zap class="h-4.5 w-4.5" />
                    </div>
                </div>
            </div>

            <div
                class="rounded-xl border border-slate-200 bg-white p-4 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p
                            class="text-[11px] font-bold tracking-wider text-slate-400 uppercase dark:text-gray-500"
                        >
                            Monthly Visits
                        </p>
                        <h3
                            class="mt-1 text-xl font-bold tracking-tight text-slate-900 sm:text-2xl dark:text-gray-100"
                        >
                            {{
                                stats?.total_visits
                                    ? stats.total_visits.toLocaleString()
                                    : hasFetched
                                      ? 0
                                      : '—'
                            }}
                        </h3>
                    </div>
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400"
                    >
                        <Eye class="h-4.5 w-4.5" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Analytics Section -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2
                    class="text-xs font-bold tracking-wider text-slate-400 uppercase dark:text-gray-500"
                >
                    Live Traffic & Analytics
                </h2>
                <span
                    v-if="hasFetched"
                    class="text-xs text-slate-400 dark:text-gray-500"
                >
                    Last 30 days
                </span>
            </div>

            <!-- Unloaded State Placeholder -->
            <EmptyState
                v-if="!hasFetched && !isLoading"
                :icon="BarChart3"
                variant="dashed"
                title="Live analytics ready on demand"
                description="Metrics are fetched on demand to keep page load instantaneous. Click below to load."
            >
                <button
                    type="button"
                    @click="fetchAnalytics(false)"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition-colors hover:bg-indigo-700 active:scale-95"
                >
                    <RefreshCw class="h-3.5 w-3.5" />
                    <span>Fetch Analytics</span>
                </button>
            </EmptyState>

            <!-- Loading Spinner -->
            <div
                v-else-if="isLoading && !hasFetched"
                class="flex flex-col items-center justify-center rounded-2xl border border-slate-200 bg-white py-14 text-center shadow-2xs dark:border-gray-800 dark:bg-gray-900"
            >
                <RefreshCw
                    class="h-7 w-7 animate-spin text-indigo-600 dark:text-indigo-400"
                />
                <p
                    class="mt-2.5 text-xs font-medium text-slate-500 dark:text-gray-400"
                >
                    Loading metrics...
                </p>
            </div>

            <!-- Metrics (Once loaded) -->
            <div v-else class="grid gap-4 md:grid-cols-2">
                <div class="space-y-3">
                    <!-- Realtime Active -->
                    <div
                        class="rounded-xl border border-emerald-200/80 bg-emerald-50/40 p-4 shadow-2xs dark:border-emerald-500/30 dark:bg-emerald-950/20"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="relative flex h-2 w-2">
                                    <span
                                        class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                                    />
                                    <span
                                        class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"
                                    />
                                </span>
                                <p
                                    class="text-xs font-bold tracking-wider text-emerald-900 uppercase dark:text-emerald-300"
                                >
                                    Active Now
                                </p>
                            </div>
                            <span
                                class="text-xs text-slate-500 dark:text-gray-400"
                                >Past 5 mins</span
                            >
                        </div>
                        <div class="mt-2">
                            <h3
                                class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl dark:text-gray-100"
                            >
                                {{ stats?.realtime_users ?? 0 }}
                            </h3>
                        </div>
                    </div>

                    <!-- Total Visits -->
                    <div
                        class="rounded-xl border border-slate-200 bg-white p-4 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
                    >
                        <div class="flex items-center justify-between">
                            <p
                                class="text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400"
                            >
                                Total Visits
                            </p>
                            <Eye
                                class="h-4 w-4 text-indigo-600 dark:text-indigo-400"
                            />
                        </div>
                        <div class="mt-2">
                            <h3
                                class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl dark:text-gray-100"
                            >
                                {{ stats?.total_visits?.toLocaleString() ?? 0 }}
                            </h3>
                            <p
                                class="mt-0.5 text-xs text-slate-400 dark:text-gray-500"
                            >
                                Pageviews recorded
                            </p>
                        </div>
                    </div>

                    <!-- Unique Visitors -->
                    <div
                        class="rounded-xl border border-slate-200 bg-white p-4 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
                    >
                        <div class="flex items-center justify-between">
                            <p
                                class="text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400"
                            >
                                Unique Visitors
                            </p>
                            <Users
                                class="h-4 w-4 text-blue-600 dark:text-blue-400"
                            />
                        </div>
                        <div class="mt-2">
                            <h3
                                class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl dark:text-gray-100"
                            >
                                {{ stats?.total_users?.toLocaleString() ?? 0 }}
                            </h3>
                            <p
                                class="mt-0.5 text-xs text-slate-400 dark:text-gray-500"
                            >
                                Distinct visitors
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Top Traffic Sources -->
                <div
                    class="flex flex-col rounded-xl border border-slate-200 bg-white p-4 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
                >
                    <div
                        class="flex items-center gap-2 border-b border-slate-100 pb-3 dark:border-gray-800"
                    >
                        <Share2
                            class="h-4 w-4 text-slate-500 dark:text-gray-400"
                        />
                        <h3
                            class="text-xs font-bold tracking-wider text-slate-900 uppercase dark:text-gray-100"
                        >
                            Top Traffic Sources
                        </h3>
                    </div>

                    <div class="mt-3 flex-1">
                        <div
                            v-if="stats?.top_sources?.length"
                            class="divide-y divide-slate-100 dark:divide-gray-800"
                        >
                            <div
                                v-for="(source, index) in stats.top_sources"
                                :key="index"
                                class="flex items-center justify-between py-2 text-xs"
                            >
                                <span
                                    class="truncate font-medium text-slate-700 dark:text-gray-300"
                                    :title="source.source"
                                >
                                    {{ source.source || 'Direct / Unknown' }}
                                </span>
                                <span
                                    class="ml-2 shrink-0 rounded-md bg-slate-100 px-2 py-0.5 font-semibold text-slate-700 dark:bg-gray-800 dark:text-gray-300"
                                >
                                    {{ source.visits?.toLocaleString() }}
                                </span>
                            </div>
                        </div>

                        <div
                            v-else
                            class="flex h-full flex-col items-center justify-center py-10 text-center"
                        >
                            <LayoutGrid
                                class="h-6 w-6 text-slate-300 dark:text-gray-600"
                            />
                            <p
                                class="mt-1.5 text-xs text-slate-400 dark:text-gray-500"
                            >
                                No source data available yet.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
