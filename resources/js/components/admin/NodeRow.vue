<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Folder, Lock, Pencil, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';
import { usePermissions } from '@/lib/usePermissions';

const { can } = usePermissions();

const props = defineProps({
    node: Object,
    isFrozen: Boolean,
});

const isNodeEffectivelyFrozen = computed(() => {
    return props.isFrozen || Boolean(props.node?.is_frozen);
});

const emit = defineEmits<{
    (e: 'edit', node: any): void;
}>();

const handleDelete = () => {
    if (confirm('Are you sure you want to delete this Folder?')) {
        router.delete(`/admin/nodes/${props.node?.id}`);
    }
};
</script>

<template>
    <div
        class="group relative flex items-center justify-between gap-3 rounded-xl border border-slate-100 bg-white p-3 transition-colors duration-150 hover:border-indigo-200 hover:bg-slate-50/50 sm:p-3.5 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-indigo-500/30 dark:hover:bg-gray-800/40"
    >
        <!-- Left: Icon + Full Title -->
        <div class="flex min-w-0 flex-1 items-center gap-3">
            <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-indigo-200/40 bg-indigo-50 text-indigo-600 sm:h-10 sm:w-10 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-400"
            >
                <Folder class="h-4.5 w-4.5 stroke-[2]" />
            </div>

            <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
                <h3
                    class="text-sm font-semibold break-words text-slate-900 transition-colors dark:text-gray-100"
                >
                    {{ node?.name }}
                </h3>
                <span
                    v-if="node?.slug"
                    class="font-mono text-xs text-slate-400 dark:text-gray-500"
                >
                    /{{ node?.slug }}
                </span>
                <span
                    v-if="node?.is_trackable"
                    class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-emerald-600/20 ring-inset dark:bg-emerald-950/50 dark:text-emerald-300 dark:ring-emerald-500/30"
                >
                    <span
                        v-if="node?.weight === 3"
                        class="font-normal text-slate-500 dark:text-gray-400"
                        >Large •</span
                    >
                    <span
                        v-else-if="node?.weight === 1"
                        class="font-normal text-slate-500 dark:text-gray-400"
                        >Small •</span
                    >
                    <span
                        v-else
                        class="font-normal text-slate-500 dark:text-gray-400"
                        >Normal •</span
                    >
                    <span>Chapter</span>
                </span>
                <span
                    v-if="node?.is_frozen || isFrozen"
                    class="inline-flex items-center gap-1 rounded-md bg-sky-50 px-1.5 py-0.5 text-[10px] font-semibold text-sky-700 ring-1 ring-sky-600/20 ring-inset dark:bg-sky-950/50 dark:text-sky-300 dark:ring-sky-500/30"
                >
                    <Lock class="h-2.5 w-2.5" />
                    Frozen
                </span>
            </div>
        </div>

        <!-- Right: Actions -->
        <div
            v-if="!isNodeEffectivelyFrozen && can('manage curriculum')"
            class="flex shrink-0 items-center gap-1"
            @click.stop
        >
            <button
                type="button"
                @click="emit('edit', node)"
                class="cursor-pointer rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-indigo-600 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-indigo-400"
                title="Edit Folder"
            >
                <Pencil class="h-4 w-4" :stroke-width="1.8" />
            </button>

            <button
                type="button"
                @click="handleDelete"
                class="cursor-pointer rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:text-gray-500 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                title="Delete Folder"
            >
                <Trash2 class="h-4 w-4" :stroke-width="1.8" />
            </button>
        </div>
    </div>
</template>
