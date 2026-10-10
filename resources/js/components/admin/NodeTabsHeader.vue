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
        class="flex flex-col gap-2 border-b border-slate-200 pb-2.5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800"
    >
        <!-- Horizontal Scrollable Tabs on Mobile -->
        <div
            class="no-scrollbar flex items-center gap-1.5 overflow-x-auto py-0.5"
        >
            <button
                type="button"
                @click="emit('change-tab', 'live')"
                :class="[
                    activeTab === 'live'
                        ? 'bg-slate-900 text-white shadow-2xs dark:bg-white dark:text-slate-900'
                        : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200',
                ]"
                class="inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-semibold transition"
            >
                <span>Live</span>
                <span
                    class="py-0.2 rounded-full px-1.5 text-[10px] font-bold"
                    :class="
                        activeTab === 'live'
                            ? 'bg-white/20 text-white dark:bg-black/15 dark:text-slate-900'
                            : 'bg-slate-100 text-slate-600 dark:bg-gray-800 dark:text-gray-400'
                    "
                >
                    {{ liveCount }}
                </span>
            </button>

            <button
                type="button"
                @click="emit('change-tab', 'pending')"
                :class="[
                    activeTab === 'pending'
                        ? 'bg-amber-500 text-white shadow-2xs dark:bg-amber-500 dark:text-white'
                        : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200',
                ]"
                class="inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-semibold transition"
            >
                <span>Pending</span>
                <span
                    v-if="totalPendingCount > 0"
                    class="py-0.2 rounded-full px-1.5 text-[10px] font-bold"
                    :class="
                        activeTab === 'pending'
                            ? 'bg-white/25 text-white'
                            : 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300'
                    "
                >
                    {{ totalPendingCount }}
                </span>
                <span
                    v-else
                    class="py-0.2 rounded-full px-1.5 text-[10px] font-bold"
                    :class="
                        activeTab === 'pending'
                            ? 'bg-white/25 text-white'
                            : 'bg-slate-100 text-slate-600 dark:bg-gray-800 dark:text-gray-400'
                    "
                >
                    0
                </span>
            </button>

            <button
                type="button"
                @click="emit('change-tab', 'rejected')"
                :class="[
                    activeTab === 'rejected'
                        ? 'bg-rose-600 text-white shadow-2xs dark:bg-rose-600 dark:text-white'
                        : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200',
                ]"
                class="inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-semibold transition"
            >
                <span>Rejected</span>
                <span
                    v-if="totalRejectedCount > 0"
                    class="py-0.2 rounded-full px-1.5 text-[10px] font-bold"
                    :class="
                        activeTab === 'rejected'
                            ? 'bg-white/25 text-white'
                            : 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300'
                    "
                >
                    {{ totalRejectedCount }}
                </span>
                <span
                    v-else
                    class="py-0.2 rounded-full px-1.5 text-[10px] font-bold"
                    :class="
                        activeTab === 'rejected'
                            ? 'bg-white/25 text-white'
                            : 'bg-slate-100 text-slate-600 dark:bg-gray-800 dark:text-gray-400'
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
                class="inline-flex cursor-pointer items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-indigo-600 shadow-2xs transition hover:bg-indigo-50 hover:text-indigo-700 dark:border-gray-700 dark:bg-gray-800 dark:text-indigo-400 dark:hover:bg-indigo-950/40"
            >
                <PencilLine class="h-3.5 w-3.5" />
                <span>Bulk Rename</span>
            </button>
        </div>
    </div>
</template>
