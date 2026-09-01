<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Search,
    X,
    Plus,
    MessageSquare,
    CheckCircle2,
    ImageIcon,
    SlidersHorizontal,
    RotateCcw,
    Check,
    Lock,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import AuthModal from '@/components/AuthModal.vue';
import ForumVoteButtons from '@/components/forum/ForumVoteButtons.vue';
import PwaInstallPrompt from '@/components/PwaInstallPrompt.vue';

interface User {
    id: number;
    name: string;
    username: string;
    image_path?: string | null;
    image_url?: string | null;
    institution?: string | null;
}

interface NodeItem {
    id: number;
    subject_id: number;
    name: string;
    slug: string;
}

interface Subject {
    id: number;
    name: string;
    course: 'hsc' | 'ssc';
    slug: string;
    nodes?: NodeItem[];
}

interface ForumPost {
    id: number;
    user_id: number;
    subject_id?: number | null;
    node_id?: number | null;
    curriculum: 'hsc' | 'ssc';
    title: string;
    slug: string;
    body: string;
    image_path?: string | null;
    image_url?: string | null;
    is_answered: boolean;
    vote_score: number;
    upvotes_count: number;
    downvotes_count: number;
    answers_count: number;
    created_at: string;
    user?: User;
    subject?: Subject | null;
    node?: NodeItem | null;
    user_vote?: number | null;
}

interface PaginationLinks {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedPosts {
    data: ForumPost[];
    links: PaginationLinks[];
    current_page: number;
    last_page: number;
    total: number;
}

const props = defineProps<{
    posts: PaginatedPosts;
    subjects: Subject[];
    filters: {
        curriculum?: string | null;
        subject_id?: string | null;
        node_id?: string | null;
        status?: string | null;
        search?: string | null;
        sort?: string | null;
        my_posts?: string | null;
    };
    postingEnabled?: boolean;
    commentsEnabled?: boolean;
    disabledReason?: string;
}>();

const page = usePage();
const user = computed(() => (page.props.auth as any)?.user);

const showAuthModal = ref(false);
const showFilterModal = ref(false);
const searchQuery = ref(props.filters.search || '');

const currentSort = computed(() => props.filters.sort || 'recent');
const currentStatus = computed(() => props.filters.status || '');
const currentCurriculum = computed(() => props.filters.curriculum || '');
const currentSubjectId = computed(() => props.filters.subject_id || '');
const currentNodeId = computed(() => props.filters.node_id || '');
const currentMyPosts = computed(() => props.filters.my_posts || '');

const sortOptions = [
    { key: 'recent', label: 'Recent' },
    { key: 'trending', label: 'Trending' },
    { key: 'top', label: 'Top Voted' },
];

const statusOptions = [
    { key: '', label: 'All' },
    { key: 'unanswered', label: 'Unanswered' },
    { key: 'answered', label: 'Answered' },
];

// Draft state for filter modal
const draftSort = ref(currentSort.value);
const draftStatus = ref(currentStatus.value);
const draftCurriculum = ref(currentCurriculum.value);
const draftSubjectId = ref(currentSubjectId.value);
const draftNodeId = ref(currentNodeId.value);
const draftMyPosts = ref(currentMyPosts.value);

const openFilterModal = () => {
    draftSort.value = currentSort.value;
    draftStatus.value = currentStatus.value;
    draftCurriculum.value = currentCurriculum.value;
    draftSubjectId.value = currentSubjectId.value;
    draftNodeId.value = currentNodeId.value;
    draftMyPosts.value = currentMyPosts.value;
    showFilterModal.value = true;
};

const safeSubjects = computed<Subject[]>(() => {
    if (Array.isArray(props.subjects)) {
        return props.subjects;
    }

    if (props.subjects && typeof props.subjects === 'object') {
        return Object.values(props.subjects) as Subject[];
    }

    return [];
});

const modalFilteredSubjects = computed(() => {
    if (!draftCurriculum.value) {
        return safeSubjects.value;
    }

    return safeSubjects.value.filter(
        (s) => s && s.course === draftCurriculum.value,
    );
});

const modalSelectedSubject = computed(() => {
    return safeSubjects.value.find(
        (s) => s && s.id === Number(draftSubjectId.value),
    );
});

const modalSubjectNodes = computed(() => {
    return modalSelectedSubject.value?.nodes || [];
});

watch(draftCurriculum, () => {
    // If subject does not match new curriculum, reset subject and node
    if (draftSubjectId.value) {
        const found = modalFilteredSubjects.value.some(
            (s) => s && s.id === Number(draftSubjectId.value),
        );

        if (!found) {
            draftSubjectId.value = '';
            draftNodeId.value = '';
        }
    }
});

watch(draftSubjectId, () => {
    draftNodeId.value = '';
});

// Active filter labels & count for badge display
const selectedSubjectName = computed(() => {
    if (!currentSubjectId.value) {
        return null;
    }

    if (currentSubjectId.value === 'other') {
        return 'Other / General';
    }

    return (
        safeSubjects.value.find(
            (s) => s && s.id === Number(currentSubjectId.value),
        )?.name || null
    );
});

const selectedNodeName = computed(() => {
    if (!currentNodeId.value) {
        return null;
    }

    for (const subj of safeSubjects.value) {
        const node = subj?.nodes?.find(
            (n) => n && n.id === Number(currentNodeId.value),
        );

        if (node) {
            return node.name;
        }
    }

    return null;
});

const activeFilterCount = computed(() => {
    let count = 0;

    if (currentCurriculum.value) {
        count++;
    }

    if (currentSubjectId.value) {
        count++;
    }

    if (currentNodeId.value) {
        count++;
    }

    if (currentStatus.value) {
        count++;
    }

    if (currentSort.value && currentSort.value !== 'recent') {
        count++;
    }

    if (currentMyPosts.value) {
        count++;
    }

    return count;
});

const applyFilters = (newFilters: Record<string, any>) => {
    const query: Record<string, any> = {
        curriculum: currentCurriculum.value || undefined,
        subject_id: currentSubjectId.value || undefined,
        node_id: currentNodeId.value || undefined,
        status: currentStatus.value || undefined,
        sort: currentSort.value !== 'recent' ? currentSort.value : undefined,
        search: searchQuery.value.trim() || undefined,
        my_posts: currentMyPosts.value || undefined,
        ...newFilters,
    };

    // Clean undefined/empty values
    Object.keys(query).forEach((k) => {
        if (!query[k]) {
            delete query[k];
        }
    });

    router.get('/forum', query, {
        preserveState: true,
        preserveScroll: true,
    });
};

const handleApplyModalFilters = () => {
    showFilterModal.value = false;
    applyFilters({
        sort: draftSort.value !== 'recent' ? draftSort.value : undefined,
        status: draftStatus.value || undefined,
        curriculum: draftCurriculum.value || undefined,
        subject_id: draftSubjectId.value || undefined,
        node_id: draftNodeId.value || undefined,
        my_posts: draftMyPosts.value || undefined,
    });
};

const handleResetModalFilters = () => {
    draftSort.value = 'recent';
    draftStatus.value = '';
    draftCurriculum.value = '';
    draftSubjectId.value = '';
    draftNodeId.value = '';
    draftMyPosts.value = '';
};

const setCurriculum = (curriculum: string) => {
    applyFilters({
        curriculum: curriculum || undefined,
        subject_id: undefined,
        node_id: undefined,
    });
};

const setSubject = (subjectId: string) => {
    applyFilters({ subject_id: subjectId || undefined, node_id: undefined });
};

const setNode = (nodeId: string) => {
    applyFilters({ node_id: nodeId || undefined });
};

const setStatus = (status: string) => {
    applyFilters({ status: status || undefined });
};

const setSort = (sortKey: string) => {
    applyFilters({ sort: sortKey });
};

const setMyPosts = (val: boolean) => {
    applyFilters({ my_posts: val ? '1' : undefined });
};

const handleSearch = () => {
    applyFilters({ search: searchQuery.value });
};

const clearSearch = () => {
    searchQuery.value = '';
    applyFilters({ search: undefined });
};

const resetAllFilters = () => {
    searchQuery.value = '';
    router.get('/forum');
};

const isUserBanned = computed(() => {
    return Boolean(user.value?.is_banned);
});

const handleAskQuestion = () => {
    if (props.postingEnabled === false || isUserBanned.value) {
        return;
    }

    if (!user.value) {
        showAuthModal.value = true;
    } else {
        router.visit('/forum/ask');
    }
};

function timeAgo(dateString?: string): string {
    if (!dateString) {
        return '';
    }

    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now.getTime() - date.getTime()) / 1000);

    if (seconds < 60) {
        return 'just now';
    }

    const minutes = Math.floor(seconds / 60);

    if (minutes < 60) {
        return `${minutes}m ago`;
    }

    const hours = Math.floor(minutes / 60);

    if (hours < 24) {
        return `${hours}h ago`;
    }

    const days = Math.floor(hours / 24);

    if (days < 30) {
        return `${days}d ago`;
    }

    const months = Math.floor(days / 30);

    if (months < 12) {
        return `${months}mo ago`;
    }

    const years = Math.floor(days / 365);

    return `${years}y ago`;
}
</script>

<template>
    <Head>
        <title>HSCStack Forum — Academic Q&A</title>
        <meta
            name="description"
            content="Ask academic questions, share answers, and collaborate with HSC and SSC students on HSCStack Forum."
        />
    </Head>

    <main class="mx-auto max-w-5xl px-3.5 py-4 sm:px-6 sm:py-8 lg:px-8">
        <!-- Header Row -->
        <div
            class="mb-3.5 flex items-center justify-between gap-3 sm:mb-6 sm:border-b sm:border-slate-100 sm:pb-5 dark:sm:border-gray-800"
        >
            <div>
                <h1
                    class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-3xl dark:text-gray-100"
                >
                    HSCStack <span class="text-indigo-600">Forum</span>
                </h1>
                <p
                    class="hidden text-xs text-slate-500 sm:mt-1 sm:block sm:text-sm dark:text-gray-400"
                >
                    প্রশ্ন করুন, উত্তর দিন এবং সহপাঠীদের সাথে জ্ঞান আদান-প্রদান
                    করুন।
                </p>
            </div>

            <div>
                <button
                    type="button"
                    @click="handleAskQuestion"
                    :disabled="props.postingEnabled === false || isUserBanned"
                    :title="
                        isUserBanned
                            ? 'You are temporarily suspended from community participation'
                            : props.postingEnabled === false
                              ? props.disabledReason ||
                                'Question posting is temporarily paused'
                              : 'Ask Question'
                    "
                    class="inline-flex items-center justify-center gap-1.5 rounded-xl px-3 py-2 text-xs font-semibold shadow-xs transition sm:gap-2 sm:px-4 sm:py-2.5 sm:text-sm"
                    :class="[
                        props.postingEnabled === false || isUserBanned
                            ? 'cursor-not-allowed bg-slate-200 text-slate-500 dark:bg-gray-800 dark:text-gray-500'
                            : 'bg-indigo-600 text-white hover:bg-indigo-700 active:scale-[0.98]',
                    ]"
                >
                    <Lock
                        v-if="props.postingEnabled === false || isUserBanned"
                        class="h-3.5 w-3.5 sm:h-4 sm:w-4"
                    />
                    <Plus v-else class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                    <span>{{
                        isUserBanned
                            ? 'Account Suspended'
                            : props.postingEnabled === false
                              ? 'Posting Paused'
                              : 'Ask Question'
                    }}</span>
                </button>
            </div>
        </div>

        <!-- Search Bar & Filter Modal Trigger (Single Row on Mobile) -->
        <div class="mb-3.5 flex items-center gap-2 sm:mb-4 sm:gap-3">
            <!-- Search Bar -->
            <div class="relative flex-1">
                <div
                    class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 dark:text-gray-500"
                >
                    <Search class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                </div>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search questions..."
                    @keyup.enter="handleSearch"
                    class="w-full rounded-xl border border-slate-200 bg-white py-2 pr-16 pl-8.5 text-xs text-slate-900 shadow-2xs transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none sm:py-2.5 sm:pr-20 sm:pl-10 sm:text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500"
                />
                <div
                    class="absolute inset-y-0 right-1.5 flex items-center gap-1"
                >
                    <button
                        v-if="searchQuery"
                        @click="clearSearch"
                        type="button"
                        class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                        aria-label="Clear search"
                    >
                        <X class="h-3 w-3 sm:h-3.5 sm:w-3.5" />
                    </button>
                    <button
                        @click="handleSearch"
                        type="button"
                        class="rounded-lg bg-slate-900 px-2.5 py-1 text-[11px] font-semibold text-white hover:bg-slate-800 sm:px-3 sm:py-1.5 sm:text-xs dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200"
                    >
                        Search
                    </button>
                </div>
            </div>

            <!-- Filter & Sort Trigger Button -->
            <button
                type="button"
                @click="openFilterModal"
                class="inline-flex shrink-0 cursor-pointer items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 shadow-2xs transition hover:border-slate-300 hover:bg-slate-50 active:scale-95 sm:px-4 sm:py-2.5 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
                title="Filter & Sort"
            >
                <SlidersHorizontal
                    class="h-3.5 w-3.5 text-indigo-600 sm:h-4 sm:w-4 dark:text-indigo-400"
                />
                <span class="hidden sm:inline">Filter & Sort</span>
                <span class="sm:hidden">Filter</span>
                <span
                    v-if="activeFilterCount > 0"
                    class="flex h-4 w-4 items-center justify-center rounded-full bg-indigo-600 text-[9px] font-extrabold text-white sm:h-5 sm:w-5 sm:text-[10px]"
                >
                    {{ activeFilterCount }}
                </span>
            </button>

            <!-- My Posts Filter (only shown when logged in) -->
            <button
                v-if="user"
                type="button"
                @click="setMyPosts(!currentMyPosts)"
                class="inline-flex shrink-0 cursor-pointer items-center justify-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-bold shadow-2xs transition active:scale-95 sm:px-3 sm:py-2.5"
                :class="[
                    currentMyPosts
                        ? 'border-indigo-600 bg-indigo-600 text-white'
                        : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800',
                ]"
                title="Show only my questions"
            >
                <span>Mine</span>
            </button>
        </div>

        <!-- Active Filter Badges Bar -->
        <div
            v-if="activeFilterCount > 0"
            class="mb-5 flex flex-wrap items-center gap-2 text-xs"
        >
            <span class="font-medium text-slate-400 dark:text-gray-500"
                >Active filters:</span
            >

            <!-- Status Badge -->
            <span
                v-if="currentStatus"
                class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 py-1 font-bold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300"
            >
                <span
                    >Status:
                    {{
                        currentStatus === 'unanswered'
                            ? 'Unanswered'
                            : 'Answered'
                    }}</span
                >
                <button
                    type="button"
                    @click="setStatus('')"
                    class="cursor-pointer rounded p-0.5 hover:bg-emerald-200/50 dark:hover:bg-emerald-800/50"
                    aria-label="Remove status filter"
                >
                    <X class="h-3 w-3" />
                </button>
            </span>

            <!-- Curriculum Badge -->
            <span
                v-if="currentCurriculum"
                class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-2.5 py-1 font-bold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300"
            >
                <span>Curriculum: {{ currentCurriculum.toUpperCase() }}</span>
                <button
                    type="button"
                    @click="setCurriculum('')"
                    class="cursor-pointer rounded p-0.5 hover:bg-indigo-200/50 dark:hover:bg-indigo-800/50"
                    aria-label="Remove curriculum filter"
                >
                    <X class="h-3 w-3" />
                </button>
            </span>

            <!-- Subject Badge -->
            <span
                v-if="selectedSubjectName"
                class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-2.5 py-1 font-medium text-slate-700 dark:bg-gray-800 dark:text-gray-300"
            >
                <span>Subject: {{ selectedSubjectName }}</span>
                <button
                    type="button"
                    @click="setSubject('')"
                    class="cursor-pointer rounded p-0.5 hover:bg-slate-200 dark:hover:bg-gray-700"
                    aria-label="Remove subject filter"
                >
                    <X class="h-3 w-3" />
                </button>
            </span>

            <!-- Chapter Badge -->
            <span
                v-if="selectedNodeName"
                class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-2.5 py-1 font-medium text-slate-700 dark:bg-gray-800 dark:text-gray-300"
            >
                <span>Chapter: {{ selectedNodeName }}</span>
                <button
                    type="button"
                    @click="setNode('')"
                    class="cursor-pointer rounded p-0.5 hover:bg-slate-200 dark:hover:bg-gray-700"
                    aria-label="Remove chapter filter"
                >
                    <X class="h-3 w-3" />
                </button>
            </span>

            <!-- Sort Badge -->
            <span
                v-if="currentSort && currentSort !== 'recent'"
                class="inline-flex items-center gap-1.5 rounded-lg bg-amber-50 px-2.5 py-1 font-semibold text-amber-700 dark:bg-amber-950/60 dark:text-amber-300"
            >
                <span
                    >Sort:
                    {{
                        sortOptions.find((t) => t.key === currentSort)?.label ||
                        currentSort
                    }}</span
                >
                <button
                    type="button"
                    @click="setSort('recent')"
                    class="cursor-pointer rounded p-0.5 hover:bg-amber-200/50 dark:hover:bg-amber-800/50"
                    aria-label="Remove sort filter"
                >
                    <X class="h-3 w-3" />
                </button>
            </span>

            <!-- My Posts Badge -->
            <span
                v-if="currentMyPosts"
                class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-2.5 py-1 font-bold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300"
            >
                <span>My Questions</span>
                <button
                    type="button"
                    @click="setMyPosts(false)"
                    class="cursor-pointer rounded p-0.5 hover:bg-indigo-200/50 dark:hover:bg-indigo-800/50"
                    aria-label="Remove my posts filter"
                >
                    <X class="h-3 w-3" />
                </button>
            </span>

            <!-- Clear All Action -->
            <button
                type="button"
                @click="resetAllFilters"
                class="inline-flex cursor-pointer items-center gap-1 rounded-lg px-2 py-1 font-medium text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
            >
                <RotateCcw class="h-3 w-3" />
                <span>Reset all</span>
            </button>
        </div>

        <!-- Post List Feed -->
        <div v-if="posts.data.length > 0" class="space-y-3">
            <article
                v-for="post in posts.data"
                :key="post.id"
                class="group cursor-pointer rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs transition duration-150 hover:border-slate-300 hover:shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:hover:border-gray-700"
                @click="router.visit('/forum/questions/' + post.slug)"
            >
                <div class="flex items-start gap-3 sm:gap-4">
                    <!-- Left: Vote Controls (Vertical on all screens) -->
                    <div class="shrink-0 pt-0.5" @click.stop>
                        <ForumVoteButtons
                            votableType="post"
                            :votableId="post.id"
                            :initialUpvotes="post.upvotes_count"
                            :initialDownvotes="post.downvotes_count"
                            :initialUserVote="post.user_vote"
                            direction="vertical"
                            size="sm"
                        />
                    </div>

                    <!-- Right: Content Body -->
                    <div class="min-w-0 flex-1">
                        <!-- Badges Row -->
                        <div
                            class="mb-1.5 flex flex-wrap items-center gap-1.5 text-[11px]"
                        >
                            <!-- Curriculum Badge -->
                            <span
                                class="rounded-md px-1.5 py-0.5 font-bold uppercase"
                                :class="[
                                    post.curriculum === 'ssc'
                                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400'
                                        : 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-400',
                                ]"
                            >
                                {{ post.curriculum }}
                            </span>

                            <!-- Subject Badge -->
                            <span
                                v-if="post.subject"
                                class="rounded-md bg-slate-100 px-1.5 py-0.5 font-medium text-slate-700 dark:bg-gray-800 dark:text-gray-300"
                            >
                                {{ post.subject.name }}
                            </span>
                            <span
                                v-else
                                class="rounded-md bg-slate-100 px-1.5 py-0.5 font-medium text-slate-500 dark:bg-gray-800 dark:text-gray-400"
                            >
                                Other / General
                            </span>

                            <!-- Chapter Badge -->
                            <span
                                v-if="post.node"
                                class="rounded-md bg-slate-100 px-1.5 py-0.5 font-medium text-slate-600 dark:bg-gray-800 dark:text-gray-400"
                            >
                                # {{ post.node.name }}
                            </span>

                            <!-- Answered Badge -->
                            <span
                                v-if="post.is_answered"
                                class="inline-flex items-center gap-1 rounded-md bg-emerald-100/70 px-1.5 py-0.5 font-bold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
                            >
                                <CheckCircle2
                                    class="h-3 w-3 text-emerald-600 dark:text-emerald-400"
                                />
                                <span>Answered</span>
                            </span>
                        </div>

                        <!-- Question Title -->
                        <h2
                            class="text-base font-bold text-slate-900 transition sm:text-lg dark:text-gray-100"
                        >
                            <Link
                                :href="`/forum/questions/${post.slug}`"
                                class="hover:text-indigo-600 dark:hover:text-indigo-400"
                            >
                                {{ post.title }}
                            </Link>
                        </h2>

                        <!-- Preview snippet -->
                        <p
                            class="mt-1 line-clamp-2 text-xs leading-relaxed text-slate-600 sm:text-sm dark:text-gray-300"
                        >
                            {{ post.body }}
                        </p>

                        <!-- Attached Image Indicator (if any) -->
                        <div
                            v-if="post.image_url"
                            class="mt-2 inline-flex items-center gap-1 text-xs text-slate-400 dark:text-gray-500"
                        >
                            <ImageIcon class="h-3.5 w-3.5" />
                            <span>Image attached</span>
                        </div>

                        <!-- Footer Author & Stats Metadata -->
                        <div
                            class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-2.5 text-xs text-slate-400 dark:border-gray-800/80 dark:text-gray-500"
                        >
                            <!-- Author info -->
                            <div class="flex items-center gap-1.5">
                                <Link
                                    v-if="post.user?.username"
                                    :href="`/u/${post.user.username}`"
                                    @click.stop
                                    class="inline-flex items-center gap-1.5 font-medium text-slate-700 hover:text-indigo-600 hover:underline dark:text-gray-300 dark:hover:text-indigo-400"
                                >
                                    <div
                                        class="flex h-5 w-5 items-center justify-center overflow-hidden rounded-full bg-slate-200 text-[10px] font-bold text-slate-700 dark:bg-gray-700 dark:text-gray-300"
                                    >
                                        <img
                                            v-if="post.user.image_url"
                                            :src="post.user.image_url"
                                            :alt="post.user.name"
                                            class="h-full w-full object-cover"
                                        />
                                        <span v-else>{{
                                            post.user.name.charAt(0)
                                        }}</span>
                                    </div>
                                    <span>{{ post.user.name }}</span>
                                </Link>
                                <span
                                    v-else
                                    class="font-medium text-slate-700 dark:text-gray-300"
                                >
                                    {{ post.user?.name || 'Anonymous' }}
                                </span>

                                <span
                                    v-if="post.user?.institution"
                                    class="hidden text-slate-400 sm:inline dark:text-gray-500"
                                >
                                    ({{ post.user.institution }})
                                </span>

                                <span>•</span>
                                <span>{{ timeAgo(post.created_at) }}</span>
                            </div>

                            <!-- Answer count badge -->
                            <div class="flex items-center gap-3">
                                <span
                                    class="inline-flex items-center gap-1 font-medium"
                                    :class="
                                        post.answers_count > 0
                                            ? 'text-indigo-600 dark:text-indigo-400'
                                            : 'text-slate-400 dark:text-gray-500'
                                    "
                                >
                                    <MessageSquare class="h-3.5 w-3.5" />
                                    <span
                                        >{{ post.answers_count }}
                                        {{
                                            post.answers_count === 1
                                                ? 'answer'
                                                : 'answers'
                                        }}</span
                                    >
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        </div>

        <!-- Empty State -->
        <div
            v-else
            class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-white p-12 text-center shadow-2xs dark:border-gray-800 dark:bg-gray-900"
        >
            <div
                class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-gray-800 dark:text-gray-500"
            >
                <Search class="h-6 w-6" />
            </div>
            <h3
                class="mt-4 text-base font-bold text-slate-900 dark:text-gray-100"
            >
                No questions found
            </h3>
            <p class="mt-1 max-w-sm text-xs text-slate-500 dark:text-gray-400">
                We couldn't find any questions matching your current filters.
                Try changing your search keywords or resetting filters.
            </p>
            <div class="mt-6 flex items-center gap-3">
                <button
                    v-if="searchQuery || activeFilterCount > 0"
                    type="button"
                    @click="resetAllFilters"
                    class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800"
                >
                    Reset Filters
                </button>
                <button
                    type="button"
                    @click="handleAskQuestion"
                    :disabled="props.postingEnabled === false || isUserBanned"
                    class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-semibold shadow-xs transition"
                    :class="[
                        props.postingEnabled === false || isUserBanned
                            ? 'cursor-not-allowed bg-slate-200 text-slate-500 dark:bg-gray-800 dark:text-gray-500'
                            : 'bg-indigo-600 text-white hover:bg-indigo-700',
                    ]"
                >
                    <Lock
                        v-if="props.postingEnabled === false || isUserBanned"
                        class="h-4 w-4"
                    />
                    <Plus v-else class="h-4 w-4" />
                    <span>{{
                        isUserBanned
                            ? 'Account Suspended'
                            : props.postingEnabled === false
                              ? 'Posting Paused'
                              : 'Ask Question'
                    }}</span>
                </button>
            </div>
        </div>

        <!-- Pagination -->
        <div
            v-if="posts.links && posts.links.length > 3"
            class="mt-8 flex items-center justify-center gap-1"
        >
            <Component
                :is="link.url ? Link : 'span'"
                v-for="(link, i) in posts.links"
                :key="i"
                :href="link.url || '#'"
                preserve-scroll
                class="flex h-8 min-w-[32px] items-center justify-center rounded-lg px-2 text-xs font-semibold transition"
                :class="[
                    link.active
                        ? 'bg-indigo-600 text-white'
                        : link.url
                          ? 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800'
                          : 'cursor-not-allowed text-slate-400 opacity-40 dark:text-gray-600',
                ]"
            >
                <span v-html="link.label"></span>
            </Component>
        </div>

        <!-- Filter & Sort Modal -->
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
                    v-if="showFilterModal"
                    class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-xs dark:bg-black/60"
                    @click.self="showFilterModal = false"
                >
                    <div
                        class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-gray-800 dark:bg-gray-900"
                    >
                        <!-- Modal Header -->
                        <div
                            class="mb-5 flex items-center justify-between border-b border-slate-100 pb-3.5 dark:border-gray-800"
                        >
                            <div class="flex items-center gap-2">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                                >
                                    <SlidersHorizontal class="h-4 w-4" />
                                </div>
                                <h3
                                    class="text-sm font-bold text-slate-900 dark:text-gray-100"
                                >
                                    Filter & Sort Questions
                                </h3>
                            </div>

                            <button
                                type="button"
                                @click="showFilterModal = false"
                                class="cursor-pointer rounded-lg p-1 text-slate-400 hover:text-slate-600 dark:text-gray-500 dark:hover:text-gray-300"
                                aria-label="Close filter modal"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <!-- Modal Form Fields -->
                        <div class="space-y-4.5">
                            <!-- 1. Sort Order -->
                            <div>
                                <label
                                    class="mb-2 block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-gray-300"
                                >
                                    Sort Order
                                </label>
                                <div class="grid grid-cols-3 gap-2">
                                    <button
                                        v-for="sort in sortOptions"
                                        :key="sort.key"
                                        type="button"
                                        @click="draftSort = sort.key"
                                        class="flex items-center justify-between rounded-xl border px-3 py-2 text-xs font-semibold transition"
                                        :class="[
                                            draftSort === sort.key
                                                ? 'border-indigo-600 bg-indigo-50/70 text-indigo-700 ring-1 ring-indigo-600/30 dark:border-indigo-500 dark:bg-indigo-950/50 dark:text-indigo-300'
                                                : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-300 dark:hover:bg-gray-800',
                                        ]"
                                    >
                                        <span>{{ sort.label }}</span>
                                        <Check
                                            v-if="draftSort === sort.key"
                                            class="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400"
                                        />
                                    </button>
                                </div>
                            </div>

                            <!-- 2. Answer Status (Independent from Sort) -->
                            <div>
                                <label
                                    class="mb-2 block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-gray-300"
                                >
                                    Answer Status
                                </label>
                                <div class="grid grid-cols-3 gap-2">
                                    <button
                                        v-for="status in statusOptions"
                                        :key="status.key"
                                        type="button"
                                        @click="draftStatus = status.key"
                                        class="flex items-center justify-center rounded-xl border py-2 text-xs font-semibold transition"
                                        :class="[
                                            draftStatus === status.key
                                                ? 'border-slate-900 bg-slate-900 text-white dark:border-white dark:bg-white dark:text-slate-900'
                                                : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-300 dark:hover:bg-gray-800',
                                        ]"
                                    >
                                        {{ status.label }}
                                    </button>
                                </div>
                            </div>

                            <!-- 3. Curriculum -->
                            <div>
                                <label
                                    class="mb-2 block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-gray-300"
                                >
                                    Curriculum
                                </label>
                                <div class="grid grid-cols-3 gap-2">
                                    <button
                                        type="button"
                                        @click="draftCurriculum = ''"
                                        class="flex items-center justify-center rounded-xl border py-2 text-xs font-semibold transition"
                                        :class="[
                                            !draftCurriculum
                                                ? 'border-slate-900 bg-slate-900 text-white dark:border-white dark:bg-white dark:text-slate-900'
                                                : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-300 dark:hover:bg-gray-800',
                                        ]"
                                    >
                                        All
                                    </button>
                                    <button
                                        type="button"
                                        @click="draftCurriculum = 'hsc'"
                                        class="flex items-center justify-center rounded-xl border py-2 text-xs font-semibold transition"
                                        :class="[
                                            draftCurriculum === 'hsc'
                                                ? 'border-indigo-600 bg-indigo-600 text-white'
                                                : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-300 dark:hover:bg-gray-800',
                                        ]"
                                    >
                                        HSC
                                    </button>
                                    <button
                                        type="button"
                                        @click="draftCurriculum = 'ssc'"
                                        class="flex items-center justify-center rounded-xl border py-2 text-xs font-semibold transition"
                                        :class="[
                                            draftCurriculum === 'ssc'
                                                ? 'border-emerald-600 bg-emerald-600 text-white'
                                                : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-300 dark:hover:bg-gray-800',
                                        ]"
                                    >
                                        SSC
                                    </button>
                                </div>
                            </div>

                            <!-- 4. Subject -->
                            <div>
                                <label
                                    for="modal-subject-select"
                                    class="mb-2 block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-gray-300"
                                >
                                    Subject
                                </label>
                                <select
                                    id="modal-subject-select"
                                    v-model="draftSubjectId"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-medium text-slate-900 shadow-2xs transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none dark:border-gray-800 dark:bg-gray-800 dark:text-gray-100"
                                >
                                    <option value="">All Subjects</option>
                                    <option value="other">
                                        Other / General
                                    </option>
                                    <option
                                        v-for="subj in modalFilteredSubjects"
                                        :key="subj.id"
                                        :value="subj.id.toString()"
                                    >
                                        {{ subj.name }} ({{
                                            subj.course.toUpperCase()
                                        }})
                                    </option>
                                </select>
                            </div>

                            <!-- 5. Chapter / Topic (If subject has nodes) -->
                            <div
                                v-if="
                                    draftSubjectId &&
                                    modalSubjectNodes.length > 0
                                "
                            >
                                <label
                                    for="modal-node-select"
                                    class="mb-2 block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-gray-300"
                                >
                                    Chapter / Topic
                                </label>
                                <select
                                    id="modal-node-select"
                                    v-model="draftNodeId"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-medium text-slate-900 shadow-2xs transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none dark:border-gray-800 dark:bg-gray-800 dark:text-gray-100"
                                >
                                    <option value="">All Chapters</option>
                                    <option
                                        v-for="node in modalSubjectNodes"
                                        :key="node.id"
                                        :value="node.id.toString()"
                                    >
                                        {{ node.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Modal Actions Footer -->
                        <div
                            class="mt-6 flex items-center justify-between border-t border-slate-100 pt-4 dark:border-gray-800"
                        >
                            <button
                                type="button"
                                @click="handleResetModalFilters"
                                class="inline-flex cursor-pointer items-center gap-1 text-xs font-semibold text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-200"
                            >
                                <RotateCcw class="h-3.5 w-3.5" />
                                <span>Reset</span>
                            </button>

                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="showFilterModal = false"
                                    class="rounded-xl border border-slate-200 px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="button"
                                    @click="handleApplyModalFilters"
                                    class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-indigo-700 active:scale-[0.98]"
                                >
                                    <span>Apply Filters</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Auth Modal -->
        <AuthModal
            v-model="showAuthModal"
            title="Sign in required"
            message="Please sign in to ask a question in the forum."
        />

        <!-- PWA Install Prompt Modal -->
        <PwaInstallPrompt variant="modal" />
    </main>
</template>
