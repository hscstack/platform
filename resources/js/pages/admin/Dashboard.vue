<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    LayoutGrid,
    Sparkles,
    Users,
    Eye,
    Share2,
    BookOpen,
    ArrowRight,
} from 'lucide-vue-next';

defineProps({
    stats: Object,
});
</script>

<template>
    <div class="animate-fade-in mx-auto max-w-7xl space-y-6 p-1">
        <!-- Main Dashboard Header -->
        <div
            class="relative overflow-hidden rounded-2xl border border-slate-300 bg-gradient-to-r from-white via-slate-50 to-white p-6 shadow-sm sm:p-8 dark:border-gray-700 dark:from-gray-900 dark:via-gray-950 dark:to-gray-900"
        >
            <div
                class="pointer-events-none absolute -top-10 -right-10 h-40 w-40 rounded-full bg-indigo-500/10 blur-3xl dark:bg-indigo-500/20"
            ></div>
            <div
                class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-4">
                    <div
                        class="hidden h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-slate-300 bg-gradient-to-b from-white to-slate-100 text-indigo-600 shadow-sm sm:flex dark:border-gray-700 dark:from-gray-900 dark:to-gray-800 dark:text-indigo-400"
                    >
                        <Sparkles class="h-5 w-5" />
                    </div>
                    <div>
                        <h1
                            class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl dark:text-gray-100"
                        >
                            Dashboard
                        </h1>
                        <p
                            class="mt-0.5 text-sm text-slate-600 dark:text-gray-400"
                        >
                            Welcome to your administration workspace. Real-time
                            operational overviews populate here.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Highlighted Guide/Tour Card (Placed Directly Below Header) -->
        <div
            class="relative overflow-hidden rounded-2xl border border-indigo-200 bg-indigo-50/60 p-5 shadow-xs transition-colors hover:border-indigo-300 sm:p-6 dark:border-indigo-500/30 dark:bg-indigo-500/10"
        >
            <div
                class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-start gap-3.5 sm:items-center">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-xs dark:bg-indigo-500"
                    >
                        <BookOpen class="h-5 w-5" />
                    </div>
                    <div>
                        <h2
                            class="text-base font-bold text-slate-900 dark:text-gray-100"
                        >
                            New to the Contributor Workspace?
                        </h2>
                        <p
                            class="mt-0.5 text-xs text-slate-600 sm:text-sm dark:text-gray-400"
                        >
                            Learn how to manage subjects, upload resources,
                            create blogs, and navigate panel permissions.
                        </p>
                    </div>
                </div>

                <Link
                    href="/guide"
                    class="group inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs transition-all hover:bg-indigo-700 active:scale-95"
                >
                    <span>Take a Tour</span>
                    <ArrowRight
                        class="h-4 w-4 transition-transform group-hover:translate-x-0.5"
                    />
                </Link>
            </div>
        </div>

        <!-- Metrics Section -->
        <div class="grid gap-6 md:grid-cols-2">
            <div class="space-y-6">
                <div
                    class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                >
                    <div class="flex items-center justify-between">
                        <p
                            class="text-sm font-semibold tracking-wide text-slate-600 uppercase dark:text-gray-400"
                        >
                            Total Visits
                        </p>
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-100 bg-indigo-50 text-indigo-600 dark:border-gray-800 dark:bg-indigo-500/10 dark:text-indigo-400"
                        >
                            <Eye class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <h3
                            class="text-3xl font-bold tracking-tight text-slate-900 dark:text-gray-100"
                        >
                            {{ stats?.total_visits ?? 0 }}
                        </h3>
                        <p
                            class="mt-1 text-xs text-slate-500 dark:text-gray-400"
                        >
                            Cumulative raw platform hits recorded
                        </p>
                    </div>
                </div>

                <div
                    class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                >
                    <div class="flex items-center justify-between">
                        <p
                            class="text-sm font-semibold tracking-wide text-slate-600 uppercase dark:text-gray-400"
                        >
                            Unique Visitors
                        </p>
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-100 bg-emerald-50 text-emerald-600 dark:border-gray-800 dark:bg-emerald-500/10 dark:text-emerald-400"
                        >
                            <Users class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <h3
                            class="text-3xl font-bold tracking-tight text-slate-900 dark:text-gray-100"
                        >
                            {{ stats?.total_users ?? 0 }}
                        </h3>
                        <p
                            class="mt-1 text-xs text-slate-500 dark:text-gray-400"
                        >
                            Distinct audiences grouped by IP address
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="flex flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900"
            >
                <div
                    class="flex items-center gap-3 border-b border-slate-100 pb-4 dark:border-gray-800"
                >
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
                    >
                        <Share2 class="h-4 w-4" />
                    </div>
                    <div>
                        <h3
                            class="font-semibold text-slate-900 dark:text-gray-100"
                        >
                            Top Acquisition Sources
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-gray-400">
                            Distribution by traffic reference origins
                        </p>
                    </div>
                </div>

                <div class="mt-4 max-h-[300px] flex-1 overflow-auto">
                    <div
                        v-if="stats?.top_sources?.length"
                        class="divide-y divide-slate-100 dark:divide-gray-800"
                    >
                        <div
                            v-for="(source, index) in stats.top_sources"
                            :key="index"
                            class="flex items-center justify-between py-3"
                        >
                            <div class="w-2/3 pr-4">
                                <p
                                    class="truncate text-sm font-medium text-slate-700 dark:text-gray-300"
                                    :title="source.source"
                                >
                                    {{ source.source || 'Direct / Unknown' }}
                                </p>
                            </div>
                            <div class="flex w-1/3 items-center justify-end">
                                <span
                                    class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-800 dark:bg-gray-800 dark:text-gray-200"
                                >
                                    {{ source.visits }} users
                                </span>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="flex h-full flex-col items-center justify-center py-12 text-center"
                    >
                        <LayoutGrid
                            class="h-8 w-8 text-slate-300 dark:text-gray-600"
                        />
                        <p
                            class="mt-2 text-sm font-medium text-slate-500 dark:text-gray-400"
                        >
                            No source data captured yet.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
