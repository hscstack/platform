<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowRight,
    BookOpen,
    Check,
    Clock,
    Flame,
    Loader2,
    Plus,
    RotateCcw,
    Search,
    SlidersHorizontal,
    User as UserIcon,
    Users,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import AdUnit from '@/components/AdUnit.vue';
import BaseModal from '@/components/BaseModal.vue';
import BlogFeedCard from '@/components/blogs/BlogFeedCard.vue';
import EmptyState from '@/components/EmptyState.vue';
import ForumFeedCard from '@/components/forum/ForumFeedCard.vue';
import NoticeDialog from '@/components/NoticeDialog.vue';
import PwaInstallPrompt from '@/components/PwaInstallPrompt.vue';
import ResourceFeedCard from '@/components/resources/ResourceFeedCard.vue';
import { useAuth } from '@/lib/useAuth';

interface Subject {
    id: number;
    name: string;
    slug: string;
    course: 'hsc' | 'ssc';
    group?: string;
}

const props = defineProps<{
    feed: {
        data: any[];
        has_more?: boolean;
        page?: number;
    };
    subjects: Subject[];
    course: 'hsc' | 'ssc';
    group?: 'science' | 'commerce' | 'humanities' | null;
    sort: 'trending' | 'recent';
    notice?: any;
}>();

const { user } = useAuth();

const currentCourse = ref<'hsc' | 'ssc'>(props.course || 'hsc');
const currentGroup = ref<'science' | 'commerce' | 'humanities'>(
    props.group || 'science',
);
const currentSort = ref<'trending' | 'recent'>(props.sort || 'trending');

// Modal State
const showFilterModal = ref(false);
const draftCourse = ref<'hsc' | 'ssc'>(currentCourse.value);
const draftGroup = ref<'science' | 'commerce' | 'humanities'>(
    currentGroup.value,
);

const activeFilterCount = computed(() => {
    let count = 0;

    if (currentGroup.value !== 'science') {
        count++;
    }

    if (currentCourse.value === 'ssc') {
        count++;
    }

    return count;
});

const openFilterModal = () => {
    draftCourse.value = currentCourse.value;
    draftGroup.value = currentGroup.value;
    showFilterModal.value = true;
};

const applyFilters = () => {
    currentCourse.value = draftCourse.value;
    currentGroup.value = draftGroup.value;
    showFilterModal.value = false;

    router.get(
        '/feed',
        {
            course: currentCourse.value,
            group: currentGroup.value,
            sort: currentSort.value,
        },
        { preserveState: true, replace: true },
    );
};

const resetModalFilters = () => {
    draftCourse.value = 'hsc';
    draftGroup.value = 'science';
};

// Peer Search State & Handlers
const sidebarPeerSearch = ref(user.value?.institution || '');

watch(
    () => user.value?.institution,
    (inst) => {
        if (inst && !sidebarPeerSearch.value) {
            sidebarPeerSearch.value = inst;
        }
    },
);

const goToPeersSearch = (query?: string) => {
    const trimmed = (query ?? '').trim();

    if (trimmed) {
        router.get('/peers', { search: trimmed });
    } else {
        router.get('/peers');
    }
};

const handleMobilePeerClick = () => {
    goToPeersSearch(user.value?.institution || '');
};

const handleSidebarPeerSearch = () => {
    goToPeersSearch(sidebarPeerSearch.value);
};

// Feed state & progressive loading
const items = ref<any[]>([...(props.feed?.data || [])]);
const hasMore = ref(Boolean(props.feed?.has_more));
const currentPage = ref(props.feed?.page || 1);
const isLoadingMore = ref(false);

watch(
    () => props.feed,
    (newFeed) => {
        items.value = [...(newFeed?.data || [])];
        hasMore.value = Boolean(newFeed?.has_more);
        currentPage.value = newFeed?.page || 1;
    },
);

const switchSort = (newSort: 'trending' | 'recent') => {
    if (currentSort.value === newSort) {
        return;
    }

    currentSort.value = newSort;
    router.get(
        '/feed',
        { course: currentCourse.value, sort: newSort },
        { preserveState: true, replace: true },
    );
};

const loadMore = async () => {
    if (isLoadingMore.value || !hasMore.value) {
        return;
    }

    isLoadingMore.value = true;
    const nextPage = currentPage.value + 1;

    try {
        const groupParam = currentGroup.value
            ? `&group=${currentGroup.value}`
            : '';
        const res = await fetch(
            `/api/feed?course=${currentCourse.value}&sort=${currentSort.value}${groupParam}&page=${nextPage}`,
            {
                headers: {
                    Accept: 'application/json',
                },
            },
        );

        if (res.ok) {
            const result = await res.json();

            if (result.data && result.data.length > 0) {
                items.value.push(...result.data);
                currentPage.value = nextPage;
                hasMore.value = Boolean(result.has_more);
            } else {
                hasMore.value = false;
            }
        }
    } catch {
        // preserve current state on network failure
    } finally {
        isLoadingMore.value = false;
    }
};
</script>

<template>
    <Head title="Feed — HSCStack" />

    <NoticeDialog v-if="notice" :notice="notice" />

    <main class="mx-auto max-w-7xl px-3 pt-4 pb-24 sm:px-6 sm:pt-6">
        <PwaInstallPrompt variant="banner" class="mb-4" />

        <!-- 2-Column Responsive Layout (Feed Stream + Right Sidebar) -->
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start">
            <!-- Left Column: Feed Stream -->
            <div class="min-w-0 flex-1">
                <!-- Top Quick-Compose Card (from screenshot) -->
                <div
                    class="mb-4 rounded-2xl border border-slate-200/80 bg-white p-3.5 shadow-2xs sm:p-4 dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="flex items-center gap-3">
                        <!-- User Avatar -->
                        <div class="shrink-0">
                            <img
                                v-if="user?.image_url || user?.image_path"
                                :src="
                                    user.image_url ||
                                    '/storage/' + user.image_path
                                "
                                :alt="user.name"
                                class="dark:ring-gray-750 h-9 w-9 rounded-full object-cover ring-1 ring-slate-200"
                            />
                            <div
                                v-else
                                class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-500 dark:bg-gray-800 dark:text-gray-400"
                            >
                                <UserIcon class="h-4 w-4" />
                            </div>
                        </div>

                        <!-- Prompt trigger input linking to forum question ask page -->
                        <Link
                            href="/forum/ask"
                            class="group dark:bg-gray-850 flex flex-1 items-center justify-between rounded-xl border border-slate-200/90 bg-slate-50/70 px-4 py-2.5 text-xs text-slate-500 transition hover:border-slate-300 hover:bg-slate-100 sm:text-sm dark:border-gray-800 dark:text-gray-400 dark:hover:border-gray-700 dark:hover:bg-gray-800"
                        >
                            <span class="truncate">
                                কী পড়ছেন আজ? হ্যান্ডনোট, বইয়ের লিংক শেয়ার
                                করুন...
                            </span>
                            <div
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-white transition group-hover:bg-indigo-700"
                            >
                                <Plus class="h-3.5 w-3.5" />
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- Mobile Quick Peer Search Pill (Visible on phones only) -->
                <div class="mb-4 block lg:hidden">
                    <button
                        type="button"
                        @click="handleMobilePeerClick"
                        class="group dark:hover:bg-gray-850 flex w-full cursor-pointer items-center justify-between rounded-2xl border border-slate-200/80 bg-white px-3.5 py-2.5 shadow-2xs transition hover:border-slate-300 hover:bg-slate-50 active:scale-95 dark:border-gray-800 dark:bg-gray-900"
                    >
                        <div
                            class="flex min-w-0 items-center gap-2.5 text-xs text-slate-500 dark:text-gray-400"
                        >
                            <div
                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                            >
                                <Search class="h-3.5 w-3.5" />
                            </div>
                            <span class="truncate font-medium">
                                সহপাঠী খুঁজুন<template v-if="user?.institution">
                                    ({{ user.institution }})</template
                                >
                            </span>
                        </div>
                        <span
                            class="shrink-0 rounded-lg bg-indigo-50 px-2 py-1 text-[11px] font-bold text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                        >
                            খুঁজুন
                        </span>
                    </button>
                </div>

                <!-- Feed Controls: Sort (Trending/Recent) on Left, Course (HSC/SSC) on Right -->
                <div
                    class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200/80 bg-white p-2.5 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
                >
                    <!-- Sort Toggle: Trending / Recent (Left) -->
                    <div
                        class="flex items-center gap-1 rounded-xl bg-slate-100/90 p-1 dark:bg-gray-800"
                    >
                        <button
                            type="button"
                            @click="switchSort('trending')"
                            class="flex cursor-pointer items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition"
                            :class="[
                                currentSort === 'trending'
                                    ? 'bg-white text-rose-600 shadow-2xs dark:bg-gray-900 dark:text-rose-400'
                                    : 'text-slate-600 hover:text-slate-900 dark:text-gray-400 dark:hover:text-gray-200',
                            ]"
                        >
                            <Flame class="h-3.5 w-3.5" />
                            <span>Trending</span>
                        </button>
                        <button
                            type="button"
                            @click="switchSort('recent')"
                            class="flex cursor-pointer items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition"
                            :class="[
                                currentSort === 'recent'
                                    ? 'bg-white text-indigo-600 shadow-2xs dark:bg-gray-900 dark:text-indigo-400'
                                    : 'text-slate-600 hover:text-slate-900 dark:text-gray-400 dark:hover:text-gray-200',
                            ]"
                        >
                            <Clock class="h-3.5 w-3.5" />
                            <span>Recent</span>
                        </button>
                    </div>

                    <!-- Filter Trigger Button (Right) -->
                    <button
                        type="button"
                        @click="openFilterModal"
                        class="inline-flex shrink-0 cursor-pointer items-center justify-center gap-1.5 rounded-xl border border-slate-200/80 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-2xs transition hover:border-slate-300 hover:bg-slate-50 active:scale-95 sm:px-3.5 sm:py-2 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
                        title="Filter"
                    >
                        <SlidersHorizontal
                            class="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400"
                        />
                        <span>Filter</span>
                        <span
                            v-if="activeFilterCount > 0"
                            class="flex h-4 w-4 items-center justify-center rounded-full bg-indigo-600 text-[9px] font-extrabold text-white"
                        >
                            {{ activeFilterCount }}
                        </span>
                    </button>
                </div>

                <!-- Feed Stream -->
                <div v-if="items && items.length > 0" class="space-y-4">
                    <template
                        v-for="(item, index) in items"
                        :key="`${item.feed_type}-${item.id}`"
                    >
                        <ForumFeedCard
                            v-if="item.feed_type === 'forum_post'"
                            :post="item"
                        />
                        <ResourceFeedCard
                            v-else-if="item.feed_type === 'resource'"
                            :resource="item"
                        />
                        <BlogFeedCard
                            v-else-if="item.feed_type === 'blog'"
                            :blog="item"
                        />

                        <!-- In-Feed Native Sponsored Ad (Injected once every 12 items) -->
                        <AdUnit
                            v-if="(index + 1) % 12 === 0"
                            variant="in-feed"
                            title="Your Ad Goes Here — Sponsor HSCStack"
                            subtitle="Reach thousands of students. Study & education promotions only — strictly verified and student-safe."
                            cta-text="Promote with Us"
                        />
                    </template>

                    <!-- Load More Button -->
                    <div class="pt-4 text-center">
                        <button
                            v-if="hasMore"
                            type="button"
                            @click="loadMore"
                            :disabled="isLoadingMore"
                            class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-6 py-2.5 text-xs font-bold text-slate-700 shadow-2xs transition hover:border-slate-300 hover:bg-slate-50 active:scale-95 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
                        >
                            <Loader2
                                v-if="isLoadingMore"
                                class="h-4 w-4 animate-spin text-indigo-600 dark:text-indigo-400"
                            />
                            <span>{{
                                isLoadingMore ? 'Loading more...' : 'Load More'
                            }}</span>
                        </button>
                        <p
                            v-else
                            class="text-xs font-medium text-slate-400 dark:text-gray-500"
                        >
                            🎉 You've reached the end of the feed!
                        </p>
                    </div>
                </div>

                <!-- Empty State -->
                <EmptyState
                    v-else
                    title="No feed items found"
                    description="Be the first to share a note or ask a question!"
                />
            </div>

            <!-- Right Column: Sidebar (on PC stays on the right, width 360px) -->
            <aside
                class="w-full space-y-4 lg:sticky lg:top-4 lg:w-[360px] lg:shrink-0"
            >
                <!-- Option 1: Sidebar Peer Search Widget (Hidden on mobile, visible on desktop) -->
                <div
                    class="hidden rounded-2xl border border-slate-200/80 bg-white p-4.5 shadow-2xs lg:block dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="flex items-center gap-2">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                        >
                            <Users class="h-4 w-4" />
                        </div>
                        <h3
                            class="text-sm font-bold text-slate-900 dark:text-gray-100"
                        >
                            সহপাঠী খুঁজুন
                        </h3>
                    </div>

                    <p
                        class="mt-2 text-xs leading-relaxed text-slate-500 dark:text-gray-400"
                    >
                        নাম বা কলেজের নাম দিয়ে শিক্ষার্থীদের খুঁজুন:
                    </p>

                    <form
                        @submit.prevent="handleSidebarPeerSearch"
                        class="relative mt-3"
                    >
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 h-3.5 w-3.5 -translate-y-1/2 text-slate-400 dark:text-gray-500"
                        />
                        <input
                            v-model="sidebarPeerSearch"
                            type="text"
                            placeholder="নাম বা কলেজের নাম..."
                            class="dark:bg-gray-850 h-9 w-full rounded-xl border border-slate-200/90 bg-slate-50/70 pr-16 pl-8.5 text-xs text-slate-800 placeholder-slate-400 shadow-2xs transition focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-gray-800 dark:text-gray-100 dark:placeholder-gray-500 dark:focus:border-indigo-500"
                        />
                        <button
                            type="submit"
                            class="absolute top-1/2 right-1 -translate-y-1/2 cursor-pointer rounded-lg bg-indigo-600 px-2.5 py-1 text-[11px] font-bold text-white shadow-2xs transition hover:bg-indigo-700 active:scale-95"
                        >
                            খুঁজুন
                        </button>
                    </form>
                </div>

                <!-- Subject-wise Note Library Card -->
                <div
                    class="rounded-2xl border border-slate-200/80 bg-white p-4.5 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="mb-3 flex items-center justify-between">
                        <h3
                            class="text-sm font-bold text-slate-900 dark:text-gray-100"
                        >
                            বিষয়ভিত্তিক নোট লাইব্রেরি
                        </h3>
                        <span
                            class="text-xs font-bold text-slate-400 dark:text-gray-500"
                        >
                            শীঘ্রই আসছে
                        </span>
                    </div>

                    <p class="mb-3 text-xs text-slate-500 dark:text-gray-400">
                        নির্দিষ্ট বিষয়ের অধ্যায় ও সম্পূর্ণ বইয়ের পিডিএফ ফিল্টার
                        করুন:
                    </p>

                    <!-- Subjects Pills: clicks disabled / no-op as instructed -->
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="subj in subjects.slice(0, 12)"
                            :key="subj.id"
                            type="button"
                            class="cursor-default rounded-xl border border-slate-100 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-300"
                        >
                            {{ subj.name }}
                        </button>
                    </div>

                    <button
                        type="button"
                        class="mt-4 flex w-full cursor-default items-center justify-center gap-1.5 rounded-xl bg-slate-900/80 py-2.5 text-xs font-bold text-white dark:bg-gray-800 dark:text-gray-200"
                    >
                        <BookOpen class="h-3.5 w-3.5" />
                        <span>পুরো নোট লাইব্রেরি (শীঘ্রই আসছে)</span>
                    </button>
                </div>

                <!-- Study Tracker Callout Widget -->
                <div
                    class="relative overflow-hidden rounded-2xl border border-indigo-200/60 bg-gradient-to-br from-indigo-500 to-indigo-700 p-5 text-white shadow-xs"
                >
                    <div class="relative z-10">
                        <span
                            class="rounded-md bg-white/20 px-2 py-0.5 text-[10px] font-bold tracking-wider text-white uppercase backdrop-blur-xs"
                        >
                            স্টাডি ট্র্যাকার
                        </span>
                        <h4 class="mt-2 text-base font-bold">
                            আপনার পড়ার অগ্রগতি ট্র্যাক করুন
                        </h4>
                        <p
                            class="mt-1 text-xs leading-relaxed text-indigo-100/90"
                        >
                            কোন অধ্যায় পড়া শেষ, কোনটা বাকি—সবকিছু ট্র্যাক করে
                            নিজের লক্ষ্য পূরণ করুন।
                        </p>
                        <Link
                            href="/tracker"
                            class="mt-3.5 inline-flex items-center gap-1.5 rounded-xl bg-white px-3.5 py-2 text-xs font-bold text-indigo-700 shadow-2xs transition hover:bg-indigo-50"
                        >
                            <span>ট্র্যাকার ওপেন করুন</span>
                            <ArrowRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>
                </div>

                <!-- Optional Present AdUnit -->
                <AdUnit variant="sidebar" />
            </aside>
        </div>
    </main>

    <!-- Filter & Sort Modal -->
    <BaseModal
        :is-open="showFilterModal"
        max-width="md"
        position="center"
        @close="showFilterModal = false"
    >
        <template #header>
            <div class="flex items-center gap-2">
                <div
                    class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                >
                    <SlidersHorizontal class="h-4 w-4" />
                </div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-gray-100">
                    Filter Feed
                </h3>
            </div>
        </template>

        <!-- Modal Form Fields -->
        <div class="space-y-5 p-5">
            <!-- 1. Curriculum -->
            <div>
                <label
                    class="mb-2 block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-gray-300"
                >
                    Curriculum
                </label>
                <div class="grid grid-cols-2 gap-2.5">
                    <button
                        type="button"
                        @click="draftCourse = 'hsc'"
                        class="flex cursor-pointer items-center justify-between rounded-xl border px-4 py-2.5 text-xs font-bold transition"
                        :class="[
                            draftCourse === 'hsc'
                                ? 'border-indigo-600 bg-indigo-50/70 text-indigo-700 ring-1 ring-indigo-600/30 dark:border-indigo-500 dark:bg-indigo-950/50 dark:text-indigo-300'
                                : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-300 dark:hover:bg-gray-800',
                        ]"
                    >
                        <span>HSC</span>
                        <Check
                            v-if="draftCourse === 'hsc'"
                            class="h-4 w-4 text-indigo-600 dark:text-indigo-400"
                        />
                    </button>
                    <button
                        type="button"
                        @click="draftCourse = 'ssc'"
                        class="flex cursor-pointer items-center justify-between rounded-xl border px-4 py-2.5 text-xs font-bold transition"
                        :class="[
                            draftCourse === 'ssc'
                                ? 'border-indigo-600 bg-indigo-50/70 text-indigo-700 ring-1 ring-indigo-600/30 dark:border-indigo-500 dark:bg-indigo-950/50 dark:text-indigo-300'
                                : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-300 dark:hover:bg-gray-800',
                        ]"
                    >
                        <span>SSC</span>
                        <Check
                            v-if="draftCourse === 'ssc'"
                            class="h-4 w-4 text-indigo-600 dark:text-indigo-400"
                        />
                    </button>
                </div>
            </div>

            <!-- 2. Group (Science, Commerce, Humanities) -->
            <div>
                <label
                    class="mb-2 block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-gray-300"
                >
                    Group
                </label>
                <div class="grid grid-cols-3 gap-2">
                    <button
                        type="button"
                        @click="draftGroup = 'science'"
                        class="flex cursor-pointer items-center justify-center rounded-xl border py-2.5 text-xs font-bold transition"
                        :class="[
                            draftGroup === 'science'
                                ? 'border-indigo-600 bg-indigo-600 text-white'
                                : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-300 dark:hover:bg-gray-800',
                        ]"
                    >
                        Science
                    </button>
                    <button
                        type="button"
                        @click="draftGroup = 'commerce'"
                        class="flex cursor-pointer items-center justify-center rounded-xl border py-2.5 text-xs font-bold transition"
                        :class="[
                            draftGroup === 'commerce'
                                ? 'border-indigo-600 bg-indigo-600 text-white'
                                : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-300 dark:hover:bg-gray-800',
                        ]"
                    >
                        Commerce
                    </button>
                    <button
                        type="button"
                        @click="draftGroup = 'humanities'"
                        class="flex cursor-pointer items-center justify-center rounded-xl border py-2.5 text-xs font-bold transition"
                        :class="[
                            draftGroup === 'humanities'
                                ? 'border-indigo-600 bg-indigo-600 text-white'
                                : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-300 dark:hover:bg-gray-800',
                        ]"
                    >
                        Humanities
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Actions Footer -->
        <template #footer>
            <div class="flex items-center justify-between">
                <button
                    type="button"
                    @click="resetModalFilters"
                    class="inline-flex cursor-pointer items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-200"
                >
                    <RotateCcw class="h-3.5 w-3.5" />
                    <span>Reset</span>
                </button>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="showFilterModal = false"
                        class="cursor-pointer rounded-xl border border-slate-200 px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="applyFilters"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-indigo-700"
                    >
                        <Check class="h-3.5 w-3.5" />
                        <span>Apply Filters</span>
                    </button>
                </div>
            </div>
        </template>
    </BaseModal>
</template>
