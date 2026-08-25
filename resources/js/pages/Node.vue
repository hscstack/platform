<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

import BreadcrumbNav from '@/components/BreadcrumbNav.vue';
import EmptyState from '@/components/EmptyState.vue';
import NodeRow from '@/components/NodeRow.vue';
import ResourceRow from '@/components/ResourceRow.vue';

const props = defineProps({
    subject: Object,
    nodes: Array,
    breadcrumb: Array,
    resources: Array,
});

const crumbs = computed(() => props.breadcrumb ?? []);
const currentTitle = computed(
    () => crumbs.value.at(-1)?.name ?? props.subject?.name,
);
const parentTitle = computed(
    () => crumbs.value.at(-2)?.name ?? props.subject?.name,
);
const totalItemsCount = computed(
    () => (props.nodes?.length ?? 0) + (props.resources?.length ?? 0),
);
</script>

<template>
    <Head>
        <title>{{ currentTitle }}</title>
        <meta
            name="description"
            :content="`Study materials, chapter breakdown, and lecture notes for ${currentTitle} - HSCStack.`"
        />
        <meta property="og:title" :content="`${currentTitle} - HSCStack`" />
        <meta
            property="og:description"
            :content="`Study materials, chapter breakdown, and lecture notes for ${currentTitle} - HSCStack.`"
        />
    </Head>

    <div
        class="mx-auto flex min-h-[calc(100vh-10rem)] w-full max-w-4xl flex-col px-4 py-8 font-sans text-slate-700 selection:bg-indigo-50 sm:px-6 md:py-12 dark:text-gray-300 dark:selection:bg-indigo-500/30"
    >
        <BreadcrumbNav :subject="subject" :breadcrumb="breadcrumb" />

        <header class="mb-8">
            <h1
                class="text-3xl font-black tracking-tight text-slate-950 sm:text-4xl dark:text-gray-100"
            >
                {{ currentTitle }}
            </h1>
            <p
                class="mt-1.5 text-sm font-semibold text-slate-400 dark:text-gray-500"
            >
                <span v-if="crumbs.length">{{ parentTitle }} · </span>
                {{ totalItemsCount }} Items Total
            </p>
        </header>

        <div
            class="flex flex-1 flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
        >
            <div
                class="flex items-center justify-between border-b border-slate-100 bg-slate-50 px-5 py-3.5 text-xs font-bold tracking-wider text-slate-400 uppercase sm:px-6 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-500"
            >
                <span>Name</span>
                <span class="hidden sm:inline">Type</span>
            </div>

            <div class="flex-1 divide-y divide-slate-100 dark:divide-gray-800">
                <template v-if="totalItemsCount > 0">
                    <NodeRow
                        v-for="node in nodes"
                        :key="`node-${node.id}`"
                        :node="node"
                    />
                    <ResourceRow
                        v-for="resource in resources"
                        :key="`resource-${resource.id}`"
                        :resource="resource"
                    />
                </template>
                <EmptyState v-else />
            </div>
        </div>
    </div>
</template>
