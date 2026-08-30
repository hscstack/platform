<script setup lang="ts">
import { router } from '@inertiajs/vue3';

import {
    Search,
    Atom,
    FlaskConical,
    Dna,
    Sigma,
    Laptop,
    BookOpen,
    PenTool,
    BarChart3,
    Pencil,
    Trash2,
} from 'lucide-vue-next';
import { usePermissions } from '@/lib/usePermissions';

const { can } = usePermissions();

const { subject } = defineProps({
    subject: Object,
});

const emit = defineEmits<{
    (e: 'edit', subject: any): void;
}>();

const icons = {
    Atom,
    FlaskConical,
    Dna,
    Sigma,
    Laptop,
    BookOpen,
    PenTool,
    BarChart3,
    Search,
};

const handleDelete = () => {
    if (confirm('Are you sure you want to delete this Subject?')) {
        router.delete(`/admin/subjects/${subject.id}`);
    }
};
</script>

<template>
    <div
        @click="router.visit(`/admin/subjects/${subject.slug}/nodes`)"
        class="group relative flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-slate-100 bg-white p-3 transition-colors duration-150 hover:border-indigo-200 hover:bg-slate-50/50 sm:p-3.5 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-indigo-500/30 dark:hover:bg-gray-800/40"
    >
        <!-- Left: Icon + Subject Details (Full title, no ellipsis) -->
        <div class="flex min-w-0 flex-1 items-center gap-3">
            <div
                :class="[
                    subject.tailwind_format ||
                        'bg-slate-100 text-slate-600 dark:bg-gray-800 dark:text-gray-400',
                    'flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-black/5 sm:h-10 sm:w-10 dark:border-white/10',
                ]"
            >
                <component
                    :is="icons[subject.icon] || BookOpen"
                    class="h-4.5 w-4.5 stroke-[2]"
                />
            </div>

            <div class="flex min-w-0 flex-wrap items-center gap-2">
                <h3
                    class="text-sm font-semibold break-words text-slate-900 transition-colors group-hover:text-indigo-600 dark:text-gray-100 dark:group-hover:text-indigo-400"
                >
                    {{ subject.name }}
                </h3>

                <!-- Course Badge (HSC / SSC) -->
                <span
                    v-if="subject.course"
                    :class="[
                        subject.course.toUpperCase() === 'SSC'
                            ? 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/30'
                            : subject.course.toUpperCase() === 'HSC'
                              ? 'bg-indigo-50 text-indigo-700 ring-indigo-700/10 dark:bg-indigo-500/10 dark:text-indigo-400 dark:ring-indigo-500/30'
                              : 'bg-slate-100 text-slate-600 ring-slate-500/10 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-500/20',
                        'inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-bold uppercase ring-1 ring-inset',
                    ]"
                >
                    {{ subject.course }}
                </span>
            </div>
        </div>

        <!-- Right: Actions -->
        <div
            v-if="can('edit subjects') || can('delete subjects')"
            class="flex shrink-0 items-center gap-1"
            @click.stop
        >
            <button
                v-if="can('edit subjects')"
                type="button"
                @click="emit('edit', subject)"
                class="cursor-pointer rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-indigo-600 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-indigo-400"
                title="Edit Subject"
            >
                <Pencil class="h-4 w-4" :stroke-width="1.8" />
            </button>

            <button
                v-if="can('delete subjects')"
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
