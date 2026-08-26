<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Folder, Pencil, Trash2 } from 'lucide-vue-next';

const { node } = defineProps({
    node: Object,
});

const handleDelete = () => {
    if (confirm('Are you sure you want to delete this Folder?')) {
        router.delete(`/admin/nodes/${node.id}`);
    }
};
</script>

<template>
    <div
        @click="router.visit(`${$page.url}/${node.slug}`)"
        class="group relative flex cursor-pointer flex-col gap-3 rounded-xl border border-slate-200/90 bg-white p-3.5 transition-all duration-150 hover:border-indigo-300 hover:bg-slate-50/60 hover:shadow-xs sm:flex-row sm:items-center sm:justify-between sm:p-4 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-indigo-500/40 dark:hover:bg-gray-800/50"
    >
        <!-- Left: Icon + Full Title -->
        <div class="flex items-center gap-3.5 min-w-0">
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-indigo-200/40 bg-indigo-50 text-indigo-600 shadow-2xs transition-transform duration-200 group-hover:scale-105 sm:h-11 sm:w-11 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-400"
            >
                <Folder class="h-5 w-5 stroke-[2.2]" />
            </div>

            <div class="flex flex-col min-w-0">
                <h3
                    class="text-sm font-bold text-slate-800 break-words transition-colors group-hover:text-indigo-600 dark:text-gray-200 dark:group-hover:text-indigo-400"
                >
                    {{ node.name }}
                </h3>
            </div>
        </div>

        <!-- Right: Actions -->
        <div
            class="flex items-center gap-2 self-end sm:self-center"
            @click.stop
        >
            <Link
                :href="`/admin/nodes/edit/${node.id}`"
                class="inline-flex h-7 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-600 shadow-2xs transition-colors hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-indigo-500/30 dark:hover:bg-indigo-500/10 dark:hover:text-indigo-400"
                title="Edit Node"
            >
                <Pencil class="h-3 w-3" :stroke-width="2" />
                <span>Edit</span>
            </Link>

            <button
                type="button"
                @click="handleDelete"
                class="inline-flex h-7 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-rose-600 shadow-2xs transition-colors hover:border-red-200 hover:bg-red-50 hover:text-red-700 dark:border-gray-700 dark:bg-gray-800 dark:text-rose-400 dark:hover:border-red-500/30 dark:hover:bg-red-500/10"
                title="Delete Node"
            >
                <Trash2 class="h-3 w-3" :stroke-width="2" />
                <span>Delete</span>
            </button>
        </div>
    </div>
</template>
