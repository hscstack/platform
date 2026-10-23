<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import SubjectIcon from '@/components/SubjectIcon.vue';

const { subject } = defineProps({
    subject: Object,
});
</script>

<template>
    <Link
        :href="subject.slug"
        class="group relative flex touch-manipulation flex-col items-start gap-2.5 overflow-hidden rounded-xl border border-slate-200 bg-white p-3.5 transition-all duration-200 hover:border-indigo-300 hover:shadow-sm active:scale-[0.99] sm:flex-row sm:items-center sm:justify-between sm:gap-4 sm:px-5 sm:py-4.5 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-indigo-500/50 dark:hover:shadow-indigo-500/5"
    >
        <div
            class="flex w-full min-w-0 flex-col items-start gap-2.5 sm:w-auto sm:flex-row sm:items-center sm:gap-4"
        >
            <!-- Balanced Icon Wrapper -->
            <div
                :class="[
                    subject.tailwind_format,
                    'flex h-9 w-9 shrink-0 items-center justify-center rounded-xl transition-colors duration-200 sm:h-10 sm:w-10',
                ]"
            >
                <SubjectIcon
                    :name="subject.icon"
                    class-name="h-4.5 w-4.5 stroke-[2.2] sm:h-5 sm:w-5"
                />
            </div>

            <!-- Content Area -->
            <div class="w-full min-w-0">
                <h3
                    class="line-clamp-2 text-[15px] leading-snug font-bold text-slate-800 transition-colors group-hover:text-indigo-600 sm:truncate sm:text-base sm:leading-normal dark:text-gray-200 dark:group-hover:text-indigo-400"
                >
                    {{ subject.name }}
                </h3>
                <p
                    class="mt-1 text-[11px] font-semibold text-slate-400 sm:mt-0.5 sm:text-xs dark:text-gray-500"
                >
                    {{ subject.nodes_count }} Chapters
                </p>
            </div>
        </div>

        <!-- Clean, Modern Chevron Indicator -->
        <div class="hidden items-center pl-4 sm:flex">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 text-slate-300 transition-all duration-200 group-hover:translate-x-0.5 group-hover:text-indigo-500 dark:text-gray-600 dark:group-hover:text-indigo-400"
                viewBox="0 0 20 20"
                fill="currentColor"
            >
                <path
                    fill-rule="evenodd"
                    d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                    clip-rule="evenodd"
                />
            </svg>
        </div>
    </Link>
</template>
