<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ArrowBigDown, ArrowBigUp, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import AuthModal from '@/components/AuthModal.vue';
import BreadcrumbNav from '@/components/BreadcrumbNav.vue';
import EmptyState from '@/components/EmptyState.vue';
import NodeRow from '@/components/NodeRow.vue';
import ResourceRow from '@/components/ResourceRow.vue';
import UserListItem from '@/components/UserListItem.vue';
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
    upvotesCount: {
        type: Number,
        default: 0,
    },
    downvotesCount: {
        type: Number,
        default: 0,
    },
    userVote: {
        type: String as () => 'up' | 'down' | null,
        default: null,
    },
    upvoters: {
        type: Array as () => any[],
        default: () => [],
    },
});

const {
    user: currentUser,
    requireAuth,
    showAuthModal,
    authModalMessage,
} = useAuth();

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

// Modals
const showUpvotersModal = ref(false);

// Optimistic Vote State
const localUserVote = ref(props.userVote);
const localUpvotesCount = ref(props.upvotesCount);
const localDownvotesCount = ref(props.downvotesCount);
const localUpvoters = ref<any[]>([...(props.upvoters || [])]);
const isVoting = ref(false);

watch(
    () => props.userVote,
    (val) => {
        localUserVote.value = val;
    },
);

watch(
    () => props.upvotesCount,
    (val) => {
        localUpvotesCount.value = val;
    },
);

watch(
    () => props.downvotesCount,
    (val) => {
        localDownvotesCount.value = val;
    },
);

watch(
    () => props.upvoters,
    (val) => {
        localUpvoters.value = [...(val || [])];
    },
);

const handleVote = (type: 'up' | 'down') => {
    if (!requireAuth('Please sign in to vote on folders.')) {
        return;
    }

    if (isVoting.value || !props.currentNode?.id) {
        return;
    }

    const previousVote = localUserVote.value;

    if (previousVote === type) {
        // Toggle off
        localUserVote.value = null;

        if (type === 'up') {
            localUpvotesCount.value = Math.max(0, localUpvotesCount.value - 1);
            localUpvoters.value = localUpvoters.value.filter(
                (u: any) => u.id !== currentUser.value?.id,
            );
        } else {
            localDownvotesCount.value = Math.max(
                0,
                localDownvotesCount.value - 1,
            );
        }
    } else if (previousVote === null) {
        // New vote
        localUserVote.value = type;

        if (type === 'up') {
            localUpvotesCount.value += 1;

            if (currentUser.value) {
                localUpvoters.value = [
                    {
                        id: currentUser.value.id,
                        name: currentUser.value.name,
                        username: currentUser.value.username,
                        image_url: currentUser.value.image_url,
                        image_path: currentUser.value.image_path,
                        institution: currentUser.value.institution,
                        is_verified: currentUser.value.is_verified,
                    },
                    ...localUpvoters.value.filter(
                        (u: any) => u.id !== currentUser.value.id,
                    ),
                ];
            }
        } else {
            localDownvotesCount.value += 1;
        }
    } else {
        // Switching vote
        localUserVote.value = type;

        if (type === 'up') {
            localUpvotesCount.value += 1;
            localDownvotesCount.value = Math.max(
                0,
                localDownvotesCount.value - 1,
            );

            if (currentUser.value) {
                localUpvoters.value = [
                    {
                        id: currentUser.value.id,
                        name: currentUser.value.name,
                        username: currentUser.value.username,
                        image_url: currentUser.value.image_url,
                        image_path: currentUser.value.image_path,
                        institution: currentUser.value.institution,
                        is_verified: currentUser.value.is_verified,
                    },
                    ...localUpvoters.value.filter(
                        (u: any) => u.id !== currentUser.value.id,
                    ),
                ];
            }
        } else {
            localDownvotesCount.value += 1;
            localUpvotesCount.value = Math.max(0, localUpvotesCount.value - 1);
            localUpvoters.value = localUpvoters.value.filter(
                (u: any) => u.id !== currentUser.value?.id,
            );
        }
    }

    isVoting.value = true;
    router.post(
        `/nodes/${props.currentNode.id}/vote`,
        { type },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                isVoting.value = false;
            },
        },
    );
};
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
        class="mx-auto flex w-full max-w-4xl flex-col px-4 py-8 font-sans text-slate-700 selection:bg-indigo-50 sm:px-6 md:py-12 dark:text-gray-300 dark:selection:bg-indigo-500/30"
    >
        <BreadcrumbNav :subject="subject" :breadcrumb="breadcrumb" />

        <header class="mb-6">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
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
                </div>

                <!-- Folder Voting Interaction Bar (Visible inside folders) -->
                <div
                    v-if="currentNode"
                    class="flex flex-wrap items-center gap-3"
                >
                    <!-- Upvote & Downvote Button Group -->
                    <div
                        class="flex items-center rounded-2xl border border-slate-200/90 bg-white p-1 shadow-xs dark:border-gray-800 dark:bg-gray-900"
                    >
                        <!-- Upvote Button -->
                        <button
                            @click="handleVote('up')"
                            type="button"
                            class="group inline-flex cursor-pointer items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold transition-all duration-150 select-none active:scale-95"
                            :class="[
                                localUserVote === 'up'
                                    ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/70 dark:text-indigo-400'
                                    : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-indigo-400',
                            ]"
                            :title="
                                localUserVote === 'up'
                                    ? 'Upvoted (click to remove)'
                                    : 'Upvote this folder'
                            "
                        >
                            <ArrowBigUp
                                class="h-4.5 w-4.5 transition-transform group-hover:scale-110"
                                :class="[
                                    localUserVote === 'up'
                                        ? 'fill-indigo-600 text-indigo-600 dark:fill-indigo-400 dark:text-indigo-400'
                                        : 'stroke-[2]',
                                ]"
                            />
                            <span>Upvote</span>
                            <span
                                class="ml-0.5 rounded-lg px-1.5 py-0.5 text-[11px] font-bold"
                                :class="[
                                    localUserVote === 'up'
                                        ? 'bg-indigo-200/60 text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300'
                                        : 'bg-slate-100 text-slate-600 dark:bg-gray-800 dark:text-gray-400',
                                ]"
                            >
                                {{ localUpvotesCount }}
                            </span>
                        </button>

                        <div
                            class="mx-1 h-4 w-px bg-slate-200 dark:bg-gray-700"
                        ></div>

                        <!-- Downvote Button -->
                        <button
                            @click="handleVote('down')"
                            type="button"
                            class="group inline-flex cursor-pointer items-center gap-1.5 rounded-xl px-2.5 py-1.5 text-xs font-bold transition-all duration-150 select-none active:scale-95"
                            :class="[
                                localUserVote === 'down'
                                    ? 'bg-rose-50 text-rose-600 dark:bg-rose-950/70 dark:text-rose-400'
                                    : 'text-slate-600 hover:bg-slate-50 hover:text-rose-600 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-rose-400',
                            ]"
                            :title="
                                localUserVote === 'down'
                                    ? 'Downvoted (click to remove)'
                                    : 'Downvote this folder'
                            "
                        >
                            <ArrowBigDown
                                class="h-4.5 w-4.5 transition-transform group-hover:scale-110"
                                :class="[
                                    localUserVote === 'down'
                                        ? 'fill-rose-600 text-rose-600 dark:fill-rose-400 dark:text-rose-400'
                                        : 'stroke-[2]',
                                ]"
                            />
                            <span
                                v-if="localDownvotesCount > 0"
                                class="rounded-lg px-1.5 py-0.5 text-[11px] font-bold"
                                :class="[
                                    localUserVote === 'down'
                                        ? 'bg-rose-200/60 text-rose-700 dark:bg-rose-900/60 dark:text-rose-300'
                                        : 'bg-slate-100 text-slate-600 dark:bg-gray-800 dark:text-gray-400',
                                ]"
                            >
                                {{ localDownvotesCount }}
                            </span>
                        </button>
                    </div>

                    <!-- Upvoters Avatar Stack & Modal Trigger -->
                    <button
                        v-if="localUpvotesCount > 0"
                        type="button"
                        @click="showUpvotersModal = true"
                        class="group flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200/80 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-600 shadow-xs transition hover:border-slate-300 hover:bg-slate-50 active:scale-95 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800"
                        title="View users who upvoted"
                    >
                        <div
                            v-if="localUpvoters.length > 0"
                            class="flex -space-x-1.5 overflow-hidden"
                        >
                            <div
                                v-for="upvoter in localUpvoters.slice(0, 3)"
                                :key="upvoter.id"
                                class="inline-block h-5 w-5 overflow-hidden rounded-full ring-2 ring-white dark:ring-gray-900"
                            >
                                <img
                                    v-if="
                                        upvoter.image_url || upvoter.image_path
                                    "
                                    :src="
                                        upvoter.image_url ||
                                        '/storage/' + upvoter.image_path
                                    "
                                    :alt="upvoter.name"
                                    class="h-full w-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center bg-indigo-100 text-[9px] font-bold text-indigo-700 uppercase dark:bg-indigo-950 dark:text-indigo-300"
                                >
                                    {{ upvoter.name?.charAt(0) || 'U' }}
                                </div>
                            </div>
                        </div>

                        <span
                            class="font-medium text-slate-700 underline-offset-2 group-hover:underline dark:text-gray-300"
                        >
                            {{ localUpvotesCount }}
                            {{ localUpvotesCount === 1 ? 'upvote' : 'upvotes' }}
                        </span>
                    </button>
                </div>
            </div>
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
                <EmptyState
                    v-else
                    title="কোনো রিসোর্স পাওয়া যায়নি"
                    description="শীঘ্রই এখানে নতুন স্টাডি ম্যাটেরিয়াল ও নোট আপলোড করা হবে।"
                    :show-cta="true"
                />
            </div>
        </div>
    </div>

    <!-- Sign-in Dialog Modal for Guest Users -->
    <AuthModal v-model="showAuthModal" :message="authModalMessage" />

    <!-- Upvoters Modal (Shows list of upvoters; downvotes list is intentionally hidden) -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="showUpvotersModal"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-xs dark:bg-black/50"
                @click.self="showUpvotersModal = false"
            >
                <div
                    class="relative w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-gray-800 dark:bg-gray-900"
                >
                    <button
                        @click="showUpvotersModal = false"
                        class="absolute top-3.5 right-3.5 cursor-pointer rounded-lg p-1 text-slate-400 hover:text-slate-600 dark:text-gray-500 dark:hover:text-gray-300"
                    >
                        <X class="h-4 w-4" />
                    </button>

                    <div class="mb-4 flex items-center gap-2.5">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                        >
                            <ArrowBigUp
                                class="h-4.5 w-4.5 fill-indigo-600 text-indigo-600 dark:fill-indigo-400 dark:text-indigo-400"
                            />
                        </div>
                        <div>
                            <h3
                                class="text-sm font-bold text-slate-900 dark:text-gray-100"
                            >
                                Upvoted by
                            </h3>
                            <p
                                class="text-[11px] font-medium text-slate-500 dark:text-gray-400"
                            >
                                {{ localUpvotesCount }}
                                {{
                                    localUpvotesCount === 1
                                        ? 'person'
                                        : 'people'
                                }}
                                upvoted this folder
                            </p>
                        </div>
                    </div>

                    <div
                        class="-mx-1 max-h-72 divide-y divide-slate-100 overflow-y-auto px-1 dark:divide-gray-800/80"
                    >
                        <div
                            v-if="localUpvoters.length === 0"
                            class="py-6 text-center text-xs text-slate-500 dark:text-gray-400"
                        >
                            No upvotes yet.
                        </div>

                        <UserListItem
                            v-for="upvoter in localUpvoters"
                            :key="upvoter.id"
                            :user="upvoter"
                            theme="indigo"
                        />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
