<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Flame, Sparkles } from 'lucide-vue-next';
import { computed, ref } from 'vue';

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

// Filter tabs for leaf nodes (when resources exist)
type ResourceFilter = 'all' | 'note' | 'video' | 'practical' | 'other';
const activeFilter = ref<ResourceFilter>('all');

type SortOption = 'score' | 'newest';
const sortBy = ref<SortOption>('score');

const notesCount = computed(
    () => props.resources.filter((r) => r.resource_type === 'note').length,
);
const videosCount = computed(
    () => props.resources.filter((r) => r.resource_type === 'video').length,
);
const practicalsCount = computed(
    () => props.resources.filter((r) => r.resource_type === 'practical').length,
);
const othersCount = computed(
    () => props.resources.filter((r) => r.resource_type === 'other').length,
);

const filteredResources = computed(() => {
    let list = props.resources;

    if (activeFilter.value !== 'all') {
        list = list.filter((r) => r.resource_type === activeFilter.value);
    }

    return [...list].sort((a, b) => {
        if (sortBy.value === 'score') {
            const scoreDiff = (b.vote_score || 0) - (a.vote_score || 0);

            if (scoreDiff !== 0) {
                return scoreDiff;
            }

            return (b.id || 0) - (a.id || 0);
        } else {
            // Newest first
            return (b.id || 0) - (a.id || 0);
        }
    });
});
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
        <header class="mb-5">
            <BreadcrumbNav
                :breadcrumb="crumbs"
                :subject="subject"
                :current-node="currentNode"
                :exclude-last="true"
                class="mb-3"
            />

            <div class="flex items-center justify-between">
                <h1
                    class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl dark:text-gray-100"
                >
                    {{ currentTitle }}
                </h1>

                <span
                    v-if="nodes.length > 0"
                    class="text-xs font-semibold text-slate-400 sm:text-sm dark:text-gray-500"
                >
                    {{ nodes.length }} টি অধ্যায়
                </span>
            </div>

            <!-- In-Page Filter Pills & Sort Controls (Visible on Leaf Nodes with Resources) -->
            <div
                v-if="resources.length > 0 && nodes.length === 0"
                class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-3 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800"
            >
                <!-- Category Filter Pills -->
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        @click="activeFilter = 'all'"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold transition active:scale-95"
                        :class="
                            activeFilter === 'all'
                                ? 'bg-slate-900 text-white shadow-xs dark:bg-white dark:text-gray-900'
                                : 'border border-slate-200/80 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800'
                        "
                    >
                        <span>সব</span>
                        <span
                            class="py-0.2 rounded-md px-1.5 text-[10px]"
                            :class="
                                activeFilter === 'all'
                                    ? 'bg-white/20 text-white dark:bg-black/20 dark:text-gray-900'
                                    : 'bg-slate-100 text-slate-500 dark:bg-gray-800 dark:text-gray-400'
                            "
                        >
                            {{ resources.length }}
                        </span>
                    </button>

                    <button
                        v-if="notesCount > 0"
                        type="button"
                        @click="activeFilter = 'note'"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold transition active:scale-95"
                        :class="
                            activeFilter === 'note'
                                ? 'bg-amber-600 text-white shadow-xs dark:bg-amber-500'
                                : 'border border-slate-200/80 bg-white text-slate-600 hover:border-amber-200 hover:bg-amber-50/50 hover:text-amber-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800'
                        "
                    >
                        <span>📝 নোট</span>
                        <span
                            class="py-0.2 rounded-md px-1.5 text-[10px]"
                            :class="
                                activeFilter === 'note'
                                    ? 'bg-white/20 text-white'
                                    : 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300'
                            "
                        >
                            {{ notesCount }}
                        </span>
                    </button>

                    <button
                        v-if="videosCount > 0"
                        type="button"
                        @click="activeFilter = 'video'"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold transition active:scale-95"
                        :class="
                            activeFilter === 'video'
                                ? 'bg-rose-600 text-white shadow-xs dark:bg-rose-500'
                                : 'border border-slate-200/80 bg-white text-slate-600 hover:border-rose-200 hover:bg-rose-50/50 hover:text-rose-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800'
                        "
                    >
                        <span>🎥 ক্লাস</span>
                        <span
                            class="py-0.2 rounded-md px-1.5 text-[10px]"
                            :class="
                                activeFilter === 'video'
                                    ? 'bg-white/20 text-white'
                                    : 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300'
                            "
                        >
                            {{ videosCount }}
                        </span>
                    </button>

                    <button
                        v-if="practicalsCount > 0"
                        type="button"
                        @click="activeFilter = 'practical'"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold transition active:scale-95"
                        :class="
                            activeFilter === 'practical'
                                ? 'bg-teal-600 text-white shadow-xs dark:bg-teal-500'
                                : 'border border-slate-200/80 bg-white text-slate-600 hover:border-teal-200 hover:bg-teal-50/50 hover:text-teal-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800'
                        "
                    >
                        <span>🧪 ব্যবহারিক</span>
                        <span
                            class="py-0.2 rounded-md px-1.5 text-[10px]"
                            :class="
                                activeFilter === 'practical'
                                    ? 'bg-white/20 text-white'
                                    : 'bg-teal-50 text-teal-700 dark:bg-teal-950/40 dark:text-teal-300'
                            "
                        >
                            {{ practicalsCount }}
                        </span>
                    </button>

                    <button
                        v-if="othersCount > 0"
                        type="button"
                        @click="activeFilter = 'other'"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold transition active:scale-95"
                        :class="
                            activeFilter === 'other'
                                ? 'bg-slate-700 text-white shadow-xs dark:bg-gray-700'
                                : 'border border-slate-200/80 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800'
                        "
                    >
                        <span>📄 অন্যান্য</span>
                        <span
                            class="py-0.2 rounded-md px-1.5 text-[10px]"
                            :class="
                                activeFilter === 'other'
                                    ? 'bg-white/20 text-white'
                                    : 'bg-slate-100 text-slate-600 dark:bg-gray-800 dark:text-gray-300'
                            "
                        >
                            {{ othersCount }}
                        </span>
                    </button>
                </div>

                <!-- Sort Filter Controls (Score vs Newest) -->
                <div
                    class="flex items-center gap-1 self-start rounded-xl border border-slate-200/80 bg-slate-50/70 p-1 sm:self-auto dark:border-gray-800 dark:bg-gray-900/60"
                >
                    <button
                        type="button"
                        @click="sortBy = 'score'"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-bold transition active:scale-95"
                        :class="
                            sortBy === 'score'
                                ? 'bg-white text-indigo-600 shadow-xs dark:bg-gray-800 dark:text-indigo-400'
                                : 'text-slate-500 hover:text-slate-900 dark:text-gray-400 dark:hover:text-gray-200'
                        "
                        title="সর্বোচ্চ ভোট স্কোর অনুযায়ী সাজান"
                    >
                        <Flame class="h-3.5 w-3.5" />
                        <span>ভোট স্কোর</span>
                    </button>

                    <button
                        type="button"
                        @click="sortBy = 'newest'"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-bold transition active:scale-95"
                        :class="
                            sortBy === 'newest'
                                ? 'bg-white text-indigo-600 shadow-xs dark:bg-gray-800 dark:text-indigo-400'
                                : 'text-slate-500 hover:text-slate-900 dark:text-gray-400 dark:hover:text-gray-200'
                        "
                        title="নতুন আপলোড অনুযায়ী সাজান"
                    >
                        <Sparkles class="h-3.5 w-3.5" />
                        <span>নতুন</span>
                    </button>
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
                <template v-else-if="filteredResources.length > 0">
                    <ResourceRow
                        v-for="resource in filteredResources"
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
