<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    Book,
    Check,
    ChevronDown,
    FileText,
    FlaskConical,
    GraduationCap,
    Loader2,
    Plus,
    Search,
    Video,
    X,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

import BaseModal from '@/components/BaseModal.vue';

import EmptyState from '@/components/EmptyState.vue';
import ResourceFormModal from '@/components/resources/ResourceFormModal.vue';
import TrendingNoteCard from '@/components/resources/TrendingNoteCard.vue';
import { useAuth } from '@/lib/useAuth';

interface NodeItem {
    id: number;
    subject_id?: number;
    parent_id?: number | null;
    name: string;
    slug: string;
}

interface SubjectItem {
    id: number;
    name: string;
    course: string;
    slug: string;
    group?: string;
    nodes?: NodeItem[];
}

interface ResourceItem {
    id: number;
    title: string;
    resource_type: string;
    thumbnail?: string | null;
    items_count?: number;
    vote_score: number;
    created_at?: string;
    user?: any;
    subject?: SubjectItem | null;
    node?: NodeItem | null;
}

interface SimplePaginatedResources {
    data: ResourceItem[];
    current_page: number;
    per_page: number;
    next_page_url: string | null;
    prev_page_url: string | null;
    path: string;
}

const props = withDefaults(
    defineProps<{
        resources: SimplePaginatedResources;
        subjects: SubjectItem[];
        allow_resource_uploads?: boolean;
        filters: {
            curriculum?: 'hsc' | 'ssc';
            course?: 'hsc' | 'ssc';
            group?: string;
            subject_id: number | null;
            node_id: string | number | null;
            type: string;
            sort: 'trending' | 'top' | 'latest';
            search: string;
        };
    }>(),
    {
        allow_resource_uploads: true,
    },
);

const page = usePage();
const { requireAuth } = useAuth();

// Local Filter States
const currentCourse = ref<'hsc' | 'ssc'>(
    props.filters.curriculum || props.filters.course || 'hsc',
);
const currentGroup = ref<string>(props.filters.group || 'science');
const selectedSubjectId = ref<number | null>(props.filters.subject_id);
const selectedNodeId = ref<string | number | null>(props.filters.node_id);
const selectedType = ref<string>(props.filters.type || 'all');
const currentSort = ref<'trending' | 'top' | 'latest'>(
    props.filters.sort || 'trending',
);
const searchInput = ref<string>(props.filters.search || '');

// Local Resource List & Load More
const resourceList = ref<ResourceItem[]>(props.resources?.data || []);
const nextPageUrl = ref<string | null>(props.resources?.next_page_url || null);
const isLoadingMore = ref(false);

watch(
    () => props.resources,
    (newVal) => {
        resourceList.value = newVal?.data || [];
        nextPageUrl.value = newVal?.next_page_url || null;
    },
    { deep: true },
);

watch(
    () => [
        props.filters.subject_id,
        props.filters.node_id,
        props.filters.type,
        props.filters.sort,
        props.filters.search,
        props.filters.curriculum,
        props.filters.group,
    ],
    () => {
        currentCourse.value =
            props.filters.curriculum || props.filters.course || 'hsc';
        currentGroup.value = props.filters.group || 'science';
        selectedSubjectId.value = props.filters.subject_id;
        selectedNodeId.value = props.filters.node_id;
        selectedType.value = props.filters.type || 'all';
        currentSort.value = props.filters.sort || 'trending';
        searchInput.value = props.filters.search || '';
    },
);

const loadMore = async () => {
    if (!nextPageUrl.value || isLoadingMore.value) {
        return;
    }

    isLoadingMore.value = true;

    try {
        const headers: Record<string, string> = {
            'X-Inertia': 'true',
            'X-Inertia-Partial-Component': 'Notes/Index',
            'X-Inertia-Partial-Data': 'resources',
            'X-Requested-With': 'XMLHttpRequest',
        };

        if (page.version) {
            headers['X-Inertia-Version'] = String(page.version);
        }

        const res = await fetch(nextPageUrl.value, { headers });

        if (res.ok) {
            const data = await res.json();
            const newResources =
                (data?.props?.resources as SimplePaginatedResources)?.data ||
                [];
            const existingIds = new Set(resourceList.value.map((r) => r.id));
            const uniqueNew = newResources.filter(
                (r) => !existingIds.has(r.id),
            );
            resourceList.value = [...resourceList.value, ...uniqueNew];
            nextPageUrl.value =
                (data?.props?.resources as SimplePaginatedResources)
                    ?.next_page_url || null;
        }
    } catch (e) {
        console.error('Failed to load more notes:', e);
    } finally {
        isLoadingMore.value = false;
    }
};

// Upload modal state
const isUploadModalOpen = ref(false);
const isUploadDisabledModalOpen = ref(false);

const openUploadModal = () => {
    if (props.allow_resource_uploads === false) {
        isUploadDisabledModalOpen.value = true;

        return;
    }

    requireAuth('রিসোর্স শেয়ার করতে অনুগ্রহ করে লগইন করুন।', () => {
        isUploadModalOpen.value = true;
    });
};

// Available chapters based on selected subject
const availableChapters = computed(() => {
    if (!selectedSubjectId.value) {
        return [];
    }

    const subj = props.subjects.find((s) => s.id === selectedSubjectId.value);

    return subj?.nodes || [];
});

const activeFiltersCount = computed(() => {
    let count = 0;

    if (selectedSubjectId.value) {
        count++;
    }

    if (selectedNodeId.value) {
        count++;
    }

    if (selectedType.value !== 'all') {
        count++;
    }

    if (searchInput.value.trim()) {
        count++;
    }

    return count;
});

const applyFilters = () => {
    router.get(
        '/notes',
        {
            curriculum: currentCourse.value,
            group: currentGroup.value,
            subject_id: selectedSubjectId.value || undefined,
            node_id: selectedNodeId.value || undefined,
            type: selectedType.value !== 'all' ? selectedType.value : undefined,
            sort:
                currentSort.value !== 'trending'
                    ? currentSort.value
                    : undefined,
            search: searchInput.value.trim() || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const isTrackDropdownOpen = ref(false);
const trackDropdownRef = ref<HTMLElement | null>(null);

const currentGroupLabel = computed(() => {
    const found = groupTabs.find((g) => g.key === currentGroup.value);

    return found ? found.label : 'বিজ্ঞান';
});

const selectTrack = (course: 'hsc' | 'ssc', group: string) => {
    isTrackDropdownOpen.value = false;

    if (currentCourse.value === course && currentGroup.value === group) {
        return;
    }

    currentCourse.value = course;
    currentGroup.value = group;
    selectedSubjectId.value = null;
    selectedNodeId.value = null;
    applyFilters();
};

const handleWindowClick = (e: MouseEvent) => {
    if (
        trackDropdownRef.value &&
        !trackDropdownRef.value.contains(e.target as Node)
    ) {
        isTrackDropdownOpen.value = false;
    }
};

// Infinite Scroll Observer Sentinel
const sentinelRef = ref<HTMLElement | null>(null);
let observer: IntersectionObserver | null = null;

onMounted(() => {
    window.addEventListener('click', handleWindowClick);

    if (typeof window !== 'undefined' && 'IntersectionObserver' in window) {
        observer = new IntersectionObserver(
            (entries) => {
                if (
                    entries[0]?.isIntersecting &&
                    nextPageUrl.value &&
                    !isLoadingMore.value
                ) {
                    loadMore();
                }
            },
            { rootMargin: '300px' },
        );

        if (sentinelRef.value) {
            observer.observe(sentinelRef.value);
        }
    }
});

watch(sentinelRef, (el) => {
    if (observer && el) {
        observer.disconnect();
        observer.observe(el);
    }
});

onUnmounted(() => {
    window.removeEventListener('click', handleWindowClick);

    if (observer) {
        observer.disconnect();
        observer = null;
    }
});

const handleSubjectChange = () => {
    selectedNodeId.value = null;
    applyFilters();
};

const pillsContainerRef = ref<HTMLElement | null>(null);

const handleTypeChange = (type: string) => {
    selectedType.value = type;
    applyFilters();
};

const handleTypeClick = (type: string, event: MouseEvent) => {
    handleTypeChange(type);
    const target = event.currentTarget as HTMLElement | null;
    const container = pillsContainerRef.value;

    if (target && container) {
        const targetRect = target.getBoundingClientRect();
        const containerRect = container.getBoundingClientRect();
        const offset =
            targetRect.left +
            targetRect.width / 2 -
            (containerRect.left + containerRect.width / 2);
        container.scrollBy({
            left: offset,
            behavior: 'smooth',
        });
    }
};

const handleSortChange = (sort: 'trending' | 'top' | 'latest') => {
    currentSort.value = sort;
    applyFilters();
};

const handleSearchSubmit = () => {
    applyFilters();
};

const clearSearch = () => {
    searchInput.value = '';
    applyFilters();
};

const resetAllFilters = () => {
    selectedSubjectId.value = null;
    selectedNodeId.value = null;
    selectedType.value = 'all';
    searchInput.value = '';
    applyFilters();
};

const groupTabs = [
    { key: 'science', label: 'বিজ্ঞান' },
    { key: 'commerce', label: 'ব্যবসায় শিক্ষা' },
    { key: 'humanities', label: 'মানবিক' },
];

const resourceTypes = [
    { key: 'all', label: 'সব', icon: null },
    { key: 'note', label: 'হ্যান্ডনোট', icon: FileText },
    { key: 'lecture_sheet', label: 'লেকচার শিট', icon: FileText },
    { key: 'marked_book', label: 'দাগানো বই', icon: Book },
    { key: 'video', label: 'ক্লাস', icon: Video },
    { key: 'practical', label: 'ব্যবহারিক', icon: FlaskConical },
];
</script>

<template>
    <Head>
        <title>Note Archive</title>
        <meta
            name="description"
            content="এইচএসসি ও এসএসসি পরীক্ষার বিষয় ও অধ্যায়ভিত্তিক হ্যান্ডনোট, বই ও লেকচার কালেকশন"
        />
    </Head>

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <!-- 1. Header Banner -->
        <div
            class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1
                    class="text-xl font-extrabold text-slate-900 sm:text-2xl dark:text-white"
                >
                    নোট
                    <span class="text-indigo-600 dark:text-indigo-400"
                        >আর্কাইভ</span
                    >
                </h1>
                <p
                    class="mt-1 text-xs text-slate-500 sm:text-sm dark:text-gray-400"
                >
                    বিষয় ও অধ্যায়ভিত্তিক হ্যান্ডনোট, বই ও লেকচার কালেকশন
                </p>
            </div>

            <!-- Header Controls: Track Selector & Upload CTA -->
            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Add Resource CTA Button (First on mobile) -->
                <button
                    type="button"
                    @click="openUploadModal"
                    class="order-first inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-indigo-700 active:scale-95 sm:order-last"
                >
                    <Plus class="h-4 w-4 stroke-[2.5]" />
                    <span>নোট শেয়ার করুন</span>
                </button>

                <!-- Combined Track Selector Dropdown -->
                <div
                    class="relative order-last sm:order-first"
                    ref="trackDropdownRef"
                >
                    <button
                        type="button"
                        @click="isTrackDropdownOpen = !isTrackDropdownOpen"
                        class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 shadow-2xs transition hover:border-slate-300 hover:bg-slate-50 active:scale-95 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
                    >
                        <GraduationCap
                            class="h-4 w-4 text-indigo-600 dark:text-indigo-400"
                        />
                        <span
                            class="font-extrabold text-slate-900 uppercase dark:text-white"
                            >{{ currentCourse }}</span
                        >
                        <span class="text-slate-300 dark:text-gray-600">•</span>
                        <span>{{ currentGroupLabel }}</span>
                        <ChevronDown
                            class="h-3.5 w-3.5 text-slate-400 transition-transform duration-200 dark:text-gray-500"
                            :class="{ 'rotate-180': isTrackDropdownOpen }"
                        />
                    </button>

                    <!-- Track Menu Popover -->
                    <div
                        v-if="isTrackDropdownOpen"
                        class="absolute top-full left-0 z-50 mt-1.5 w-56 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl sm:right-0 sm:left-auto dark:border-gray-800 dark:bg-gray-900"
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
                                v-for="grp in groupTabs"
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
                                v-for="grp in groupTabs"
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
        </div>

        <!-- 3. Filter Toolbar Card -->
        <div
            class="mb-0 rounded-2xl border border-slate-200/80 bg-white p-3.5 shadow-2xs sm:mb-6 sm:p-4 dark:border-gray-800 dark:bg-gray-900"
        >
            <div class="flex flex-col gap-3">
                <!-- Search & Dropdowns Row -->
                <div
                    class="grid grid-cols-1 gap-2.5 sm:grid-cols-12 sm:items-center"
                >
                    <!-- Search Input with Dedicated Search Button -->
                    <div class="relative sm:col-span-5">
                        <Search
                            class="pointer-events-none absolute top-2.5 left-3 h-4 w-4 text-slate-400 dark:text-gray-500"
                        />
                        <input
                            v-model="searchInput"
                            @keydown.enter="handleSearchSubmit"
                            type="text"
                            placeholder="নোট, টপিক বা @ইউজারনেম..."
                            class="h-9 w-full rounded-xl border border-slate-200/90 bg-slate-50/70 pr-20 pl-9 text-xs text-slate-800 placeholder-slate-400 transition focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-100 dark:placeholder-gray-500 dark:focus:border-indigo-500 dark:focus:bg-gray-800"
                        />
                        <div
                            class="absolute top-1.5 right-1.5 flex items-center gap-1"
                        >
                            <button
                                v-if="searchInput"
                                type="button"
                                @click="clearSearch"
                                class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-gray-300"
                                title="ক্লিয়ার করুন"
                            >
                                <X class="h-3.5 w-3.5" />
                            </button>
                            <button
                                type="button"
                                @click="handleSearchSubmit"
                                class="inline-flex cursor-pointer items-center justify-center rounded-lg bg-indigo-600 px-2.5 py-1 text-[11px] font-bold text-white shadow-2xs transition hover:bg-indigo-700 active:scale-95"
                            >
                                খুঁজুন
                            </button>
                        </div>
                    </div>

                    <!-- Subject Select Dropdown -->
                    <div class="sm:col-span-4">
                        <select
                            v-model="selectedSubjectId"
                            @change="handleSubjectChange"
                            class="h-9 w-full rounded-xl border border-slate-200/90 bg-slate-50/70 px-3 text-xs font-semibold text-slate-800 transition focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-100 dark:focus:border-indigo-500 dark:focus:bg-gray-800"
                        >
                            <option :value="null">সকল বিষয়</option>
                            <option
                                v-for="subj in subjects"
                                :key="subj.id"
                                :value="subj.id"
                            >
                                {{ subj.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Chapter Select Dropdown (Hidden on mobile if no subject is selected) -->
                    <div
                        class="sm:col-span-3"
                        :class="[
                            !selectedSubjectId ? 'hidden sm:block' : 'block',
                        ]"
                    >
                        <select
                            v-model="selectedNodeId"
                            @change="applyFilters"
                            :disabled="!selectedSubjectId"
                            class="h-9 w-full rounded-xl border border-slate-200/90 bg-slate-50/70 px-3 text-xs font-semibold text-slate-800 transition focus:border-indigo-500 focus:bg-white focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-100 dark:focus:border-indigo-500 dark:focus:bg-gray-800"
                        >
                            <option :value="null">সকল অধ্যায়</option>
                            <option value="whole">
                                পুরো বিষয়ভিত্তিক রিসোর্স
                            </option>
                            <option
                                v-for="ch in availableChapters"
                                :key="ch.id"
                                :value="ch.id"
                            >
                                {{ ch.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Type Pills & Sort Row -->
                <div
                    class="flex flex-wrap items-center gap-x-2.5 gap-y-2 border-t border-slate-100 pt-3 dark:border-gray-800/80"
                >
                    <!-- Resource Type Pills (Horizontal scroll on mobile, wrap on desktop) -->
                    <div
                        ref="pillsContainerRef"
                        class="no-scrollbar flex min-w-0 flex-1 basis-full items-center gap-1.5 overflow-x-auto pb-1 sm:basis-auto sm:flex-wrap sm:pb-0"
                    >
                        <button
                            v-for="item in resourceTypes"
                            :key="item.key"
                            type="button"
                            @click="handleTypeClick(item.key, $event)"
                            class="inline-flex shrink-0 cursor-pointer items-center gap-1 rounded-xl px-2.5 py-1 text-xs font-semibold whitespace-nowrap transition"
                            :class="[
                                selectedType === item.key
                                    ? 'bg-slate-900 text-white shadow-2xs dark:bg-white dark:text-slate-900'
                                    : 'border border-slate-200/80 bg-slate-50/70 text-slate-600 hover:bg-slate-100 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-300 dark:hover:bg-gray-800',
                            ]"
                        >
                            <component
                                :is="item.icon"
                                v-if="item.icon"
                                class="h-3 w-3 shrink-0"
                            />
                            <span>{{ item.label }}</span>
                        </button>
                    </div>

                    <!-- Sort Dropdown — desktop only (inside card) -->
                    <div class="relative hidden shrink-0 sm:block">
                        <select
                            v-model="currentSort"
                            @change="handleSortChange(currentSort)"
                            aria-label="সর্টিং"
                            class="h-8 cursor-pointer appearance-none rounded-xl border border-slate-200/90 bg-slate-50/80 pr-7 pl-2.5 text-xs font-semibold text-slate-700 transition focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-gray-800 dark:bg-gray-800/70 dark:text-gray-200 dark:focus:bg-gray-800"
                        >
                            <option value="trending">🔥 Trending</option>
                            <option value="top">⭐ Best</option>
                            <option value="latest">🕒 Newest</option>
                        </select>
                        <ChevronDown
                            class="pointer-events-none absolute top-2.5 right-2 h-3.5 w-3.5 text-slate-400 dark:text-gray-500"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Sort Dropdown — mobile only (outside card) -->
        <div class="mt-3 mb-3 sm:hidden">
            <div class="relative w-fit">
                <select
                    v-model="currentSort"
                    @change="handleSortChange(currentSort)"
                    aria-label="সর্টিং"
                    class="h-8 cursor-pointer appearance-none rounded-xl border border-slate-200/90 bg-white pr-7 pl-2.5 text-xs font-semibold text-slate-700 shadow-2xs transition focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200"
                >
                    <option value="trending">🔥 Trending</option>
                    <option value="top">⭐ Best</option>
                    <option value="latest">🕒 Newest</option>
                </select>
                <ChevronDown
                    class="pointer-events-none absolute top-2.5 right-2 h-3.5 w-3.5 text-slate-400 dark:text-gray-500"
                />
            </div>
        </div>

        <!-- 4. Resources Cards Grid -->
        <div v-if="resourceList.length > 0">
            <div
                class="grid grid-cols-2 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-4"
            >
                <TrendingNoteCard
                    v-for="note in resourceList"
                    :key="note.id"
                    :note="note"
                />
            </div>

            <!-- Automated Infinite Scroll Sentinel & Loader -->
            <div
                v-if="nextPageUrl"
                ref="sentinelRef"
                class="mt-8 flex justify-center py-4"
            >
                <Loader2
                    v-if="isLoadingMore"
                    class="h-5 w-5 animate-spin text-indigo-600 dark:text-indigo-400"
                />
            </div>
        </div>

        <!-- 5. Empty State -->
        <div v-else class="mt-8">
            <EmptyState
                variant="dashed"
                title="কোনো রিসোর্স পাওয়া যায়নি"
                description="আপনার ফিল্টারের সাথে মিলে এমন কোনো নোট বা বই এখনও যোগ করা হয়নি। আপনি চাইলে নিজেই প্রথম রিসোর্সটি শেয়ার করতে পারেন!"
            >
                <template #action>
                    <div class="flex items-center gap-3">
                        <button
                            v-if="activeFiltersCount > 0"
                            type="button"
                            @click="resetAllFilters"
                            class="cursor-pointer rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                        >
                            ফিল্টার ক্লিয়ার করুন
                        </button>
                        <button
                            type="button"
                            @click="openUploadModal"
                            class="cursor-pointer rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-indigo-700"
                        >
                            নোট শেয়ার করুন
                        </button>
                    </div>
                </template>
            </EmptyState>
        </div>
    </div>

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

    <!-- Upload Resource Modal (Uses unified ResourceFormModal with subjects support) -->
    <ResourceFormModal
        :is-open="isUploadModalOpen"
        :subjects="subjects"
        @close="isUploadModalOpen = false"
    />
</template>
