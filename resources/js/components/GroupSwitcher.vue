<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

withDefaults(
    defineProps<{
        currentGroup?: string;
    }>(),
    {
        currentGroup: 'science',
    },
);

const page = usePage();
const isSsc = computed(() => page.url.startsWith('/ssc'));

const groupTabs = [
    { key: 'science', label: 'বিজ্ঞান' },
    { key: 'humanities', label: 'মানবিক' },
    { key: 'commerce', label: 'ব্যবসায় শিক্ষা' },
];

const getGroupUrl = (groupKey: string) => {
    const base = isSsc.value ? '/ssc' : '/';

    return groupKey === 'science' ? base : `${base}?group=${groupKey}`;
};
</script>

<template>
    <div class="mb-6 flex flex-wrap items-center gap-1.5">
        <span
            class="mr-1 text-xs font-semibold text-slate-500 dark:text-gray-400"
        >
            বিভাগ:
        </span>
        <Link
            v-for="tab in groupTabs"
            :key="tab.key"
            :href="getGroupUrl(tab.key)"
            preserve-scroll
            class="cursor-pointer rounded-xl px-3.5 py-1.5 text-xs transition select-none"
            :class="[
                currentGroup === tab.key
                    ? 'bg-indigo-600 font-bold text-white shadow-2xs'
                    : 'border border-slate-200/80 bg-white font-medium text-slate-600 hover:bg-slate-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700',
            ]"
        >
            {{ tab.label }}
        </Link>
    </div>
</template>
