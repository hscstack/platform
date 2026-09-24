<script setup lang="ts">
import { PencilLine } from 'lucide-vue-next';

defineProps<{
    activeTab: 'live' | 'pending' | 'rejected';
    liveCount: number;
    totalPendingCount: number;
    totalRejectedCount: number;
    canBulkRename: boolean;
}>();

const emit = defineEmits<{
    (e: 'change-tab', tab: 'live' | 'pending' | 'rejected'): void;
    (e: 'bulk-rename'): void;
}>();
</script>

<template>
    <div
        class="flex flex-col gap-2 border-b border-slate-200/60 pb-2.5 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800/60"
    >
        <!-- Horizontal Scrollable Tabs on Mobile -->
        <div
            class="no-scrollbar flex items-center gap-1.5 overflow-x-auto py-0.5"
        >
            <button
                type="button"
                :aria-pressed="activeTab === 'live'"
                @click="emit('change-tab', 'live')"
                :class="[
                    activeTab === 'live'
                        ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200/60 dark:bg-indigo-500/10 dark:text-indigo-200 dark:ring-indigo-500/20'
                        : 'text-slate-600 hover:bg-white/70 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/70 dark:hover:text-slate-100',
                ]"
                class="inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-[10px] px-3 py-1.5 text-xs font-semibold tracking-tight transition-colors duration-150"
            >
                <span>Live</span>
                <span
                    class="rounded-md px-1.5 py-0.5 text-[10px] font-bold"
                    :class="
                        activeTab === 'live'
                            ? 'bg-indigo-100/80 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-200'
                            : 'bg-slate-200/70 text-slate-600 dark:bg-gray-800 dark:text-gray-400'
                    "
                >
                    {{ liveCount }}
                </span>
            </button>

            <button
                type="button"
                :aria-pressed="activeTab === 'pending'"
                @click="emit('change-tab', 'pending')"
                :class="[
                    activeTab === 'pending'
                        ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-300/60 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20'
                        : 'text-slate-600 hover:bg-white/70 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/70 dark:hover:text-slate-100',
                ]"
                class="inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-[10px] px-3 py-1.5 text-xs font-semibold tracking-tight transition-colors duration-150"
            >
                <span>Pending</span>
                <span
                    v-if="totalPendingCount > 0"
                    class="rounded-md px-1.5 py-0.5 text-[10px] font-bold"
                    :class="
                        activeTab === 'pending'
                            ? 'bg-amber-100/80 text-amber-800 dark:bg-amber-500/20 dark:text-amber-200'
                            : 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400'
                    "
                >
                    {{ totalPendingCount }}
                </span>
                <span
                    v-else
                    class="rounded-md px-1.5 py-0.5 text-[10px] font-bold"
                    :class="
                        activeTab === 'pending'
                            ? 'bg-amber-100/80 text-amber-800 dark:bg-amber-500/20 dark:text-amber-200'
                            : 'bg-slate-200/70 text-slate-600 dark:bg-gray-800 dark:text-gray-400'
                    "
                >
                    0
                </span>
            </button>

            <button
                type="button"
                :aria-pressed="activeTab === 'rejected'"
                @click="emit('change-tab', 'rejected')"
                :class="[
                    activeTab === 'rejected'
                        ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-200/60 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/20'
                        : 'text-slate-600 hover:bg-white/70 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/70 dark:hover:text-slate-100',
                ]"
                class="inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-[10px] px-3 py-1.5 text-xs font-semibold tracking-tight transition-colors duration-150"
            >
                <span>Rejected</span>
                <span
                    v-if="totalRejectedCount > 0"
                    class="rounded-md px-1.5 py-0.5 text-[10px] font-bold"
                    :class="
                        activeTab === 'rejected'
                            ? 'bg-rose-100/80 text-rose-700 dark:bg-rose-500/20 dark:text-rose-200'
                            : 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-400'
                    "
                >
                    {{ totalRejectedCount }}
                </span>
                <span
                    v-else
                    class="rounded-md px-1.5 py-0.5 text-[10px] font-bold"
                    :class="
                        activeTab === 'rejected'
                            ? 'bg-rose-100/80 text-rose-700 dark:bg-rose-500/20 dark:text-rose-200'
                            : 'bg-slate-200/70 text-slate-600 dark:bg-gray-800 dark:text-gray-400'
                    "
                >
                    0
                </span>
            </button>
        </div>

        <!-- Bulk Rename Action (if available and in live tab) -->
        <div
            v-if="canBulkRename && activeTab === 'live'"
            class="flex shrink-0 items-center"
        >
            <button
                type="button"
                @click="emit('bulk-rename')"
                class="inline-flex h-8 cursor-pointer items-center gap-1 rounded-[10px] bg-white/70 px-2.5 text-xs font-semibold text-indigo-600 ring-1 ring-slate-200/60 backdrop-blur transition-colors hover:bg-white/90 hover:text-indigo-700 dark:bg-slate-900/60 dark:text-indigo-400 dark:ring-slate-800/60 dark:hover:bg-slate-900/85"
            >
                <PencilLine class="h-3.5 w-3.5" />
                <span>Bulk Rename</span>
            </button>
        </div>
    </div>
</template>
