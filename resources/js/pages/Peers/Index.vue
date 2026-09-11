<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, X, Users, Heart, Loader2 } from 'lucide-vue-next';
import { onUnmounted, ref, watch } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import VerifiedBadge from '@/components/VerifiedBadge.vue';
import { useAuth } from '@/lib/useAuth';

interface Peer {
    id: number;
    name: string;
    username: string;
    image_url?: string | null;
    institution?: string | null;
    is_verified?: boolean;
    appreciations_received_count?: number;
    is_appreciated?: boolean;
}

interface Props {
    peers: {
        data: Peer[];
        next_page_url: string | null;
        prev_page_url: string | null;
        current_page: number;
        per_page?: number;
        from?: number | null;
        to?: number | null;
    };
    filters: {
        search?: string | null;
        sort?: string;
    };
}

const props = defineProps<Props>();

const peerList = ref<Peer[]>([...props.peers.data]);
const nextPageUrl = ref<string | null>(props.peers.next_page_url);
const isLoadingMore = ref(false);

const searchQuery = ref(props.filters.search || '');
const currentSort = ref(props.filters.sort || 'relevant');

const sortOptions = [
    { value: 'relevant', label: 'You May Know' },
    { value: 'appreciated', label: 'Most Appreciated' },
];

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const applyFilters = () => {
    router.get(
        '/peers',
        {
            search: searchQuery.value.trim() || undefined,
            sort:
                currentSort.value !== 'relevant'
                    ? currentSort.value
                    : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const handleSearchInput = () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 350);
};

const clearSearch = () => {
    searchQuery.value = '';
    applyFilters();
};

const setSort = (sortValue: string) => {
    currentSort.value = sortValue;
    applyFilters();
};

const loadMore = () => {
    if (!nextPageUrl.value || isLoadingMore.value) {
        return;
    }

    isLoadingMore.value = true;
    router.get(
        nextPageUrl.value,
        {},
        {
            preserveState: true,
            preserveScroll: true,
            preserveUrl: true,
            only: ['peers'],
            onSuccess: (page) => {
                const newPeersData =
                    (page.props.peers as Props['peers'])?.data || [];
                const existingIds = new Set(peerList.value.map((p) => p.id));
                const uniqueNew = newPeersData.filter(
                    (p) => !existingIds.has(p.id),
                );
                peerList.value.push(...uniqueNew);
                nextPageUrl.value =
                    (page.props.peers as Props['peers'])?.next_page_url || null;
            },
            onFinish: () => {
                isLoadingMore.value = false;
            },
        },
    );
};

onUnmounted(() => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
});

const { requireAuth } = useAuth();

const togglePeerAppreciation = (peer: Peer) => {
    requireAuth('Please sign in to appreciate members.', () => {
        const prevIsAppreciated = peer.is_appreciated;
        const prevCount = peer.appreciations_received_count;

        // Optimistic UI update
        if (peer.is_appreciated) {
            peer.is_appreciated = false;
            peer.appreciations_received_count = Math.max(
                0,
                (peer.appreciations_received_count || 1) - 1,
            );
        } else {
            peer.is_appreciated = true;
            peer.appreciations_received_count =
                (peer.appreciations_received_count || 0) + 1;
        }

        const rollback = () => {
            peer.is_appreciated = prevIsAppreciated;
            peer.appreciations_received_count = prevCount;
        };

        router.post(
            `/u/${peer.id}/appreciate`,
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onError: rollback,
                onCancel: rollback,
            },
        );
    });
};

watch(
    () => props.peers,
    (newPeers) => {
        if (!isLoadingMore.value) {
            peerList.value = [...newPeers.data];
            nextPageUrl.value = newPeers.next_page_url;
        }
    },
    { immediate: true },
);

watch(
    () => props.filters,
    (newFilters) => {
        searchQuery.value = newFilters.search || '';
        currentSort.value = newFilters.sort || 'relevant';
    },
);
</script>

<template>
    <Head>
        <title>Discover Peers - HSC Stack</title>
        <meta
            name="description"
            content="Discover and connect with fellow students, batchmates, and learners across colleges on HSC Stack."
        />
    </Head>

    <div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 sm:py-8">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                    >
                        <Users class="h-5 w-5" />
                    </div>
                    <div>
                        <h1
                            class="text-xl font-black tracking-tight text-slate-900 sm:text-2xl dark:text-gray-100"
                        >
                            Find Peers
                        </h1>
                        <p class="text-xs text-slate-500 dark:text-gray-400">
                            সহপাঠী ও অন্য প্রতিষ্ঠানের শিক্ষার্থীদের সাথে যুক্ত
                            হোন
                        </p>
                    </div>
                </div>
            </div>

            <!-- Search & Sort Controls Bar -->
            <div class="mt-4 flex flex-col gap-2.5 sm:flex-row sm:items-center">
                <!-- Search Input -->
                <div class="relative flex-1">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500"
                    />
                    <input
                        v-model="searchQuery"
                        @input="handleSearchInput"
                        @keyup.enter="applyFilters"
                        type="text"
                        placeholder="Search by name, @username, or college..."
                        class="h-10 w-full rounded-2xl border border-slate-200 bg-white pr-9 pl-10 text-xs font-medium text-slate-900 placeholder-slate-400 shadow-2xs transition focus:border-indigo-500 focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-500 dark:focus:border-indigo-500"
                    />
                    <button
                        v-if="searchQuery"
                        @click="clearSearch"
                        type="button"
                        class="absolute top-1/2 right-2.5 -translate-y-1/2 rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                        aria-label="Clear search"
                    >
                        <X class="h-3.5 w-3.5" />
                    </button>
                </div>

                <!-- Sort Pills -->
                <div class="flex items-center gap-1.5 self-start sm:self-auto">
                    <button
                        v-for="opt in sortOptions"
                        :key="opt.value"
                        @click="setSort(opt.value)"
                        type="button"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-semibold transition active:scale-95"
                        :class="[
                            currentSort === opt.value
                                ? 'bg-slate-900 text-white shadow-xs dark:bg-gray-100 dark:text-gray-900'
                                : 'border border-slate-200/80 bg-white text-slate-600 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800',
                        ]"
                    >
                        <span>{{ opt.label }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Social Rows List Container -->
        <div
            v-if="peerList.length > 0"
            class="divide-y divide-slate-100 overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-xs dark:divide-gray-800/80 dark:border-gray-800 dark:bg-gray-900"
        >
            <Link
                v-for="peer in peerList"
                :key="peer.id"
                :href="`/u/${peer.username}`"
                class="group flex items-center justify-between gap-3.5 p-3.5 transition hover:bg-slate-50/70 sm:p-4 dark:hover:bg-gray-800/40"
            >
                <!-- Left: Avatar + Details -->
                <div class="flex min-w-0 items-center gap-3.5">
                    <!-- Avatar -->
                    <div
                        class="h-11 w-11 shrink-0 overflow-hidden rounded-full ring-2 ring-slate-100 transition group-hover:ring-indigo-200 sm:h-12 sm:w-12 dark:ring-gray-800 dark:group-hover:ring-indigo-900/60"
                    >
                        <img
                            v-if="peer.image_url"
                            :src="peer.image_url"
                            :alt="peer.name"
                            class="h-full w-full object-cover"
                        />
                        <div
                            v-else
                            class="flex h-full w-full items-center justify-center bg-gradient-to-br from-indigo-500 to-indigo-700 text-base font-black text-white"
                        >
                            {{ peer.name.charAt(0).toUpperCase() }}
                        </div>
                    </div>

                    <!-- User Info -->
                    <div class="min-w-0 flex-1 space-y-0.5">
                        <!-- Name + Verified -->
                        <div class="flex items-center gap-1.5">
                            <span
                                class="truncate text-sm font-bold text-slate-900 group-hover:text-indigo-600 dark:text-gray-100 dark:group-hover:text-indigo-400"
                            >
                                {{ peer.name }}
                            </span>
                            <VerifiedBadge
                                v-if="peer.is_verified"
                                size="h-4 w-4"
                            />
                        </div>

                        <!-- School & Appreciators Row -->
                        <div
                            class="flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-gray-400"
                        >
                            <!-- Institution -->
                            <span
                                v-if="peer.institution"
                                class="max-w-[200px] truncate sm:max-w-xs"
                            >
                                {{ peer.institution }}
                            </span>

                            <span
                                v-if="peer.institution"
                                class="text-slate-300 select-none dark:text-gray-600"
                                >·</span
                            >

                            <!-- Appreciators Minimal Format -->
                            <span class="inline-flex items-center gap-1">
                                <span
                                    class="font-bold text-slate-800 dark:text-gray-200"
                                >
                                    {{ peer.appreciations_received_count || 0 }}
                                </span>
                                <span>
                                    {{
                                        peer.appreciations_received_count === 1
                                            ? 'Appreciator'
                                            : 'Appreciators'
                                    }}
                                </span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Right: Quick Appreciate Action -->
                <div class="shrink-0 pl-2">
                    <button
                        @click.prevent.stop="togglePeerAppreciation(peer)"
                        type="button"
                        class="group/btn inline-flex h-8 cursor-pointer items-center gap-1.5 rounded-xl px-2.5 text-xs font-bold transition-all duration-150 select-none active:scale-95 sm:px-3"
                        :class="[
                            peer.is_appreciated
                                ? 'border border-rose-200 bg-rose-50 text-rose-600 shadow-2xs dark:border-rose-900/60 dark:bg-rose-950/60 dark:text-rose-400'
                                : 'border border-slate-200 bg-white text-slate-700 shadow-2xs hover:border-rose-200 hover:bg-rose-50/40 hover:text-rose-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-rose-900/50 dark:hover:bg-rose-950/30 dark:hover:text-rose-400',
                        ]"
                        :title="
                            peer.is_appreciated
                                ? 'Appreciating (click to remove)'
                                : 'Appreciate this member'
                        "
                    >
                        <Heart
                            class="h-3.5 w-3.5 transition-transform group-hover/btn:scale-110"
                            :class="[
                                peer.is_appreciated
                                    ? 'fill-rose-500 text-rose-500 dark:fill-rose-400 dark:text-rose-400'
                                    : 'stroke-[2.2] text-slate-500 group-hover/btn:text-rose-500 dark:text-gray-400 dark:group-hover/btn:text-rose-400',
                            ]"
                        />
                        <span class="hidden sm:inline">{{
                            peer.is_appreciated ? 'Appreciating' : 'Appreciate'
                        }}</span>
                    </button>
                </div>
            </Link>
        </div>

        <!-- Empty State -->
        <EmptyState
            v-else
            :icon="Users"
            title="No peers found"
            :description="
                searchQuery
                    ? `No members found matching &quot;${searchQuery}&quot;. Try checking for typos or searching by college name.`
                    : 'There are no members listed right now.'
            "
        >
            <template #actions>
                <button
                    v-if="searchQuery"
                    @click="clearSearch"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-slate-800 active:scale-95 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200"
                >
                    <span>Clear Search</span>
                </button>
            </template>
        </EmptyState>

        <!-- Load More Button -->
        <div v-if="nextPageUrl" class="mt-6 flex justify-center">
            <button
                @click="loadMore"
                :disabled="isLoadingMore"
                type="button"
                class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-6 py-2.5 text-xs font-bold text-slate-700 shadow-2xs transition-all hover:border-slate-300 hover:bg-slate-50 active:scale-95 disabled:pointer-events-none disabled:opacity-60 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
            >
                <Loader2
                    v-if="isLoadingMore"
                    class="h-4 w-4 animate-spin text-indigo-600 dark:text-indigo-400"
                />
                <span>{{
                    isLoadingMore ? 'Loading more...' : 'Load More Peers'
                }}</span>
            </button>
        </div>
    </div>
</template>
