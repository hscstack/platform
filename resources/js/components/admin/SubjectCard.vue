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
        class="group relative flex cursor-pointer flex-col justify-between rounded-xl border border-slate-200 bg-white p-4 transition-all duration-200 hover:border-indigo-200 hover:bg-slate-50/40 hover:shadow-sm"
    >
        <div
            class="absolute top-2 right-2 z-10 flex gap-1 opacity-100 transition-opacity duration-150 md:opacity-0 md:group-hover:opacity-100"
            @click.stop
        >
            <Link
                :href="`/admin/subjects/edit/${subject.id}`"
                class="inline-flex h-6 items-center gap-1 rounded-md border border-slate-200 bg-white px-2 py-0.5 text-[11px] font-medium text-slate-500 shadow-sm transition-colors hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-600"
                title="Edit Subject"
            >
                <Pencil class="h-3 w-3" :stroke-width="2" />
                <span>Edit</span>
            </Link>

            <button
                type="button"
                @click="handleDelete"
                class="inline-flex h-6 items-center gap-1 rounded-md border border-slate-200 bg-white px-2 py-0.5 text-[11px] font-medium text-slate-500 shadow-sm transition-colors hover:border-red-200 hover:bg-red-50 hover:text-red-600"
                title="Delete Subject"
            >
                <Trash2 class="h-3 w-3" :stroke-width="2" />
                <span>Delete</span>
            </button>
        </div>

        <div class="flex flex-col items-start text-left focus:outline-none">
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
        </div>
    </div>
</template>
