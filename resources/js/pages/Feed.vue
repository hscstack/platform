<script setup lang="ts">
import { Head, Link, router, useRemember } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowRight,
    BookOpen,
    Check,
    ChevronDown,
    Clock,
    FileText,
    Flame,
    GraduationCap,
    HelpCircle,
    Loader2,
    Plus,
    Search,
    User as UserIcon,
    Users,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

import AdUnit from '@/components/AdUnit.vue';
import BaseModal from '@/components/BaseModal.vue';
import BlogFeedCard from '@/components/blogs/BlogFeedCard.vue';
import EmptyState from '@/components/EmptyState.vue';
import AskQuestionModal from '@/components/forum/AskQuestionModal.vue';
import ForumFeedCard from '@/components/forum/ForumFeedCard.vue';
import NoticeDialog from '@/components/NoticeDialog.vue';
import PwaInstallPrompt from '@/components/PwaInstallPrompt.vue';
import ResourceFeedCard from '@/components/resources/ResourceFeedCard.vue';
import ResourceFormModal from '@/components/resources/ResourceFormModal.vue';
import { useAuth } from '@/lib/useAuth';

interface SubjectNode {
    id: number;
    name: string;
    slug: string;
}

interface Subject {
    id: number;
    name: string;
    slug: string;
    course: 'hsc' | 'ssc';
    group?: string;
    nodes?: SubjectNode[];
}

const props = withDefaults(
    defineProps<{
        feed: {
            data: any[];
            has_more?: boolean;
            page?: number;
        };
        subjects: Subject[];
        course: 'hsc' | 'ssc';
        group?: 'science' | 'commerce' | 'humanities';
        sort: 'trending' | 'recent';
        notice?: any;
        allow_resource_uploads?: boolean;
    }>(),
    {
        allow_resource_uploads: true,
    },
);

const { user, requireAuth } = useAuth();

// Intermediate Action Modal State (Ask Question vs Upload Resource)
const isActionChoiceModalOpen = ref(false);
const isResourceUploadModalOpen = ref(false);
const isAskQuestionModalOpen = ref(false);

const handleComposeClick = () => {
    requireAuth('পোস্ট করতে অনুগ্রহ করে লগইন করুন।', () => {
        isActionChoiceModalOpen.value = true;
    });
};

const chooseAskQuestion = () => {
    isActionChoiceModalOpen.value = false;
    isAskQuestionModalOpen.value = true;
};

const isUploadDisabledModalOpen = ref(false);

const chooseUploadResource = () => {
    isActionChoiceModalOpen.value = false;

    if (props.allow_resource_uploads === false) {
        isUploadDisabledModalOpen.value = true;

        return;
    }

    isResourceUploadModalOpen.value = true;
};

const currentCourse = ref<'hsc' | 'ssc'>(props.course || 'hsc');
const currentGroup = ref<'science' | 'commerce' | 'humanities'>(
    props.group || 'science',
);
const currentSort = ref<'trending' | 'recent'>(props.sort || 'trending');

// Track Selector State
const isTrackDropdownOpen = ref(false);
const trackDropdownRef = ref<HTMLElement | null>(null);

const groupOptions: Array<{
    key: 'science' | 'commerce' | 'humanities';
    label: string;
}> = [
    { key: 'science', label: 'বিজ্ঞান' },
    { key: 'commerce', label: 'ব্যবসায় শিক্ষা' },
    { key: 'humanities', label: 'মানবিক' },
];

const currentGroupLabel = computed(() => {
    const found = groupOptions.find((g) => g.key === currentGroup.value);

    return found ? found.label : 'বিজ্ঞান';
});

const selectTrack = (
    course: 'hsc' | 'ssc',
    group: 'science' | 'commerce' | 'humanities',
) => {
    isTrackDropdownOpen.value = false;

    if (currentCourse.value === course && currentGroup.value === group) {
        return;
    }

    currentCourse.value = course;
    currentGroup.value = group;

    router.get(
        '/',
        {
            course: currentCourse.value,
            group: currentGroup.value,
            sort: currentSort.value,
        },
        { preserveState: true, replace: true },
    );
};

const handleWindowClick = (e: MouseEvent) => {
    if (
        trackDropdownRef.value &&
        !trackDropdownRef.value.contains(e.target as Node)
    ) {
        isTrackDropdownOpen.value = false;
    }
};

// Feed Infinite Scroll Observer
const feedSentinelRef = ref<HTMLElement | null>(null);
let feedObserver: IntersectionObserver | null = null;

onMounted(() => {
    window.addEventListener('click', handleWindowClick);

    if (typeof window !== 'undefined' && 'IntersectionObserver' in window) {
        feedObserver = new IntersectionObserver(
            (entries) => {
                if (
                    entries[0]?.isIntersecting &&
                    hasMore.value &&
                    !isLoadingMore.value
                ) {
                    loadMore();
                }
            },
            { rootMargin: '350px' },
        );

        if (feedSentinelRef.value) {
            feedObserver.observe(feedSentinelRef.value);
        }
    }
});

watch(feedSentinelRef, (el) => {
    if (feedObserver && el) {
        feedObserver.disconnect();
        feedObserver.observe(el);
    }
});

onUnmounted(() => {
    window.removeEventListener('click', handleWindowClick);

    if (feedObserver) {
        feedObserver.disconnect();
        feedObserver = null;
    }
});

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

// Feed state & progressive loading (persisted automatically in history state on native Back)
const items = useRemember(
    ref<any[]>([...(props.feed?.data || [])]),
    'feed_items',
);
const hasMore = useRemember(
    ref(Boolean(props.feed?.has_more)),
    'feed_has_more',
);
const currentPage = useRemember(
    ref(props.feed?.page || 1),
    'feed_current_page',
);
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
        '/',
        {
            course: currentCourse.value,
            sort: newSort,
            group: currentGroup.value,
        },
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
        const res = await fetch(
            `/api/feed?course=${currentCourse.value}&sort=${currentSort.value}&group=${currentGroup.value}&page=${nextPage}`,
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
    <Head title="Feed" />

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
                                class="h-9 w-9 rounded-full object-cover ring-1 ring-slate-200 dark:ring-gray-700"
                            />
                            <div
                                v-else
                                class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-500 dark:bg-gray-800 dark:text-gray-400"
                            >
                                <UserIcon class="h-4 w-4" />
                            </div>
                        </div>

                        <!-- Prompt trigger input opening intermediate choice modal -->
                        <button
                            type="button"
                            @click="handleComposeClick"
                            class="group flex flex-1 cursor-pointer items-center justify-between rounded-xl border border-slate-200/90 bg-slate-50/70 px-4 py-2.5 text-xs text-slate-500 transition hover:border-slate-300 hover:bg-slate-100 sm:text-sm dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-400 dark:hover:border-gray-700 dark:hover:bg-gray-800"
                        >
                            <span class="truncate">
                                কী পড়ছেন আজ? প্রশ্ন করুন বা নোট শেয়ার করুন...
                            </span>
                            <div
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-white transition group-hover:bg-indigo-700"
                            >
                                <Plus class="h-3.5 w-3.5" />
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Mobile Quick Peer Search Pill (Visible on phones only) -->
                <div class="mb-4 block lg:hidden">
                    <button
                        type="button"
                        @click="handleMobilePeerClick"
                        class="group flex w-full cursor-pointer items-center justify-between rounded-2xl border border-slate-200/80 bg-white px-3.5 py-2.5 shadow-2xs transition hover:border-slate-300 hover:bg-slate-50 active:scale-95 dark:border-gray-800 dark:bg-gray-900 dark:hover:bg-gray-800/80"
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

                    <!-- Track Selector Dropdown (Right) -->
                    <div class="relative" ref="trackDropdownRef">
                        <button
                            type="button"
                            @click="isTrackDropdownOpen = !isTrackDropdownOpen"
                            class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-bold text-slate-700 shadow-2xs transition hover:border-slate-300 hover:bg-slate-50 active:scale-95 sm:py-2 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
                        >
                            <GraduationCap
                                class="h-4 w-4 text-indigo-600 dark:text-indigo-400"
                            />
                            <span
                                class="font-extrabold text-slate-900 uppercase dark:text-white"
                                >{{ currentCourse }}</span
                            >
                            <span class="text-slate-300 dark:text-gray-600"
                                >•</span
                            >
                            <span>{{ currentGroupLabel }}</span>
                            <ChevronDown
                                class="h-3.5 w-3.5 text-slate-400 transition-transform duration-200 dark:text-gray-500"
                                :class="{ 'rotate-180': isTrackDropdownOpen }"
                            />
                        </button>

                        <!-- Track Menu Popover -->
                        <div
                            v-if="isTrackDropdownOpen"
                            class="absolute top-full right-0 z-50 mt-1.5 w-56 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl dark:border-gray-800 dark:bg-gray-900"
                        >
                            <div
                                class="px-2 py-1 text-[10px] font-bold tracking-wider text-slate-400 uppercase dark:text-gray-500"
                            >
                                কোর্স ও বিভাগ পরিবর্তন করুন
                            </div>

                            <!-- HSC Options -->
                            <div class="mt-1 space-y-0.5">
                                <div
                                    class="px-2 pt-1 text-[10px] font-bold tracking-wider text-indigo-600 uppercase dark:text-indigo-400"
                                >
                                    এইচএসসি (HSC)
                                </div>
                                <button
                                    v-for="grp in groupOptions"
                                    :key="`hsc-${grp.key}`"
                                    type="button"
                                    @click="selectTrack('hsc', grp.key)"
                                    class="flex w-full cursor-pointer items-center justify-between rounded-xl px-2.5 py-1.5 text-xs font-semibold transition"
                                    :class="[
                                        currentCourse === 'hsc' &&
                                        currentGroup === grp.key
                                            ? 'bg-indigo-50 font-bold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300'
                                            : 'text-slate-700 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-gray-800',
                                    ]"
                                >
                                    <span>{{ grp.label }}</span>
                                    <Check
                                        v-if="
                                            currentCourse === 'hsc' &&
                                            currentGroup === grp.key
                                        "
                                        class="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400"
                                    />
                                </button>
                            </div>

                            <!-- SSC Options -->
                            <div
                                class="mt-2 space-y-0.5 border-t border-slate-100 pt-1.5 dark:border-gray-800"
                            >
                                <div
                                    class="px-2 pt-1 text-[10px] font-bold tracking-wider text-indigo-600 uppercase dark:text-indigo-400"
                                >
                                    এসএসসি (SSC)
                                </div>
                                <button
                                    v-for="grp in groupOptions"
                                    :key="`ssc-${grp.key}`"
                                    type="button"
                                    @click="selectTrack('ssc', grp.key)"
                                    class="flex w-full cursor-pointer items-center justify-between rounded-xl px-2.5 py-1.5 text-xs font-semibold transition"
                                    :class="[
                                        currentCourse === 'ssc' &&
                                        currentGroup === grp.key
                                            ? 'bg-indigo-50 font-bold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300'
                                            : 'text-slate-700 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-gray-800',
                                    ]"
                                >
                                    <span>{{ grp.label }}</span>
                                    <Check
                                        v-if="
                                            currentCourse === 'ssc' &&
                                            currentGroup === grp.key
                                        "
                                        class="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400"
                                    />
                                </button>
                            </div>
                        </div>
                    </div>
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

                    <!-- Infinite Scroll Sentinel & Loader -->
                    <div class="pt-4 text-center">
                        <div
                            v-if="hasMore"
                            ref="feedSentinelRef"
                            class="flex justify-center py-4"
                        >
                            <Loader2
                                v-if="isLoadingMore"
                                class="h-5 w-5 animate-spin text-indigo-600 dark:text-indigo-400"
                            />
                        </div>
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

            <!-- Right Column: Sidebar (Desktop only) -->
            <aside
                class="hidden w-full space-y-4 lg:sticky lg:top-4 lg:block lg:w-[360px] lg:shrink-0"
            >
                <!-- Option 1: Sidebar Peer Search Widget -->
                <div
                    class="rounded-2xl border border-slate-200/80 bg-white p-4.5 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
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
                            class="h-9 w-full rounded-xl border border-slate-200/90 bg-slate-50/70 pr-16 pl-8.5 text-xs text-slate-800 placeholder-slate-400 shadow-2xs transition focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-100 dark:placeholder-gray-500 dark:focus:border-indigo-500 dark:focus:bg-gray-800"
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
                            বিষয়ভিত্তিক নোট আর্কাইভ
                        </h3>
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

                    <Link
                        href="/notes"
                        class="mt-4 flex w-full cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-slate-900/80 py-2.5 text-xs font-bold text-white transition hover:bg-slate-900 active:scale-95 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                    >
                        <BookOpen class="h-3.5 w-3.5" />
                        <span>পুরো নোট আর্কাইভ</span>
                    </Link>
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

                <!-- Desktop Sidebar Mini Footer -->
                <div
                    class="px-2 pt-1 text-[11px] leading-relaxed text-slate-400 dark:text-gray-500"
                >
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                        <Link href="/privacy-policy" class="hover:underline"
                            >গোপনীয়তা</Link
                        >
                        <span>·</span>
                        <Link href="/terms-service" class="hover:underline"
                            >নীতিমালা</Link
                        >
                        <span>·</span>
                        <Link href="/content-policy" class="hover:underline"
                            >কনটেন্ট পলিসি</Link
                        >
                        <span>·</span>
                        <a
                            href="https://github.com/hscstack"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="hover:underline"
                            >GitHub</a
                        >
                        <span>·</span>
                        <a
                            href="https://facebook.com/hscstackbd"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="hover:underline"
                            >Facebook</a
                        >
                    </div>
                    <p
                        class="mt-2 text-[10px] text-slate-400/80 dark:text-gray-500/80"
                    >
                        © {{ new Date().getFullYear() }} HSCStack. সর্বস্বত্ব
                        সংরক্ষিত।
                    </p>
                </div>
            </aside>
        </div>
    </main>

    <!-- Intermediate Choice Modal: Ask Question vs Upload Resource -->
    <BaseModal
        :is-open="isActionChoiceModalOpen"
        title="নতুন পোস্ট করুন"
        description="আপনি কী শেয়ার করতে চান নির্বাচন করুন"
        max-width="md"
        @close="isActionChoiceModalOpen = false"
    >
        <div class="space-y-3 p-5 sm:p-6">
            <!-- Option 1: Ask Question (Forum) -->
            <button
                type="button"
                @click="chooseAskQuestion"
                class="group flex w-full cursor-pointer items-center gap-3.5 rounded-2xl border border-slate-200/90 bg-white p-4 text-left shadow-2xs transition hover:border-indigo-300 hover:bg-indigo-50/40 active:scale-98 dark:border-gray-800 dark:bg-gray-800/60 dark:hover:border-indigo-800 dark:hover:bg-indigo-950/40"
            >
                <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 transition group-hover:bg-amber-100 dark:bg-amber-950/60 dark:text-amber-400"
                >
                    <HelpCircle class="h-5 w-5 stroke-[2.2]" />
                </div>
                <div class="min-w-0 flex-1">
                    <h4
                        class="text-sm font-bold text-slate-900 group-hover:text-indigo-600 dark:text-gray-100 dark:group-hover:text-indigo-400"
                    >
                        প্রশ্ন করুন (ফোরাম)
                    </h4>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                        পড়াশোনার কোনো বিষয় না বুঝলে প্রশ্ন করে সহপাঠীদের সাহায্য
                        নিন
                    </p>
                </div>
                <ArrowRight
                    class="h-4 w-4 shrink-0 text-slate-400 transition group-hover:translate-x-0.5 group-hover:text-indigo-600 dark:text-gray-500 dark:group-hover:text-indigo-400"
                />
            </button>

            <!-- Option 2: Upload Resource (Notes, Book, Video, Practical) -->
            <button
                type="button"
                @click="chooseUploadResource"
                class="group flex w-full cursor-pointer items-center gap-3.5 rounded-2xl border border-slate-200/90 bg-white p-4 text-left shadow-2xs transition hover:border-indigo-300 hover:bg-indigo-50/40 active:scale-98 dark:border-gray-800 dark:bg-gray-800/60 dark:hover:border-indigo-800 dark:hover:bg-indigo-950/40"
            >
                <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 transition group-hover:bg-indigo-100 dark:bg-indigo-950/60 dark:text-indigo-400"
                >
                    <FileText class="h-5 w-5 stroke-[2.2]" />
                </div>
                <div class="min-w-0 flex-1">
                    <h4
                        class="text-sm font-bold text-slate-900 group-hover:text-indigo-600 dark:text-gray-100 dark:group-hover:text-indigo-400"
                    >
                        রিসোর্স শেয়ার করুন
                    </h4>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                        হ্যান্ডনোট, দাগানো বই, ভিডিও ক্লাস বা ব্যবহারিক আপলোড
                        করুন
                    </p>
                </div>
                <ArrowRight
                    class="h-4 w-4 shrink-0 text-slate-400 transition group-hover:translate-x-0.5 group-hover:text-indigo-600 dark:text-gray-500 dark:group-hover:text-indigo-400"
                />
            </button>
        </div>
    </BaseModal>

    <!-- Upload Disabled Notice Modal -->
    <BaseModal
        :is-open="isUploadDisabledModalOpen"
        title="নোট আপলোড স্থগিত"
        max-width="md"
        @close="isUploadDisabledModalOpen = false"
    >
        <div class="space-y-4 p-5 text-center">
            <div
                class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400"
            >
                <AlertCircle class="h-6 w-6 stroke-[2]" />
            </div>
            <p class="text-sm font-semibold text-slate-800 dark:text-gray-100">
                প্ল্যাটফর্ম রক্ষণাবেক্ষণ ও আপগ্রেডের সুবিধার্থে নতুন রিসোর্স
                আপলোড সাময়িকভাবে স্থগিত রাখা হয়েছে।
            </p>
            <p
                class="text-xs leading-relaxed text-slate-500 dark:text-gray-400"
            >
                খুব দ্রুতই পুনরায় নোট আপলোড চালু করা হবে। এই সময়ে আপনি যেকোনো
                বিদ্যমান নোট ও আর্কাইভ ব্রাউজ ও পড়তে পারেন।
            </p>
            <div class="pt-2">
                <button
                    type="button"
                    @click="isUploadDisabledModalOpen = false"
                    class="w-full cursor-pointer rounded-xl bg-slate-900 py-2.5 text-xs font-bold text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-gray-100"
                >
                    বুঝেছি
                </button>
            </div>
        </div>
    </BaseModal>

    <!-- Upload Resource Modal (Dedicated Note/Resource Archive modal with subjects & chapters) -->
    <ResourceFormModal
        :is-open="isResourceUploadModalOpen"
        :subjects="subjects"
        @close="isResourceUploadModalOpen = false"
    />

    <!-- Ask Question Modal (Modal replacement for /forum/ask) -->
    <AskQuestionModal
        v-model:is-open="isAskQuestionModalOpen"
        :subjects="subjects"
        :initial-curriculum="currentCourse"
        @close="isAskQuestionModalOpen = false"
    />
</template>
