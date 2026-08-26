<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';

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

const { subject } = defineProps({
    subject: Object,
});

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
        class="group relative flex cursor-pointer flex-col gap-3 rounded-xl border border-slate-200/90 bg-white p-3.5 transition-all duration-150 hover:border-indigo-300 hover:bg-slate-50/60 hover:shadow-xs sm:flex-row sm:items-center sm:justify-between sm:p-4 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-indigo-500/40 dark:hover:bg-gray-800/50"
    >
        <!-- Left: Icon + Subject Details (Full title, no ellipsis) -->
        <div class="flex items-center gap-3.5 min-w-0">
            <div
                :class="[
                    subject.tailwind_format ||
                        'bg-slate-100 text-slate-600 dark:bg-gray-800 dark:text-gray-400',
                    'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-black/5 shadow-2xs transition-transform duration-200 group-hover:scale-105 sm:h-11 sm:w-11 dark:border-white/10',
                ]"
            >
                <component
                    :is="icons[subject.icon] || BookOpen"
                    class="h-5 w-5 stroke-[2.2]"
                />
            </div>

            <div class="flex flex-col min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h3
                        class="text-sm font-bold text-slate-800 break-words transition-colors group-hover:text-indigo-600 dark:text-gray-200 dark:group-hover:text-indigo-400"
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
                            'inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-extrabold tracking-wider uppercase ring-1 ring-inset',
                        ]"
                    >
                        {{ subject.course }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Right: Actions -->
        <div
            class="flex items-center gap-2 self-end sm:self-center"
            @click.stop
        >
            <Link
                :href="`/admin/subjects/edit/${subject.id}`"
                class="inline-flex h-7 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-600 shadow-2xs transition-colors hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-indigo-500/30 dark:hover:bg-indigo-500/10 dark:hover:text-indigo-400"
                title="Edit Subject"
            >
                <Pencil class="h-3 w-3" :stroke-width="2" />
                <span>Edit</span>
            </Link>

            <button
                type="button"
                @click="handleDelete"
                class="inline-flex h-7 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-rose-600 shadow-2xs transition-colors hover:border-red-200 hover:bg-red-50 hover:text-red-700 dark:border-gray-700 dark:bg-gray-800 dark:text-rose-400 dark:hover:border-red-500/30 dark:hover:bg-red-500/10"
                title="Delete Subject"
            >
                <Trash2 class="h-3 w-3" :stroke-width="2" />
                <span>Delete</span>
            </button>
        </div>
    </div>
</template>
