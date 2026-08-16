<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { kCard } from 'konsta/vue';
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
</script>

<template>
    <k-card outline>
        <Link
            :href="subject.slug"
            class="group flex touch-manipulation items-center gap-4 transition-all duration-200 active:scale-[0.99]"
        >
            <div
                :class="[
                    subject.tailwind_format,
                    'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl transition-colors duration-200',
                ]"
            >
                <component
                    :is="icons[subject.icon]"
                    class="h-5 w-5 stroke-[2.2]"
                />
            </div>

            <div class="min-w-0 flex-1">
                <h3
                    class="truncate text-base font-bold text-slate-800 transition-colors group-hover:text-indigo-600 dark:text-gray-200 dark:group-hover:text-indigo-400"
                >
                    {{ subject.name }}
                </h3>
                <p
                    class="mt-0.5 text-xs font-semibold text-slate-400 dark:text-gray-500"
                >
                    {{ subject.nodes_count }} Chapters
                </p>
            </div>

            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 shrink-0 text-slate-300 transition-all duration-200 group-hover:translate-x-0.5 group-hover:text-indigo-500 dark:text-gray-600 dark:group-hover:text-indigo-400"
                viewBox="0 0 20 20"
                fill="currentColor"
            >
                <path
                    fill-rule="evenodd"
                    d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                    clip-rule="evenodd"
                />
            </svg>
        </Link>
    </k-card>
</template>
