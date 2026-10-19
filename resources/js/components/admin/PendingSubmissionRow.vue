<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Clock, AlertCircle, Eye } from 'lucide-vue-next';
import { usePermissions } from '@/lib/usePermissions';

defineProps<{
    submission: any;
    status: 'pending' | 'rejected';
}>();

const emit = defineEmits<{
    (e: 'preview', submission: any): void;
}>();

const { can } = usePermissions();
</script>

<template>
    <!-- Pending Submission Row -->
    <div
        v-if="status === 'pending'"
        @click="emit('preview', submission)"
        class="group relative flex cursor-pointer items-center justify-between gap-3 rounded-xl bg-amber-50/60 p-3 ring-1 ring-amber-200/70 backdrop-blur-xl transition-colors duration-150 hover:bg-amber-50 sm:p-3.5 dark:bg-amber-500/10 dark:ring-amber-500/20 dark:hover:bg-amber-500/15 dark:hover:ring-amber-500/30"
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
                    {{ submission.payload?.title || '(Untitled)' }}
                </h3>

                <span
                    class="inline-flex items-center gap-1 rounded-md bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-800 dark:bg-amber-500/20 dark:text-amber-300"
                >
                    New Upload Pending
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
            <button
                type="button"
                @click.stop="emit('preview', submission)"
                class="inline-flex h-7 cursor-pointer items-center gap-1 rounded-[10px] bg-white/70 px-2.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-200/60 backdrop-blur transition-colors hover:bg-white/90 hover:text-slate-900 dark:bg-slate-900/60 dark:text-gray-300 dark:ring-slate-800/60 dark:hover:bg-slate-900/85"
            >
                <Eye class="h-3.5 w-3.5 text-slate-500" />
                <span>Preview</span>
            </button>

            <Link
                v-if="can('moderate resources')"
                href="/admin/moderation/resources"
                @click.stop
                class="inline-flex h-7 cursor-pointer items-center gap-1 rounded-[10px] bg-amber-50/80 px-2.5 text-xs font-semibold text-amber-800 ring-1 ring-amber-300/60 backdrop-blur transition-colors hover:bg-amber-100/80 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/25 dark:hover:bg-amber-500/15"
            >
                Review in Queue
            </Link>
        </div>
    </div>

    <!-- Rejected Submission Row -->
    <div
        v-else-if="status === 'rejected'"
        @click="emit('preview', submission)"
        class="group relative flex cursor-pointer items-center justify-between gap-3 rounded-xl bg-rose-50/60 p-3 ring-1 ring-rose-200/70 backdrop-blur-xl transition-colors duration-150 hover:bg-rose-50 sm:p-3.5 dark:bg-rose-500/10 dark:ring-rose-500/20 dark:hover:bg-rose-500/15 dark:hover:ring-rose-500/30"
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
                    {{ submission.payload?.title || '(Untitled)' }}
                </h3>

                <span
                    class="inline-flex items-center gap-1 rounded-md bg-rose-100 px-1.5 py-0.5 text-[10px] font-bold text-rose-800 dark:bg-rose-500/20 dark:text-rose-300"
                >
                    Upload Rejected
                </span>

                <span
                    v-if="submission.rejection_reason"
                    class="max-w-xs truncate text-xs text-rose-600/90 dark:text-rose-400/90"
                >
                    Reason: {{ submission.rejection_reason }}
                </span>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-2">
            <button
                type="button"
                @click.stop="emit('preview', submission)"
                class="inline-flex h-7 cursor-pointer items-center gap-1 rounded-[10px] bg-white/70 px-2.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-200/60 backdrop-blur transition-colors hover:bg-white/90 hover:text-slate-900 dark:bg-slate-900/60 dark:text-gray-300 dark:ring-slate-800/60 dark:hover:bg-slate-900/85"
            >
                <Eye class="h-3.5 w-3.5 text-slate-500" />
                <span>Feedback</span>
            </button>
        </div>
    </div>
</template>
