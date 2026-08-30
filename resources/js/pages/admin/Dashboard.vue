<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    LayoutGrid,
    Sparkles,
    Users,
    Eye,
    Share2,
    BookOpen,
    ArrowRight,
    Zap,
    RefreshCw,
    BarChart3,
} from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    totalAccounts?: number;
}>();

const stats = ref<any>(null);
const isLoading = ref(false);
const hasFetched = ref(false);
const errorMsg = ref<string | null>(null);

const fetchAnalytics = async (refresh = false) => {
    isLoading.value = true;
    errorMsg.value = null;

    try {
        const res = await fetch(
            `/admin/analytics${refresh ? '?refresh=1' : ''}`,
        );

        if (!res.ok) {
            throw new Error('Analytics ডাটা লোড করা যায়নি');
        }

        stats.value = await res.json();
        hasFetched.value = true;
    } catch (e: any) {
        errorMsg.value = e.message || 'Analytics লোড করতে সমস্যা হয়েছে';
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <Head title="Staff Panel" />

    <div class="animate-fade-in mx-auto max-w-7xl space-y-6 p-1">
        <!-- Main Dashboard Header -->
        <div
            class="relative overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-r from-white via-slate-50 to-white p-5 shadow-2xs sm:p-6 dark:border-gray-800 dark:from-gray-900 dark:via-gray-950 dark:to-gray-900"
        >
            <div
                class="pointer-events-none absolute -top-10 -right-10 h-40 w-40 rounded-full bg-indigo-500/10 blur-3xl dark:bg-indigo-500/20"
            ></div>
            <div
                class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-3.5">
                    <div
                        class="hidden h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-gradient-to-b from-white to-slate-100 text-indigo-600 shadow-2xs sm:flex dark:border-gray-800 dark:from-gray-900 dark:to-gray-800 dark:text-indigo-400"
                    >
                        <Sparkles class="h-4.5 w-4.5" />
                    </div>
                    <div>
                        <h1
                            class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl dark:text-gray-100"
                        >
                            Dashboard
                        </h1>
                        <p
                            class="mt-0.5 text-xs text-slate-600 dark:text-gray-400"
                        >
                            অ্যাডমিন ওয়ার্কস্পেসের সামগ্রিক তথ্য ও
                            অ্যানালিটিক্স।
                        </p>
                    </div>
                </div>

                <!-- On-Demand Load / Reload Button -->
                <button
                    type="button"
                    @click="fetchAnalytics(hasFetched)"
                    :disabled="isLoading"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-2xs transition-colors hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-600 active:scale-95 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-indigo-500/30 dark:hover:bg-indigo-500/10 dark:hover:text-indigo-400"
                >
                    <RefreshCw
                        class="h-3.5 w-3.5"
                        :class="{ 'animate-spin': isLoading }"
                    />
                    <span>{{
                        hasFetched
                            ? isLoading
                                ? 'রিফ্রেশ হচ্ছে...'
                                : 'Reload Analytics'
                            : isLoading
                              ? 'লোড হচ্ছে...'
                              : 'Load Analytics'
                    }}</span>
                </button>
            </div>
        </div>

        <!-- Overview Stats Grid -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div
                class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p
                            class="text-xs font-bold tracking-wider text-slate-600 uppercase dark:text-gray-400"
                        >
                            Total Accounts
                        </p>
                        <h3
                            class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl dark:text-gray-100"
                        >
                            {{
                                (
                                    stats?.total_accounts ??
                                    props.totalAccounts ??
                                    0
                                ).toLocaleString()
                            }}
                        </h3>
                        <p
                            class="mt-1 text-xs text-slate-500 dark:text-gray-400"
                        >
                            সর্বমোট নিবন্ধিত একাউন্ট
                        </p>
                    </div>
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-2xl border border-indigo-100 bg-indigo-50 text-indigo-600 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-400"
                    >
                        <Users class="h-5 w-5" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Highlighted Guide/Tour Card -->
        <div
            class="relative overflow-hidden rounded-2xl border border-indigo-200/70 bg-indigo-50/50 p-4 shadow-2xs transition-colors hover:border-indigo-300 sm:p-5 dark:border-indigo-500/30 dark:bg-indigo-500/10"
        >
            <div
                class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-start gap-3 sm:items-center">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-2xs dark:bg-indigo-500"
                    >
                        <BookOpen class="h-4.5 w-4.5" />
                    </div>
                    <div>
                        <h2
                            class="text-sm font-bold text-slate-900 dark:text-gray-100"
                        >
                            কন্ট্রিবিউটর গাইড প্রয়োজন?
                        </h2>
                        <p
                            class="mt-0.5 text-xs text-slate-600 dark:text-gray-400"
                        >
                            সাবজেক্ট ম্যানেজ, রিসোর্স আপলোড, ব্লগ তৈরি ও প্যানেল
                            পারমিশন সংক্রান্ত বিস্তারিত গাইডলাইন দেখুন।
                        </p>
                    </div>
                </div>

                <Link
                    href="/guide"
                    class="group inline-flex shrink-0 items-center justify-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 py-2 text-xs font-bold text-white shadow-2xs transition-all hover:bg-indigo-700 active:scale-95"
                >
                    <span>গাইড দেখুন</span>
                    <ArrowRight
                        class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5"
                    />
                </Link>
            </div>
        </div>

        <!-- Initial Unloaded State Placeholder -->
        <div
            v-if="!hasFetched && !isLoading"
            class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 py-12 text-center dark:border-gray-800 dark:bg-gray-900/40"
        >
            <div
                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white shadow-xs dark:bg-gray-800"
            >
                <BarChart3
                    class="h-6 w-6 text-indigo-600 dark:text-indigo-400"
                />
            </div>
            <h3
                class="mt-3 text-sm font-bold text-slate-800 dark:text-gray-200"
            >
                Analytics ডাটা লোড করার জন্য প্রস্তুত
            </h3>
            <p class="mt-1 max-w-sm text-xs text-slate-500 dark:text-gray-400">
                পেজ লোড দ্রুত রাখতে অ্যানালিটিক্স ডাটা ডিমান্ড অনুযায়ী লোড হয়।
                লাইভ মেট্রিক্স দেখতে নিচে ক্লিক করুন।
            </p>
            <button
                type="button"
                @click="fetchAnalytics(false)"
                class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition-colors hover:bg-indigo-700 active:scale-95"
            >
                <RefreshCw class="h-3.5 w-3.5" />
                <span>Fetch Live Analytics</span>
            </button>
        </div>

        <!-- Loading State Spinner -->
        <div
            v-else-if="isLoading && !hasFetched"
            class="flex flex-col items-center justify-center rounded-2xl border border-slate-200 bg-white py-16 text-center shadow-2xs dark:border-gray-800 dark:bg-gray-900"
        >
            <RefreshCw
                class="h-8 w-8 animate-spin text-indigo-600 dark:text-indigo-400"
            />
            <p
                class="mt-3 text-xs font-medium text-slate-500 dark:text-gray-400"
            >
                বিগত ৩০ দিনের অ্যানালিটিক্স লোড হচ্ছে...
            </p>
        </div>

        <!-- Metrics Section (Rendered once loaded) -->
        <div v-else class="grid gap-6 md:grid-cols-2">
            <div class="space-y-6">
                <!-- Realtime Active -->
                <div
                    class="relative overflow-hidden rounded-2xl border border-emerald-200/70 bg-gradient-to-br from-emerald-500/10 via-white to-white p-5 shadow-2xs dark:border-emerald-500/30 dark:from-emerald-950/40 dark:via-gray-900 dark:to-gray-900"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="relative flex h-2.5 w-2.5">
                                <span
                                    class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                                ></span>
                                <span
                                    class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"
                                ></span>
                            </span>
                            <p
                                class="text-xs font-bold tracking-wider text-emerald-900 uppercase dark:text-emerald-300"
                            >
                                Active Now
                            </p>
                        </div>
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl border border-emerald-200 bg-emerald-100/60 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/20 dark:text-emerald-300"
                        >
                            <Zap class="h-4.5 w-4.5" />
                        </div>
                    </div>
                    <div class="mt-3.5">
                        <h3
                            class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl dark:text-gray-100"
                        >
                            {{ stats?.realtime_users ?? 0 }}
                        </h3>
                        <p
                            class="mt-0.5 text-xs text-slate-500 dark:text-gray-400"
                        >
                            গত ৫ মিনিটে একটিভ ইউজার
                        </p>
                    </div>
                </div>

                <!-- Total Visits -->
                <div
                    class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="flex items-center justify-between">
                        <p
                            class="text-xs font-bold tracking-wider text-slate-600 uppercase dark:text-gray-400"
                        >
                            Total Visits
                        </p>
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-100 bg-indigo-50 text-indigo-600 dark:border-gray-800 dark:bg-indigo-500/10 dark:text-indigo-400"
                        >
                            <Eye class="h-4.5 w-4.5" />
                        </div>
                    </div>
                    <div class="mt-3.5">
                        <h3
                            class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl dark:text-gray-100"
                        >
                            {{ stats?.total_visits?.toLocaleString() ?? 0 }}
                        </h3>
                        <p
                            class="mt-0.5 text-xs text-slate-500 dark:text-gray-400"
                        >
                            সর্বমোট পেজ ভিউ (বিগত ৩০ দিন)
                        </p>
                    </div>
                </div>

                <!-- Unique Visitors -->
                <div
                    class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="flex items-center justify-between">
                        <p
                            class="text-xs font-bold tracking-wider text-slate-600 uppercase dark:text-gray-400"
                        >
                            Unique Visitors
                        </p>
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-100 bg-blue-50 text-blue-600 dark:border-gray-800 dark:bg-blue-500/10 dark:text-blue-400"
                        >
                            <Users class="h-4.5 w-4.5" />
                        </div>
                    </div>
                    <div class="mt-3.5">
                        <h3
                            class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl dark:text-gray-100"
                        >
                            {{ stats?.total_users?.toLocaleString() ?? 0 }}
                        </h3>
                        <p
                            class="mt-0.5 text-xs text-slate-500 dark:text-gray-400"
                        >
                            ইউনিক ভিজিটর সংখ্যা (বিগত ৩০ দিন)
                        </p>
                    </div>
                </div>
            </div>

            <!-- Top Acquisition Sources -->
            <div
                class="flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
            >
                <div
                    class="flex items-center gap-3 border-b border-slate-100 pb-3.5 dark:border-gray-800"
                >
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
                    >
                        <Share2 class="h-4 w-4" />
                    </div>
                    <div>
                        <h3
                            class="text-sm font-bold text-slate-900 dark:text-gray-100"
                        >
                            Top Traffic Sources
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-gray-400">
                            যেসব রেফারেল বা সোর্স থেকে ট্রাফিক এসেছে
                        </p>
                    </div>
                </div>

                <div class="mt-3.5 flex-1">
                    <div
                        v-if="stats?.top_sources?.length"
                        class="divide-y divide-slate-100 dark:divide-gray-800"
                    >
                        <div
                            v-for="(source, index) in stats.top_sources"
                            :key="index"
                            class="flex items-center justify-between py-2.5"
                        >
                            <div class="w-2/3 pr-4">
                                <p
                                    class="truncate text-xs font-semibold text-slate-700 dark:text-gray-300"
                                    :title="source.source"
                                >
                                    {{ source.source || 'Direct / Unknown' }}
                                </p>
                            </div>
                            <div class="flex w-1/3 items-center justify-end">
                                <span
                                    class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-800 dark:bg-gray-800 dark:text-gray-200"
                                >
                                    {{ source.visits?.toLocaleString() }} visits
                                </span>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="flex h-full flex-col items-center justify-center py-10 text-center"
                    >
                        <LayoutGrid
                            class="h-7 w-7 text-slate-300 dark:text-gray-600"
                        />
                        <p
                            class="mt-2 text-xs font-medium text-slate-500 dark:text-gray-400"
                        >
                            এখনও কোনো সোর্স ডাটা পাওয়া যায়নি।
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
