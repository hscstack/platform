<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Pencil, Trash2 } from 'lucide-vue-next';

import { usePermissions } from '@/lib/usePermissions';

const { can } = usePermissions();

const { subject } = defineProps<{
    subject: any;
}>();

const emit = defineEmits<{
    (e: 'edit', subject: any): void;
}>();

const handleDelete = () => {
    if (confirm('Are you sure you want to delete this Subject?')) {
        router.delete(`/admin/subjects/${subject.id}`);
    }
};
</script>

<template>
    <div
        class="group relative flex items-center justify-between gap-3 rounded-xl border border-slate-100 bg-white p-3 transition-colors duration-150 hover:border-indigo-200 hover:bg-slate-50/50 sm:p-3.5 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-indigo-500/30 dark:hover:bg-gray-800/40"
    >
        <!-- Left: Subject Details as Link -->
        <Link
            :href="`/admin/subjects/${subject.id}/chapters`"
            class="flex min-w-0 flex-1 cursor-pointer items-center gap-3"
        >
            <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-black/5 bg-indigo-50 text-xs font-bold text-indigo-600 sm:h-10 sm:w-10 dark:border-white/10 dark:bg-indigo-950/60 dark:text-indigo-400"
            >
                {{ subject.name?.charAt(0) || 'S' }}
            </div>

            <div class="flex min-w-0 flex-wrap items-center gap-2">
                <h3
                    class="text-sm font-semibold break-words text-slate-900 transition-colors group-hover:text-indigo-600 dark:text-gray-100 dark:group-hover:text-indigo-400"
                >
                    {{ subject.name }}
                </h3>

                <span
                    v-if="subject.is_optional"
                    class="inline-flex items-center rounded-md bg-orange-50 px-1.5 py-0.5 text-[10px] font-semibold text-orange-700 ring-1 ring-orange-600/20 ring-inset dark:bg-orange-950/50 dark:text-orange-300 dark:ring-orange-500/30"
                >
                    Optional
                </span>

                <span
                    v-if="subject.is_disabled"
                    class="inline-flex items-center rounded-md bg-rose-50 px-1.5 py-0.5 text-[10px] font-semibold text-rose-700 ring-1 ring-rose-600/20 ring-inset dark:bg-rose-950/50 dark:text-rose-300 dark:ring-rose-500/30"
                >
                    Disabled
                </span>
            </div>
        </Link>

        <!-- Right: Actions -->
        <div
            v-if="can('manage curriculum')"
            class="flex shrink-0 items-center gap-1"
            @click.stop
        >
            <button
                type="button"
                @click="emit('edit', subject)"
                class="cursor-pointer rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-indigo-600 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-indigo-400"
                title="Edit Subject"
            >
                <Pencil class="h-4 w-4" :stroke-width="1.8" />
            </button>

            <button
                type="button"
                @click="handleDelete"
                class="cursor-pointer rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:text-gray-500 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                title="Delete Subject"
            >
                <Trash2 class="h-4 w-4" :stroke-width="1.8" />
            </button>
        </div>
    </div>
</template>
