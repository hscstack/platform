<script setup lang="ts">
import { kList, kListItem } from 'konsta/vue';
import { Folder } from 'lucide-vue-next';
import { computed } from 'vue';

const { node } = defineProps({
    node: Object,
});
const href = computed(() => {
    const path = new URL(window.location.href).pathname.replace(/\/$/, '');

    return `${path}/${node.slug}`;
});
</script>

<template>
    <kList>
        <kListItem :link="true" :href="href">
            <template #media>
                <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-slate-200/40 bg-slate-100 text-slate-500 transition-colors duration-200 group-hover:border-indigo-100 group-hover:bg-indigo-50 group-hover:text-indigo-600 dark:border-gray-700/40 dark:bg-gray-800 dark:text-gray-400 dark:group-hover:border-indigo-500/30 dark:group-hover:bg-indigo-500/10 dark:group-hover:text-indigo-400"
                >
                    <Folder class="h-5 w-5 stroke-[2.2]" />
                </div>
            </template>

            <template #title>
                <span
                    class="block truncate text-base font-bold text-slate-900 transition-colors group-hover:text-indigo-600 dark:text-gray-100 dark:group-hover:text-indigo-400"
                >
                    {{ node.name }}
                </span>
            </template>

            <template #subtitle>
                <span
                    class="mt-0.5 inline-block text-xs font-semibold text-slate-400 sm:hidden dark:text-gray-500"
                >
                    {{ node.children_count + node.resources_count || 0 }}
                    Materials
                </span>
            </template>

            <template #after>
                <div class="flex shrink-0 items-center gap-4 pl-3">
                    <span
                        class="hidden rounded-md border border-slate-200/60 bg-slate-50 px-2.5 py-1 text-xs font-bold text-slate-500 transition-colors group-hover:border-indigo-100/80 group-hover:bg-indigo-50/60 group-hover:text-indigo-600 sm:inline-block dark:border-gray-700/60 dark:bg-gray-800 dark:text-gray-400 dark:group-hover:border-indigo-500/30 dark:group-hover:bg-indigo-500/10 dark:group-hover:text-indigo-400"
                    >
                        {{ node.children_count + node.resources_count || 0 }}
                        Materials
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
            </template>
        </kListItem>
    </kList>
</template>

<style scoped></style>
