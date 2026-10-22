<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

import AuthModal from '@/components/AuthModal.vue';
import BreadcrumbNav from '@/components/BreadcrumbNav.vue';
import EmptyState from '@/components/EmptyState.vue';
import NodeRow from '@/components/NodeRow.vue';
import ResourceRow from '@/components/ResourceRow.vue';
import UploadResourceDropdown from '@/components/resources/UploadResourceDropdown.vue';
import { useAuth } from '@/lib/useAuth';

const props = defineProps({
    subject: {
        type: Object,
        default: () => ({}),
    },
    currentNode: {
        type: Object,
        default: null,
    },
    nodes: {
        type: Array as () => any[],
        default: () => [],
    },
    breadcrumb: {
        type: Array as () => any[],
        default: () => [],
    },
    resources: {
        type: Array as () => any[],
        default: () => [],
    },
});

const { showAuthModal, authModalMessage } = useAuth();

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

    <div class="mx-auto max-w-5xl px-3 pt-6 pb-24 sm:px-6">
        <header class="mb-8">
            <BreadcrumbNav
                :breadcrumb="crumbs"
                :subject="subject"
                :current-node="currentNode"
                class="mb-4"
            />

            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1
                        class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl dark:text-gray-100"
                    >
                        {{ currentTitle }}
                    </h1>
                    <p
                        class="mt-1.5 text-sm font-semibold text-slate-400 dark:text-gray-500"
                    >
                        <span v-if="crumbs.length">{{ parentTitle }} · </span>
                        {{ totalItemsCount }} Items Total
                    </p>
                </div>
            </div>
        </header>

        <div
            class="flex flex-1 flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
        >
            <div
                class="flex items-center justify-between border-b border-slate-100 bg-slate-50 px-5 py-2.5 text-xs font-bold tracking-wider text-slate-400 uppercase sm:px-6 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-500"
            >
                <span>Name</span>
                <UploadResourceDropdown
                    v-if="currentNode && nodes.length === 0"
                    :node="currentNode as any"
                    :is-frozen="Boolean(currentNode.is_frozen)"
                />
            </div>

            <div class="flex-1 divide-y divide-slate-100 dark:divide-gray-800">
                <template v-if="nodes.length > 0">
                    <NodeRow
                        v-for="node in nodes"
                        :key="`node-${node.id}`"
                        :node="node"
                    />
                </template>
                <template v-else-if="resources.length > 0">
                    <ResourceRow
                        v-for="resource in resources"
                        :key="`resource-${resource.id}`"
                        :resource="resource"
                    />
                </template>
                <EmptyState
                    v-else
                    title="কোনো রিসোর্স পাওয়া যায়নি"
                    description="শীঘ্রই এখানে নতুন স্টাডি ম্যাটেরিয়াল ও নোট আপলোড করা হবে।"
                />
            </div>
        </div>
    </div>

    <!-- Sign-in Dialog Modal for Guest Users -->
    <AuthModal v-model="showAuthModal" :message="authModalMessage" />
</template>
