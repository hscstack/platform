<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    LayoutGrid,
    Users,
    Eye,
    Share2,
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
                    Staff workspace &bull;
                    <span class="font-medium text-slate-700 dark:text-gray-300">
                        {{
                            (
                                stats?.total_accounts ??
                                props.totalAccounts ??
                                0
                            ).toLocaleString()
                        }}
                        Total Accounts
                    </span>
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
            <div class="mb-3">
                <h2
                    class="text-xs font-bold tracking-wider text-slate-400 uppercase dark:text-gray-500"
                >
                    Management Tools
                </h2>
            </div>

            <div
                class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 sm:gap-3 lg:grid-cols-3"
            >
                <Link
                    v-for="tool in visibleTools"
                    :key="tool.href"
                    :href="tool.href"
                    class="group flex items-center gap-2.5 rounded-xl border border-slate-200/80 bg-white p-2.5 shadow-2xs transition-all hover:border-indigo-300 hover:shadow-xs sm:p-3 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-indigo-500/40"
                >
                    <div
                        :class="[
                            'flex h-8 w-8 shrink-0 items-center justify-center rounded-lg transition-colors',
                            tool.color,
                        ]"
                    >
                        <component :is="tool.icon" class="h-4 w-4 stroke-[2]" />
                    </div>
                    <span
                        class="text-xs leading-tight font-semibold text-slate-800 transition-colors group-hover:text-indigo-600 sm:text-sm dark:text-gray-200 dark:group-hover:text-indigo-400"
                    >
                        {{ tool.name }}
                    </span>
                </Link>
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
