/**
 * AdminDashboard — TSX port of the former `Dashboard.vue`.
 *
 * Same UI/behavior as the SFC (flat, decardified), rewritten as
 * a `.tsx` `defineComponent` render function. Resolved via the explicit
 * dual-extension (`*.vue` + `*.tsx`) page resolver in `resources/js/app.ts`.
 */
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BarChart3,
    BookOpen,
    RefreshCw,
    Share2,
} from 'lucide-vue-next';
import { defineComponent, ref } from 'vue';
import type { PropType } from 'vue';

import EmptyState from '@/components/EmptyState.vue';

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

export default defineComponent({
    name: 'AdminDashboard',
    props: {
        totalAccounts: { type: Number as PropType<number>, default: undefined },
    },
    setup(props) {
        const stats = ref<DashboardAnalytics | null>(null);
        const isLoading = ref<boolean>(false);
        const hasFetched = ref<boolean>(false);
        const errorMsg = ref<string | null>(null);

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
                    throw new Error('Analytics ডাটা লোড করা যায়নি');
                }

                const data: unknown = await res.json();
                stats.value = data as DashboardAnalytics;
                hasFetched.value = true;
            } catch (e: unknown) {
                if (e instanceof Error) {
                    errorMsg.value = e.message;
                } else {
                    errorMsg.value = 'Analytics লোড করতে সমস্যা হয়েছে';
                }
            } finally {
                isLoading.value = false;
            }
        };

        return () => {
            const totalAccounts =
                stats.value?.total_accounts ?? props.totalAccounts ?? 0;
            const topVisits = stats.value?.top_sources?.[0]?.visits ?? 0;

            return (
                <>
                    <Head title="Staff Panel" />

                    <div class="space-y-6">
                        {/* Page Header — no rule; the KPI strip below carries the separator */}
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-gray-100">
                                    Dashboard
                                </h1>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                                    অ্যাডমিন ওয়ার্কস্পেসের সামগ্রিক তথ্য ও
                                    অ্যানালিটিক্স।
                                </p>
                            </div>

                            {/* On-Demand Load / Reload Button */}
                            <button
                                type="button"
                                onClick={() => fetchAnalytics(hasFetched.value)}
                                disabled={isLoading.value}
                                class="inline-flex h-9 cursor-pointer items-center justify-center gap-1.5 rounded-xl border border-slate-200 px-3.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 max-sm:w-full dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                            >
                                <RefreshCw
                                    class={[
                                        'h-3.5 w-3.5',
                                        isLoading.value ? 'animate-spin' : '',
                                    ]}
                                />
                                <span>
                                    {hasFetched.value
                                        ? isLoading.value
                                            ? 'রিফ্রেশ হচ্ছে...'
                                            : 'Reload Analytics'
                                        : isLoading.value
                                          ? 'লোড হচ্ছে...'
                                          : 'Load Analytics'}
                                </span>
                            </button>
                        </div>

                        {/* KPI strip — one horizontal strip, no per-stat cards */}
                        <section aria-label="Analytics overview">
                            <div class="grid grid-cols-2 border-y border-slate-100 lg:grid-cols-4 lg:divide-x lg:divide-slate-100 dark:border-gray-800 dark:lg:divide-gray-800">
                                {/* Total Accounts */}
                                <div class="border-r border-b border-slate-100 py-4 pr-4 lg:border-r-0 lg:border-b-0 lg:px-4 lg:pl-0 dark:border-gray-800">
                                    <p class="text-[11px] font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400">
                                        Total Accounts
                                    </p>
                                    <p class="mt-1 flex h-8 items-center text-xl font-bold text-slate-900 tabular-nums dark:text-gray-100">
                                        {totalAccounts.toLocaleString()}
                                    </p>
                                    <p class="mt-0.5 text-[11px] text-slate-500 dark:text-gray-400">
                                        সর্বমোট নিবন্ধিত একাউন্ট
                                    </p>
                                </div>

                                {/* Active Now */}
                                <div class="border-b border-slate-100 py-4 pl-4 lg:border-b-0 lg:px-4 dark:border-gray-800">
                                    <p class="flex h-[18px] items-center gap-1.5 text-[11px] font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400">
                                        <span class="relative flex h-2 w-2 shrink-0">
                                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                            <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                                        </span>
                                        Active Now
                                    </p>
                                    <p class="mt-1 flex h-8 items-center text-xl font-bold text-slate-900 tabular-nums dark:text-gray-100">
                                        {hasFetched.value
                                            ? (
                                                  stats.value?.realtime_users ??
                                                  0
                                              ).toLocaleString()
                                            : '—'}
                                    </p>
                                    <p class="mt-0.5 text-[11px] text-slate-500 dark:text-gray-400">
                                        গত ৫ মিনিটে একটিভ ইউজার
                                    </p>
                                </div>

                                {/* Total Visits */}
                                <div class="border-r border-slate-100 py-4 pr-4 lg:border-r-0 lg:px-4 dark:border-gray-800">
                                    <p class="flex h-[18px] items-center text-[11px] font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400">
                                        Total Visits
                                    </p>
                                    <p class="mt-1 flex h-8 items-center text-xl font-bold text-slate-900 tabular-nums dark:text-gray-100">
                                        {hasFetched.value
                                            ? (
                                                  stats.value?.total_visits ?? 0
                                              ).toLocaleString()
                                            : '—'}
                                    </p>
                                    <p class="mt-0.5 text-[11px] text-slate-500 dark:text-gray-400">
                                        পেজ ভিউ · বিগত ৩০ দিন
                                    </p>
                                </div>

                                {/* Unique Visitors */}
                                <div class="py-4 pl-4 lg:px-4 lg:pr-0">
                                    <p class="flex h-[18px] items-center text-[11px] font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400">
                                        Unique Visitors
                                    </p>
                                    <p class="mt-1 flex h-8 items-center text-xl font-bold text-slate-900 tabular-nums dark:text-gray-100">
                                        {hasFetched.value
                                            ? (
                                                  stats.value?.total_users ?? 0
                                              ).toLocaleString()
                                            : '—'}
                                    </p>
                                    <p class="mt-0.5 text-[11px] text-slate-500 dark:text-gray-400">
                                        ইউনিক ভিজিটর · বিগত ৩০ দিন
                                    </p>
                                </div>
                            </div>
                        </section>

                        {/* Initial Unloaded State / Loading / Metrics */}
                        {!hasFetched.value && !isLoading.value ? (
                            <EmptyState
                                icon={BarChart3}
                                variant="simple"
                                title="Analytics ডাটা লোড করার জন্য প্রস্তুত"
                                description="পেজ লোড দ্রুত রাখতে অ্যানালিটিক্স ডাটা ডিমান্ড অনুযায়ী লোড হয়। দেখতে উপরে Load Analytics বাটনে ক্লিক করুন।"
                            />
                        ) : isLoading.value && !hasFetched.value ? (
                            <div class="flex flex-col items-center justify-center py-10 text-center">
                                <RefreshCw class="h-7 w-7 animate-spin text-indigo-600 dark:text-indigo-400" />
                                <p class="mt-3 text-xs font-medium text-slate-500 dark:text-gray-400">
                                    বিগত ৩০ দিনের অ্যানালিটিক্স লোড হচ্ছে...
                                </p>
                            </div>
                        ) : (
                            <div class="flex flex-1 flex-col gap-6">
                                {errorMsg.value && (
                                    <p class="text-center text-xs font-medium text-rose-600 dark:text-rose-400">
                                        {errorMsg.value}
                                    </p>
                                )}

                                {/* Top Traffic Sources — ranked list, two columns on lg */}
                                <section aria-label="Top traffic sources">
                                    <div class="flex items-center gap-2">
                                        <Share2 class="h-4 w-4 shrink-0 text-indigo-600 dark:text-indigo-400" />
                                        <h2 class="text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400">
                                            Top Traffic Sources
                                        </h2>
                                    </div>
                                    <p class="mt-1 text-[11px] text-slate-500 dark:text-gray-400">
                                        যেসব রেফারেল বা সোর্স থেকে ট্রাফিক এসেছে
                                    </p>

                                    {stats.value?.top_sources?.length ? (
                                        <ol class="mt-1 grid gap-x-8 lg:grid-cols-2">
                                            {stats.value?.top_sources?.map(
                                                (source, index) => {
                                                    const share =
                                                        topVisits > 0
                                                            ? Math.round(
                                                                  ((source.visits ??
                                                                      0) /
                                                                      topVisits) *
                                                                      100,
                                                              )
                                                            : 0;

                                                    return (
                                                        <li
                                                            key={index}
                                                            class="flex items-center gap-3 border-b border-slate-100 py-3 dark:border-gray-800"
                                                        >
                                                            <span class="w-6 shrink-0 text-xs font-bold text-slate-400 tabular-nums dark:text-gray-500">
                                                                {String(
                                                                    index + 1,
                                                                ).padStart(
                                                                    2,
                                                                    '0',
                                                                )}
                                                            </span>
                                                            <div class="min-w-0 flex-1">
                                                                <div class="flex items-baseline justify-between gap-3">
                                                                    <p
                                                                        class="truncate text-xs font-semibold text-slate-700 dark:text-gray-300"
                                                                        title={
                                                                            source.source
                                                                        }
                                                                    >
                                                                        {source.source ||
                                                                            'Direct / Unknown'}
                                                                    </p>
                                                                    <span class="shrink-0 text-xs font-bold text-slate-900 tabular-nums dark:text-gray-100">
                                                                        {source.visits?.toLocaleString() ??
                                                                            0}{' '}
                                                                        <span class="font-medium text-slate-400 dark:text-gray-500">
                                                                            visits
                                                                        </span>
                                                                    </span>
                                                                </div>
                                                                <div class="mt-1.5 h-1 overflow-hidden rounded-full bg-slate-100 dark:bg-gray-800">
                                                                    <div
                                                                        class="h-1 rounded-full bg-indigo-500 transition-all dark:bg-indigo-400"
                                                                        style={{
                                                                            width: `${share}%`,
                                                                        }}
                                                                    />
                                                                </div>
                                                            </div>
                                                        </li>
                                                    );
                                                },
                                            )}
                                        </ol>
                                    ) : (
                                        <div class="flex flex-col items-center justify-center py-10 text-center">
                                            <Share2 class="h-6 w-6 text-slate-300 dark:text-gray-600" />
                                            <p class="mt-2 text-xs font-medium text-slate-500 dark:text-gray-400">
                                                এখনও কোনো সোর্স ডাটা পাওয়া
                                                যায়নি।
                                            </p>
                                        </div>
                                    )}
                                </section>
                            </div>
                        )}

                        {/* Contributor guide — pinned to the bottom, no card, no dividers */}
                        <div class="mt-auto flex flex-col gap-3 py-1 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-2.5">
                                <BookOpen class="h-4 w-4 shrink-0 text-indigo-600 dark:text-indigo-400" />
                                <p class="text-xs text-slate-500 dark:text-gray-400">
                                    <span class="font-bold text-slate-900 dark:text-gray-100">
                                        কন্ট্রিবিউটর গাইড প্রয়োজন?{' '}
                                    </span>
                                    সাবজেক্ট ম্যানেজ, রিসোর্স আপলোড, ব্লগ তৈরি ও
                                    প্যানেল পারমিশন সংক্রান্ত বিস্তারিত গাইডলাইন
                                    দেখুন।
                                </p>
                            </div>

                            <Link
                                href="/guide"
                                class="group inline-flex h-9 shrink-0 cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 text-xs font-semibold text-white transition hover:bg-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 max-sm:w-full dark:bg-indigo-500 dark:hover:bg-indigo-600"
                            >
                                <span>গাইড দেখুন</span>
                                <ArrowRight class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" />
                            </Link>
                        </div>
                    </div>
                </>
            );
        };
    },
});
