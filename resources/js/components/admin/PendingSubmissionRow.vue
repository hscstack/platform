<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Clock, AlertCircle } from 'lucide-vue-next';

defineProps<{
    submission: any;
    status: 'pending' | 'rejected';
}>();
</script>

<template>
    <!-- Pending Submission Row -->
    <Link
        v-if="status === 'pending'"
        :href="`/resources/${submission?.id}`"
        class="group relative flex items-center justify-between gap-3 rounded-xl border border-amber-200/80 bg-amber-50/50 p-3 transition hover:border-amber-300 hover:bg-amber-50 sm:p-3.5 dark:border-amber-900/40 dark:bg-amber-950/20 dark:hover:border-amber-800/80"
    >
        <div class="flex min-w-0 flex-1 items-center gap-3">
            <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-amber-300 bg-amber-100 text-amber-700 sm:h-10 sm:w-10 dark:border-amber-800 dark:bg-amber-900/50 dark:text-amber-300"
            >
                <Clock class="h-4.5 w-4.5 animate-pulse" />
            </div>

            <div class="flex min-w-0 flex-wrap items-center gap-2">
                <h3
                    class="text-sm font-semibold break-words text-slate-900 transition-colors group-hover:text-amber-700 dark:text-gray-100 dark:group-hover:text-amber-300"
                >
                    {{ submission.title || '(Untitled)' }}
                </h3>

                <span
                    class="inline-flex items-center gap-1 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-800 dark:bg-amber-500/20 dark:text-amber-300"
                >
                    Pending Review
                </span>

                <span
                    v-if="submission.user?.name"
                    class="text-xs text-slate-400 dark:text-gray-500"
                >
                    by {{ submission.user.name }}
                </span>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-2">
            <span
                class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
            >
                Review
            </span>
        </div>
    </Link>

    <!-- Rejected Submission Row -->
    <Link
        v-else-if="status === 'rejected'"
        :href="`/resources/${submission?.id}`"
        class="group relative flex items-center justify-between gap-3 rounded-xl border border-rose-200/80 bg-rose-50/50 p-3 transition hover:border-rose-300 hover:bg-rose-50 sm:p-3.5 dark:border-rose-900/40 dark:bg-rose-950/20 dark:hover:border-rose-800/80"
    >
        <div class="flex min-w-0 flex-1 items-center gap-3">
            <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-rose-300 bg-rose-100 text-rose-700 sm:h-10 sm:w-10 dark:border-rose-800 dark:bg-rose-900/50 dark:text-rose-300"
            >
                <AlertCircle
                    class="h-4.5 w-4.5 text-rose-600 dark:text-rose-400"
                />
            </div>

            <div class="flex min-w-0 flex-wrap items-center gap-2">
                <h3
                    class="text-sm font-semibold break-words text-slate-900 transition-colors group-hover:text-rose-700 dark:text-gray-100 dark:group-hover:text-rose-300"
                >
                    {{ submission.title || '(Untitled)' }}
                </h3>

                <span
                    class="inline-flex items-center gap-1 rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-bold text-rose-800 dark:bg-rose-500/20 dark:text-rose-300"
                >
                    Rejected
                </span>

                <span
                    v-if="submission.user?.name"
                    class="text-xs text-slate-400 dark:text-gray-500"
                >
                    by {{ submission.user.name }}
                </span>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-2">
            <span
                class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-white px-2.5 py-1 text-xs font-semibold text-rose-700 shadow-2xs hover:bg-rose-50 dark:border-rose-800 dark:bg-gray-800 dark:text-rose-300 dark:hover:bg-gray-700"
            >
                View
            </span>
        </div>
    </Link>
</template>
