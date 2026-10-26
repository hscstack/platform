<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { BookOpen, Pencil, Trash2 } from 'lucide-vue-next';
import { usePermissions } from '@/lib/usePermissions';

const { can } = usePermissions();

const props = defineProps<{
    chapter: any;
}>();

const emit = defineEmits<{
    (e: 'edit', chapter: any): void;
}>();

const handleDelete = () => {
    if (confirm('Are you sure you want to delete this Chapter?')) {
        router.delete(`/admin/chapters/${props.chapter?.id}`);
    }
};
</script>

<template>
    <div
        class="group relative flex flex-col justify-between gap-2.5 rounded-xl border border-slate-100 bg-white p-3 transition-colors duration-150 hover:border-indigo-200 hover:bg-slate-50/50 sm:flex-row sm:items-center sm:gap-3 sm:p-3.5 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-indigo-500/30 dark:hover:bg-gray-800/40"
    >
        <!-- Top / Left: Icon + Chapter Details -->
        <div
            class="flex min-w-0 flex-1 items-start gap-2.5 sm:items-center sm:gap-3"
        >
            <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-indigo-200/40 bg-indigo-50 text-indigo-600 sm:h-10 sm:w-10 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-400"
            >
                <BookOpen class="h-4.5 w-4.5 stroke-[2]" />
            </div>

            <div
                class="flex min-w-0 flex-1 flex-col gap-1 sm:flex-row sm:flex-wrap sm:items-center sm:gap-2"
            >
                <h3
                    class="text-sm font-semibold text-slate-900 transition-colors dark:text-gray-100"
                >
                    {{ chapter?.name }}
                </h3>

                <span
                    class="inline-flex w-fit items-center gap-1 rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-700 ring-1 ring-slate-600/10 ring-inset dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700"
                >
                    Weight: {{ chapter?.weight ?? 2 }}
                </span>
            </div>
        </div>

        <!-- Bottom / Right: Actions -->
        <div
            v-if="can('manage curriculum')"
            class="flex shrink-0 items-center justify-end gap-1 border-t border-slate-100 pt-1 sm:border-0 sm:pt-0 dark:border-gray-800/80"
        >
            <button
                type="button"
                @click="emit('edit', chapter)"
                class="cursor-pointer rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-indigo-600 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-indigo-400"
                title="Edit Chapter"
            >
                <Pencil class="h-4 w-4" :stroke-width="1.8" />
            </button>

            <button
                type="button"
                @click="handleDelete"
                class="cursor-pointer rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:text-gray-500 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                title="Delete Chapter"
            >
                <Trash2 class="h-4 w-4" :stroke-width="1.8" />
            </button>
        </div>
    </div>
</template>
