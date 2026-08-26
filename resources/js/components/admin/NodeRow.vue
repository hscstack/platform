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
        class="group relative flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-slate-100 bg-white p-3 transition-colors duration-150 hover:border-indigo-200 hover:bg-slate-50/50 sm:p-3.5 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-indigo-500/30 dark:hover:bg-gray-800/40"
    >
        <!-- Left: Icon + Full Title -->
        <div class="flex items-center gap-3 min-w-0 flex-1">
            <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-indigo-200/40 bg-indigo-50 text-indigo-600 sm:h-10 sm:w-10 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-400"
            >
                <Folder class="h-4.5 w-4.5 stroke-[2]" />
            </div>

            <div class="min-w-0 flex-1">
                <h3
                    class="text-sm font-semibold text-slate-900 break-words transition-colors group-hover:text-indigo-600 dark:text-gray-100 dark:group-hover:text-indigo-400"
                >
                    {{ node.name }}
                </h3>
            </div>
        </div>

        <!-- Right: Actions -->
        <div
            class="flex shrink-0 items-center gap-1"
            @click.stop
        >
            <Link
                :href="`/admin/nodes/edit/${node.id}`"
                class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-indigo-600 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-indigo-400"
                title="Edit Node"
            >
                <Pencil class="h-4 w-4" :stroke-width="1.8" />
            </Link>

            <button
                type="button"
                @click="handleDelete"
                class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:text-gray-500 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                title="Delete Node"
            >
                <Trash2 class="h-4 w-4" :stroke-width="1.8" />
            </button>
        </div>
    </div>
</template>
