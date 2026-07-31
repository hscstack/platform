<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Folder } from 'lucide-vue-next';

const { node } = defineProps({
    node: Object,
});
</script>

<template>
    <Link
        :href="`${$page.url}/${node.slug}`"
        class="group relative flex cursor-pointer touch-manipulation items-center justify-between bg-white px-5 py-4.5 transition-all duration-200 hover:bg-slate-50/40 active:scale-[0.995] sm:px-6 sm:active:scale-100"
    >
        <div class="flex min-w-0 items-center gap-4">
            <div
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-slate-200/40 bg-slate-100 text-slate-500 transition-colors duration-200 group-hover:border-indigo-100 group-hover:bg-indigo-50 group-hover:text-indigo-600"
            >
                <Folder class="h-5 w-5 stroke-[2.2]" />
            </div>

            <div class="min-w-0">
                <span
                    class="block truncate text-base font-bold text-slate-900 transition-colors group-hover:text-indigo-600"
                >
                    {{ node.name }}
                </span>
                <span
                    class="mt-0.5 inline-block text-xs font-semibold text-slate-400 sm:hidden"
                >
                    {{ node.children_count + node.resources_count || 0 }}
                    Materials
                </span>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-4 pl-3">
            <span
                class="hidden rounded-md border border-slate-200/60 bg-slate-50 px-2.5 py-1 text-xs font-bold text-slate-500 transition-colors group-hover:border-indigo-100/80 group-hover:bg-indigo-50/60 group-hover:text-indigo-600 sm:inline-block"
            >
                {{ node.children_count + node.resources_count || 0 }}
                Materials
            </span>

            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4 text-slate-400 transition-transform duration-200 group-hover:translate-x-1 group-hover:text-indigo-600"
                viewBox="0 0 20 20"
                fill="currentColor"
            >
                <path
                    fill-rule="evenodd"
                    d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z"
                    clip-rule="evenodd"
                />
            </svg>
        </div>
    </Link>
</template>

<style scoped></style>
