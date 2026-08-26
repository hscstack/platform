<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowBigUp, Folder } from 'lucide-vue-next';
import { computed } from 'vue';

const { node } = defineProps({
    node: {
        type: Object,
        required: true,
    },
});
const href = computed(() => {
    const path = new URL(window.location.href).pathname.replace(/\/$/, '');

    return `${path}/${node.slug}`;
});
</script>

<template>
    <Link
        :href="href"
        class="group relative flex cursor-pointer touch-manipulation items-center justify-between bg-white px-5 py-4.5 transition-all duration-200 hover:bg-slate-50/40 active:scale-[0.995] sm:px-6 sm:active:scale-100 dark:bg-gray-900 dark:hover:bg-gray-800/50"
    >
        <div class="flex min-w-0 items-center gap-4">
            <div
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-slate-200/40 bg-slate-100 text-slate-500 transition-colors duration-200 group-hover:border-indigo-100 group-hover:bg-indigo-50 group-hover:text-indigo-600 dark:border-gray-700/40 dark:bg-gray-800 dark:text-gray-400 dark:group-hover:border-indigo-500/30 dark:group-hover:bg-indigo-500/10 dark:group-hover:text-indigo-400"
            >
                <Folder class="h-5 w-5 stroke-[2.2]" />
            </div>

            <div class="min-w-0">
                <span
                    class="block truncate text-base font-bold text-slate-900 transition-colors group-hover:text-indigo-600 dark:text-gray-100 dark:group-hover:text-indigo-400"
                >
                    {{ node.name }}
                </span>
                <span
                    class="mt-0.5 flex items-center gap-1.5 text-xs font-semibold text-slate-400 sm:hidden dark:text-gray-500"
                >
                    <span>
                        {{
                            (node.children_count || 0) +
                            (node.resources_count || 0)
                        }}
                        Items
                    </span>
                    <span
                        v-if="node.upvotes_count"
                        class="inline-flex items-center gap-0.5 font-bold text-indigo-600 dark:text-indigo-400"
                    >
                        ·
                        <ArrowBigUp
                            class="h-3.5 w-3.5 fill-indigo-600 text-indigo-600 dark:fill-indigo-400 dark:text-indigo-400"
                        />
                        {{ node.upvotes_count }}
                    </span>
                </span>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-2.5 pl-3">
            <span
                v-if="node.upvotes_count"
                class="hidden items-center gap-0.5 rounded-md border border-indigo-100/80 bg-indigo-50/60 px-2 py-1 text-xs font-bold text-indigo-600 sm:inline-flex dark:border-indigo-900/40 dark:bg-indigo-950/40 dark:text-indigo-400"
                title="Folder Upvotes"
            >
                <ArrowBigUp
                    class="h-3.5 w-3.5 fill-indigo-600 text-indigo-600 dark:fill-indigo-400 dark:text-indigo-400"
                />
                {{ node.upvotes_count }}
            </span>

            <span
                class="hidden rounded-md border border-slate-200/60 bg-slate-50 px-2.5 py-1 text-xs font-bold text-slate-500 transition-colors group-hover:border-indigo-100/80 group-hover:bg-indigo-50/60 group-hover:text-indigo-600 sm:inline-block dark:border-gray-700/60 dark:bg-gray-800 dark:text-gray-400 dark:group-hover:border-indigo-500/30 dark:group-hover:bg-indigo-500/10 dark:group-hover:text-indigo-400"
            >
                {{ (node.children_count || 0) + (node.resources_count || 0) }}
                Items
            </span>

            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4 text-slate-400 transition-transform duration-200 group-hover:translate-x-1 group-hover:text-indigo-600 dark:text-gray-500 dark:group-hover:text-indigo-400"
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
