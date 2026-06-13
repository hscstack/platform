<script setup>
import { Link } from '@inertiajs/vue3';

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
} from 'lucide-vue-next';

const { subject, admin } = defineProps({
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
</script>

<template>
    <div
        class="group relative flex flex-col justify-between rounded-xl border border-slate-200 bg-white p-4 transition-all duration-200 hover:border-indigo-200 hover:bg-slate-50/40 hover:shadow-sm"
    >
        <div 
            class="absolute right-2 top-2 z-10 opacity-0 transition-opacity duration-150 group-hover:opacity-100"
        >
            <Link
                :href="`/admin/subjects/${subject.slug}/edit`"
                class="inline-flex h-6 items-center gap-1 rounded-md border border-slate-200 bg-white px-2 py-0.5 text-[11px] font-medium text-slate-500 shadow-sm transition-colors hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-600"
                title="Edit Subject"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-3 w-3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                </svg>
                <span>Edit</span>
            </Link>
        </div>

        <Link
            :href="`/admin/subjects/${subject.slug}/nodes`"
            class="flex flex-col items-start text-left focus:outline-none"
        >
            <div
                :class="[
                    subject.tailwind_format || 'bg-slate-100 text-slate-600',
                    'mb-3.5 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-black/5 shadow-inner transition-transform duration-200 group-hover:scale-105',
                ]"
            >
                <component
                    :is="icons[subject.icon]"
                    class="h-5.5 w-5.5 stroke-[2.2]"
                />
            </div>

            <div class="w-full min-w-0">
                <h3
                    class="truncate text-sm font-bold text-slate-800 transition-colors group-hover:text-indigo-600"
                >
                    {{ subject.name }}
                </h3>
                
                <p class="mt-0.5 text-[11px] font-semibold text-slate-400">
                    {{ subject.nodes_count || 0 }} items
                </p>
            </div>
        </Link>
    </div>
</template>