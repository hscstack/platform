<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Activity,
    ArrowBigUp,
    ArrowBigDown,
    ArrowRight,
    ArrowUpRight,
    Calendar,
    CheckCircle2,
    Edit3,
    Eye,
    Facebook,
    FileText,
    Folder,
    Github,
    GraduationCap,
    Heart,
    HelpCircle,
    Instagram,
    LogIn,
    MessageSquare,
    MessageSquareCheck,
    UploadCloud,
    Users,
    X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import UserListItem from '@/components/UserListItem.vue';
import VerifiedBadge from '@/components/VerifiedBadge.vue';

const props = defineProps<{
    profileUser: {
        id: number;
        name: string;
        username: string;
        about: string | null;
        institution: string | null;
        image_url: string | null;
        facebook: string | null;
        instagram: string | null;
        github: string | null;
        created_at: string;
        is_verified?: boolean;
        is_staff?: boolean;
        roles?: string[];
    };
    stats: {
        questionsCount: number;
        answersCount: number;
        blogsCount: number;
        sharedResourcesCount: number;
        totalBlogViews: number;
    };
    appreciationsCount: number;
    appreciatingCount: number;
    isAppreciated: boolean;
    appreciators?: Array<{
        id: number;
        name: string;
        username: string;
        image_path?: string | null;
        institution?: string | null;
        is_verified?: boolean;
    }>;
    appreciating?: Array<{
        id: number;
        name: string;
        username: string;
        image_path?: string | null;
        institution?: string | null;
        is_verified?: boolean;
    }>;
    forumPosts?: Array<{
        id: number;
        title: string;
        slug: string;
        curriculum: 'hsc' | 'ssc';
        is_answered: boolean;
        answers_count?: number;
        vote_score: number;
        created_at: string;
        subject?: {
            id: number;
            name: string;
            course: string;
            slug: string;
        } | null;
        node?: {
            id: number;
            name: string;
            slug: string;
        } | null;
    }>;
    forumAnswers?: Array<{
        id: number;
        body: string;
        vote_score: number;
        created_at: string;
        post?: {
            id: number;
            title: string;
            slug: string;
            curriculum: string;
            is_answered: boolean;
        } | null;
    }>;
    blogs: Array<{
        id: number;
        title: string;
        slug: string;
        excerpt: string | null;
        featured_image: string | null;
        views: number;
        reactions_count: number;
        comments_count: number;
        created_at: string;
    }>;
    recentActivities: {
        forum_posts?: Array<{
            type: string;
            title: string;
            subtitle?: string;
            url: string | null;
            created_at: string;
            timestamp?: number;
        }>;
        forum_answers?: Array<{
            type: string;
            title: string;
            content?: string;
            url: string | null;
            created_at: string;
            timestamp?: number;
        }>;
        folders?: Array<{
            type: string;
            title: string;
            subtitle?: string;
            url: string | null;
            created_at: string;
            timestamp?: number;
        }>;
        uploads?: Array<{
            type: string;
            title: string;
            subtitle?: string;
            resource_type?: string;
            url: string | null;
            created_at: string;
            timestamp?: number;
        }>;
        reactions?: Array<{
            type: string;
            title: string;
            url: string | null;
            created_at: string;
            timestamp?: number;
        }>;
        comments?: Array<{
            type: string;
            title: string;
            content?: string;
            url: string | null;
            created_at: string;
            timestamp?: number;
        }>;
        appreciations?: Array<{
            type: string;
            title: string;
            username?: string;
            url: string | null;
            created_at: string;
            timestamp?: number;
        }>;
    };
    suggestedUsers?: Array<{
        id: number;
        name: string;
        username: string;
        institution: string | null;
        image_url: string | null;
        image_path: string | null;
        about: string | null;
        is_verified?: boolean;
    }>;
}>();

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);
const isOwnProfile = computed(
    () => currentUser.value?.id === props.profileUser.id,
);

const activeTab = ref<'forum' | 'blogs' | 'activity'>('forum');
const forumSubTab = ref<'questions' | 'answers'>('questions');

interface ActivityItem {
    type: string;
    title?: string | null;
    subtitle?: string | null;
    content?: string | null;
    username?: string | null;
    resource_type?: string | null;
    url: string | null;
    created_at: string;
    timestamp?: number;
}

const sortedActivities = computed<ActivityItem[]>(() => {
    const list: ActivityItem[] = [
        ...((props.recentActivities.forum_posts || []) as ActivityItem[]),
        ...((props.recentActivities.forum_answers || []) as ActivityItem[]),
        ...((props.recentActivities.folders || []) as ActivityItem[]),
        ...((props.recentActivities.uploads || []) as ActivityItem[]),
        ...((props.recentActivities.reactions || []) as ActivityItem[]),
        ...((props.recentActivities.comments || []) as ActivityItem[]),
        ...((props.recentActivities.appreciations || []) as ActivityItem[]),
    ];

    return list.sort((a, b) => (b.timestamp ?? 0) - (a.timestamp ?? 0));
});

const totalActivitiesCount = computed(() => sortedActivities.value.length);

const localIsAppreciated = ref(props.isAppreciated);
const localAppreciationsCount = ref(props.appreciationsCount);
const showAppreciatorsModal = ref(false);
const showAppreciatingModal = ref(false);
const showGuestModal = ref(false);

watch(
    () => props.isAppreciated,
    (val) => {
        localIsAppreciated.value = val;
    },
);

watch(
    () => props.appreciationsCount,
    (val) => {
        localAppreciationsCount.value = val;
    },
);

const handleAppreciate = () => {
    if (!currentUser.value) {
        showGuestModal.value = true;

        return;
    }

    if (isOwnProfile.value) {
        return;
    }

    // Optimistic UI update
    if (localIsAppreciated.value) {
        localIsAppreciated.value = false;
        localAppreciationsCount.value = Math.max(
            0,
            localAppreciationsCount.value - 1,
        );
    } else {
        localIsAppreciated.value = true;
        localAppreciationsCount.value++;
    }

    router.post(
        `/u/${props.profileUser.id}/appreciate`,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                localIsAppreciated.value = props.isAppreciated;
                localAppreciationsCount.value = props.appreciationsCount;
            },
        },
    );
};

function timeAgo(dateString?: string): string {
    if (!dateString) {
        return '';
    }

    const date = new Date(dateString);

    if (isNaN(date.getTime())) {
        return dateString;
    }

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
    <Head :title="`${profileUser.name} (@${profileUser.username})`">
        <meta
            name="description"
            :content="
                [profileUser.institution, profileUser.about]
                    .filter(Boolean)
                    .slice(0, 2)
                    .join(' · ') ||
                `View ${profileUser.name}'s profile, completed study topics, and contributions on HSCStack.`
            "
        />
        <meta
            property="og:title"
            :content="`${profileUser.name} (@${profileUser.username})`"
        />
        <meta
            property="og:description"
            :content="
                [profileUser.institution, profileUser.about]
                    .filter(Boolean)
                    .slice(0, 2)
                    .join(' · ') ||
                `View ${profileUser.name}'s profile, completed study topics, and contributions on HSCStack.`
            "
        />
        <meta property="og:type" content="profile" />
        <meta
            v-if="profileUser.image_url"
            property="og:image"
            :content="profileUser.image_url"
        />
        <meta name="twitter:card" content="summary_large_image" />
        <meta
            name="twitter:title"
            :content="`${profileUser.name} (@${profileUser.username})`"
        />
        <meta
            name="twitter:description"
            :content="
                [profileUser.institution, profileUser.about]
                    .filter(Boolean)
                    .slice(0, 2)
                    .join(' · ') ||
                `View ${profileUser.name}'s profile, completed study topics, and contributions on HSCStack.`
            "
        />
        <meta
            v-if="profileUser.image_url"
            name="twitter:image"
            :content="profileUser.image_url"
        />
    </Head>

    <div
        class="min-h-screen bg-slate-50/60 px-3.5 py-5 sm:px-6 sm:py-8 lg:px-8 dark:bg-gray-950"
    >
        <div class="mx-auto max-w-3xl space-y-4.5 sm:space-y-6">
            <!-- Profile Identity Card -->
            <div
                class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-4.5 shadow-xs sm:rounded-3xl sm:p-7 dark:border-gray-800 dark:bg-gray-900"
            >
                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between sm:gap-5"
                >
                    <!-- Left: Avatar & Bio Details -->
                    <div class="flex min-w-0 items-start gap-3.5 sm:gap-4.5">
                        <!-- Avatar -->
                        <div class="relative shrink-0">
                            <div
                                class="h-16 w-16 overflow-hidden rounded-2xl shadow-sm ring-4 ring-slate-100 sm:h-20 sm:w-20 dark:ring-gray-800"
                            >
                                <img
                                    v-if="profileUser.image_url"
                                    :src="profileUser.image_url"
                                    :alt="profileUser.name"
                                    class="h-full w-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center bg-gradient-to-br from-indigo-500 to-indigo-700 text-xl font-black text-white sm:text-2xl"
                                >
                                    {{
                                        profileUser.name.charAt(0).toUpperCase()
                                    }}
                                </div>
                            </div>
                        </div>

                        <!-- Name, Verified Badge, Username & Institution -->
                        <div class="min-w-0 flex-1 space-y-1">
                            <div
                                class="flex flex-wrap items-center gap-1.5 sm:gap-2"
                            >
                                <h1
                                    class="truncate text-lg font-bold tracking-tight text-slate-900 sm:text-xl dark:text-gray-100"
                                >
                                    {{ profileUser.name }}
                                </h1>

                                <VerifiedBadge
                                    v-if="
                                        profileUser.is_verified ||
                                        profileUser.is_staff
                                    "
                                    size="h-5 w-5"
                                />
                            </div>

                            <!-- Username Tag -->
                            <p
                                class="text-xs font-semibold text-slate-400 dark:text-gray-500"
                            >
                                @{{ profileUser.username }}
                            </p>

                            <!-- Academic / Institution Info -->
                            <div
                                v-if="profileUser.institution"
                                class="flex items-center gap-1.5 text-xs font-medium text-slate-600 dark:text-gray-300"
                            >
                                <GraduationCap
                                    class="h-3.5 w-3.5 shrink-0 text-slate-400 dark:text-gray-500"
                                />
                                <span class="truncate">{{
                                    profileUser.institution
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right Actions: Edit & Logout (For Profile Owner) or Appreciate (For Others) -->
                    <div
                        class="flex shrink-0 items-center gap-1.5 self-start pt-1 sm:self-auto sm:pt-0"
                    >
                        <template v-if="isOwnProfile">
                            <Link
                                href="/profile"
                                class="inline-flex h-8 items-center gap-1.5 rounded-xl bg-slate-900 px-3.5 text-xs font-semibold text-white shadow-xs transition hover:bg-slate-800 active:scale-95 sm:h-8.5 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200"
                            >
                                <Edit3 class="h-3.5 w-3.5" />
                                <span>Edit</span>
                            </Link>
                        </template>

                        <button
                            v-else
                            @click="handleAppreciate"
                            type="button"
                            class="group inline-flex h-8.5 cursor-pointer items-center gap-1.5 rounded-xl px-3 text-xs font-bold transition-all duration-150 select-none active:scale-95 sm:h-9 sm:px-3.5"
                            :class="[
                                localIsAppreciated
                                    ? 'border border-rose-200 bg-rose-50 text-rose-600 shadow-xs dark:border-rose-900/60 dark:bg-rose-950/60 dark:text-rose-400'
                                    : 'border border-slate-200 bg-white text-slate-700 shadow-xs hover:border-rose-200 hover:bg-rose-50/40 hover:text-rose-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-rose-900/50 dark:hover:bg-rose-950/30 dark:hover:text-rose-400',
                            ]"
                            :title="
                                localIsAppreciated
                                    ? 'Appreciated (click to remove)'
                                    : 'Appreciate this member'
                            "
                        >
                            <Heart
                                class="h-4 w-4 transition-transform group-hover:scale-110"
                                :class="[
                                    localIsAppreciated
                                        ? 'fill-rose-500 text-rose-500 dark:fill-rose-400 dark:text-rose-400'
                                        : 'stroke-[2.2] text-slate-500 group-hover:text-rose-500 dark:text-gray-400 dark:group-hover:text-rose-400',
                                ]"
                            />
                            <span>{{
                                localIsAppreciated
                                    ? 'Appreciated'
                                    : 'Appreciate'
                            }}</span>
                            <span
                                v-if="localAppreciationsCount > 0"
                                class="ml-0.5 rounded-lg px-1.5 py-0.5 text-[11px] font-bold"
                                :class="[
                                    localIsAppreciated
                                        ? 'bg-rose-200/70 text-rose-700 dark:bg-rose-900/70 dark:text-rose-300'
                                        : 'bg-slate-100 text-slate-600 dark:bg-gray-700 dark:text-gray-300',
                                ]"
                            >
                                {{ localAppreciationsCount }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Bio / About -->
                <div
                    v-if="profileUser.about"
                    class="mt-4 rounded-xl border border-slate-100 bg-slate-50/60 p-3 text-xs leading-relaxed text-slate-600 sm:rounded-2xl sm:p-3.5 dark:border-gray-800/80 dark:bg-gray-800/30 dark:text-gray-300"
                >
                    <p class="whitespace-pre-line">{{ profileUser.about }}</p>
                </div>

                <!-- Appreciations Summary Pill Row (Always single horizontal row) -->
                <div
                    v-if="localAppreciationsCount > 0 || appreciatingCount > 0"
                    class="mt-3.5 flex items-center gap-2 overflow-x-auto"
                >
                    <button
                        v-if="localAppreciationsCount > 0"
                        type="button"
                        @click="showAppreciatorsModal = true"
                        class="inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-lg border border-rose-100 bg-rose-50/70 px-2.5 py-1 text-xs font-bold text-rose-700 transition select-none hover:bg-rose-100 active:scale-95 dark:border-rose-900/40 dark:bg-rose-950/40 dark:text-rose-300 dark:hover:bg-rose-900/60"
                        title="View members who appreciated this profile"
                    >
                        <Heart
                            class="h-3.5 w-3.5 fill-rose-500 text-rose-500 dark:fill-rose-400 dark:text-rose-400"
                        />
                        <span class="whitespace-nowrap"
                            >{{ localAppreciationsCount }}
                            {{
                                localAppreciationsCount === 1
                                    ? 'Appreciator'
                                    : 'Appreciators'
                            }}</span
                        >
                    </button>

                    <button
                        v-if="appreciatingCount > 0"
                        type="button"
                        @click="showAppreciatingModal = true"
                        class="inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-lg border border-slate-200/80 bg-slate-50/80 px-2.5 py-1 text-xs font-semibold text-slate-600 transition select-none hover:bg-slate-100 active:scale-95 dark:border-gray-700 dark:bg-gray-800/80 dark:text-gray-300 dark:hover:bg-gray-700"
                        title="View members this user appreciates"
                    >
                        <Heart
                            class="h-3.5 w-3.5 stroke-[2] text-slate-400 dark:text-gray-500"
                        />
                        <span class="whitespace-nowrap"
                            >Appreciating {{ appreciatingCount }}
                            {{
                                appreciatingCount === 1 ? 'user' : 'users'
                            }}</span
                        >
                    </button>
                </div>

                <!-- Social Links & Joined Date Footer -->
                <div
                    class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3.5 dark:border-gray-800"
                >
                    <!-- Social Links -->
                    <div class="flex items-center gap-2">
                        <a
                            v-if="profileUser.facebook"
                            :href="profileUser.facebook"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:text-blue-400"
                            title="Facebook Profile"
                        >
                            <Facebook class="h-3.5 w-3.5" />
                        </a>
                        <a
                            v-if="profileUser.instagram"
                            :href="profileUser.instagram"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-pink-200 hover:bg-pink-50 hover:text-pink-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:text-pink-400"
                            title="Instagram Profile"
                        >
                            <Instagram class="h-3.5 w-3.5" />
                        </a>
                        <a
                            v-if="profileUser.github"
                            :href="profileUser.github"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-slate-400 hover:bg-slate-100 hover:text-slate-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:text-gray-100"
                            title="GitHub Profile"
                        >
                            <Github class="h-3.5 w-3.5" />
                        </a>
                    </div>

                    <!-- Member Joined Date -->
                    <div
                        class="flex items-center gap-1.5 text-xs font-medium text-slate-400 dark:text-gray-500"
                    >
                        <Calendar class="h-3.5 w-3.5" />
                        <span>Joined {{ profileUser.created_at }}</span>
                    </div>
                </div>
            </div>

            <!-- Stats Metrics Row (4 Cards Grid: Questions, Answers, Articles, Shared Files) -->
            <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-4 sm:gap-3">
                <!-- Questions Asked -->
                <div
                    class="rounded-xl border border-slate-200/80 bg-white p-3 shadow-xs sm:rounded-2xl dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="flex items-center gap-2.5">
                        <div
                            class="flex h-7.5 w-7.5 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                        >
                            <HelpCircle class="h-4 w-4 stroke-[2.2]" />
                        </div>
                        <div class="min-w-0">
                            <p
                                class="text-sm font-black text-slate-900 dark:text-gray-100"
                            >
                                {{ stats.questionsCount }}
                            </p>
                            <p
                                class="truncate text-[10px] font-medium text-slate-400 dark:text-gray-500"
                            >
                                Questions
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Answers Given -->
                <div
                    class="rounded-xl border border-slate-200/80 bg-white p-3 shadow-xs sm:rounded-2xl dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="flex items-center gap-2.5">
                        <div
                            class="flex h-7.5 w-7.5 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400"
                        >
                            <MessageSquareCheck class="h-4 w-4 stroke-[2.2]" />
                        </div>
                        <div class="min-w-0">
                            <p
                                class="text-sm font-black text-slate-900 dark:text-gray-100"
                            >
                                {{ stats.answersCount }}
                            </p>
                            <p
                                class="truncate text-[10px] font-medium text-slate-400 dark:text-gray-500"
                            >
                                Answers
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Articles Published -->
                <div
                    class="rounded-xl border border-slate-200/80 bg-white p-3 shadow-xs sm:rounded-2xl dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="flex items-center gap-2.5">
                        <div
                            class="flex h-7.5 w-7.5 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400"
                        >
                            <FileText class="h-4 w-4 stroke-[2.2]" />
                        </div>
                        <div class="min-w-0">
                            <p
                                class="text-sm font-black text-slate-900 dark:text-gray-100"
                            >
                                {{ stats.blogsCount }}
                            </p>
                            <p
                                class="truncate text-[10px] font-medium text-slate-400 dark:text-gray-500"
                            >
                                Articles
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Shared Files -->
                <div
                    class="rounded-xl border border-slate-200/80 bg-white p-3 shadow-xs sm:rounded-2xl dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="flex items-center gap-2.5">
                        <div
                            class="flex h-7.5 w-7.5 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400"
                        >
                            <UploadCloud class="h-4 w-4 stroke-[2.2]" />
                        </div>
                        <div class="min-w-0">
                            <p
                                class="text-sm font-black text-slate-900 dark:text-gray-100"
                            >
                                {{ stats.sharedResourcesCount }}
                            </p>
                            <p
                                class="truncate text-[10px] font-medium text-slate-400 dark:text-gray-500"
                            >
                                Shared Files
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabbed Content Section (3 Tabs: Forum, Articles, Activity) -->
            <div class="space-y-3.5">
                <!-- Navigation Tabs Bar -->
                <div
                    class="flex items-center gap-1 rounded-xl border border-slate-200/80 bg-white p-1 shadow-xs sm:rounded-2xl dark:border-gray-800 dark:bg-gray-900"
                >
                    <button
                        @click="activeTab = 'forum'"
                        type="button"
                        class="flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-xs font-bold transition sm:rounded-xl sm:px-3"
                        :class="
                            activeTab === 'forum'
                                ? 'bg-slate-900 text-white shadow-xs dark:bg-gray-100 dark:text-gray-900'
                                : 'text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800'
                        "
                    >
                        <MessageSquare class="h-3.5 w-3.5 shrink-0" />
                        <span>Forum</span>
                    </button>

                    <button
                        @click="activeTab = 'blogs'"
                        type="button"
                        class="flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-xs font-bold transition sm:rounded-xl sm:px-3"
                        :class="
                            activeTab === 'blogs'
                                ? 'bg-slate-900 text-white shadow-xs dark:bg-gray-100 dark:text-gray-900'
                                : 'text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800'
                        "
                    >
                        <FileText class="h-3.5 w-3.5 shrink-0" />
                        <span>Articles</span>
                    </button>

                    <button
                        @click="activeTab = 'activity'"
                        type="button"
                        class="flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-xs font-bold transition sm:rounded-xl sm:px-3"
                        :class="
                            activeTab === 'activity'
                                ? 'bg-slate-900 text-white shadow-xs dark:bg-gray-100 dark:text-gray-900'
                                : 'text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800'
                        "
                    >
                        <Activity class="h-3.5 w-3.5 shrink-0" />
                        <span>Activity</span>
                    </button>
                </div>

                <!-- Tab 1: Forum Questions & Answers -->
                <div v-if="activeTab === 'forum'" class="space-y-3">
                    <!-- Sub-navigation: Questions vs Answers -->
                    <div class="flex items-center justify-between gap-2 px-1">
                        <div class="flex items-center gap-1.5">
                            <button
                                type="button"
                                @click="forumSubTab = 'questions'"
                                class="cursor-pointer rounded-lg px-2.5 py-1 text-xs font-bold transition"
                                :class="[
                                    forumSubTab === 'questions'
                                        ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400'
                                        : 'text-slate-500 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800',
                                ]"
                            >
                                Questions ({{ forumPosts?.length || 0 }})
                            </button>
                            <button
                                type="button"
                                @click="forumSubTab = 'answers'"
                                class="cursor-pointer rounded-lg px-2.5 py-1 text-xs font-bold transition"
                                :class="[
                                    forumSubTab === 'answers'
                                        ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400'
                                        : 'text-slate-500 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800',
                                ]"
                            >
                                Answers ({{ forumAnswers?.length || 0 }})
                            </button>
                        </div>

                        <Link
                            href="/forum"
                            class="group inline-flex items-center gap-1 text-[11px] font-semibold text-indigo-600 transition hover:text-indigo-700 dark:text-indigo-400"
                        >
                            <span>Visit Forum</span>
                            <ArrowRight
                                class="h-3 w-3 transition-transform group-hover:translate-x-0.5"
                            />
                        </Link>
                    </div>

                    <!-- Questions Sub-panel -->
                    <div v-if="forumSubTab === 'questions'">
                        <div
                            v-if="!forumPosts || forumPosts.length === 0"
                            class="rounded-2xl border border-dashed border-slate-200 bg-white p-7 text-center sm:rounded-3xl sm:p-8 dark:border-gray-800 dark:bg-gray-900"
                        >
                            <div
                                class="mx-auto flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 sm:h-10 sm:w-10 dark:bg-indigo-950/60 dark:text-indigo-400"
                            >
                                <HelpCircle class="h-5 w-5 stroke-[1.8]" />
                            </div>
                            <h3
                                class="mt-2 text-xs font-bold text-slate-900 dark:text-gray-100"
                            >
                                No questions asked yet
                            </h3>
                            <p
                                class="mx-auto mt-1 max-w-xs text-[11px] text-slate-500 dark:text-gray-400"
                            >
                                Questions posted to the academic forum will
                                appear here.
                            </p>
                        </div>

                        <div v-else class="space-y-2">
                            <Link
                                v-for="post in forumPosts"
                                :key="post.id"
                                :href="`/forum/questions/${post.slug}`"
                                class="group block rounded-xl border border-slate-200/80 bg-white p-3 shadow-xs transition hover:border-indigo-300 hover:shadow-xs sm:rounded-2xl sm:p-3.5 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-indigo-900/50"
                            >
                                <div
                                    class="flex items-start justify-between gap-2"
                                >
                                    <div class="min-w-0 flex-1 space-y-1">
                                        <div
                                            class="flex flex-wrap items-center gap-1.5"
                                        >
                                            <span
                                                class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-700 uppercase dark:bg-gray-800 dark:text-gray-300"
                                            >
                                                {{ post.curriculum }}
                                            </span>
                                            <span
                                                v-if="post.subject?.name"
                                                class="rounded-md bg-indigo-50 px-1.5 py-0.5 text-[10px] font-semibold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300"
                                            >
                                                {{ post.subject.name }}
                                            </span>
                                            <span
                                                v-if="post.node?.name"
                                                class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600 dark:bg-gray-800 dark:text-gray-400"
                                            >
                                                {{ post.node.name }}
                                            </span>
                                            <span
                                                v-if="post.is_answered"
                                                class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-1.5 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300"
                                            >
                                                <CheckCircle2
                                                    class="h-2.5 w-2.5"
                                                />
                                                Answered
                                            </span>
                                        </div>

                                        <h4
                                            class="text-xs font-bold text-slate-900 transition group-hover:text-indigo-600 sm:text-sm dark:text-gray-100 dark:group-hover:text-indigo-400"
                                        >
                                            {{ post.title }}
                                        </h4>
                                    </div>

                                    <ArrowUpRight
                                        class="h-3.5 w-3.5 shrink-0 text-slate-400 opacity-0 transition group-hover:opacity-100 dark:text-gray-500"
                                    />
                                </div>

                                <div
                                    class="mt-2 flex items-center gap-3 text-[10px] font-medium text-slate-400 dark:text-gray-500"
                                >
                                    <span
                                        class="flex items-center gap-0.5"
                                        :class="{
                                            'text-rose-500 dark:text-rose-400':
                                                post.vote_score < 0,
                                            'text-indigo-600 dark:text-indigo-400':
                                                post.vote_score > 0,
                                        }"
                                    >
                                        <ArrowBigDown
                                            v-if="post.vote_score < 0"
                                            class="h-3.5 w-3.5 fill-current"
                                        />
                                        <ArrowBigUp
                                            v-else
                                            class="h-3.5 w-3.5"
                                            :class="
                                                post.vote_score > 0
                                                    ? 'fill-current'
                                                    : 'fill-slate-400 dark:fill-gray-500'
                                            "
                                        />
                                        <span>{{
                                            Math.abs(post.vote_score)
                                        }}</span>
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <MessageSquare class="h-3 w-3" />
                                        {{ post.answers_count || 0 }}
                                        {{
                                            post.answers_count === 1
                                                ? 'answer'
                                                : 'answers'
                                        }}
                                    </span>
                                    <span>{{ timeAgo(post.created_at) }}</span>
                                </div>
                            </Link>

                            <div
                                v-if="
                                    stats.questionsCount >
                                    (forumPosts?.length || 0)
                                "
                                class="pt-1 text-center"
                            >
                                <Link
                                    href="/forum"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-700 hover:underline dark:text-indigo-400"
                                >
                                    <span
                                        >View all
                                        {{ stats.questionsCount }} questions in
                                        Forum &rarr;</span
                                    >
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Answers Sub-panel -->
                    <div v-else-if="forumSubTab === 'answers'">
                        <div
                            v-if="!forumAnswers || forumAnswers.length === 0"
                            class="rounded-2xl border border-dashed border-slate-200 bg-white p-7 text-center sm:rounded-3xl sm:p-8 dark:border-gray-800 dark:bg-gray-900"
                        >
                            <div
                                class="mx-auto flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 sm:h-10 sm:w-10 dark:bg-amber-950/60 dark:text-amber-400"
                            >
                                <MessageSquareCheck
                                    class="h-5 w-5 stroke-[1.8]"
                                />
                            </div>
                            <h3
                                class="mt-2 text-xs font-bold text-slate-900 dark:text-gray-100"
                            >
                                No answers contributed yet
                            </h3>
                            <p
                                class="mx-auto mt-1 max-w-xs text-[11px] text-slate-500 dark:text-gray-400"
                            >
                                Solutions provided to questions will appear
                                here.
                            </p>
                        </div>

                        <div v-else class="space-y-2">
                            <Link
                                v-for="ans in forumAnswers"
                                :key="ans.id"
                                :href="
                                    ans.post
                                        ? `/forum/questions/${ans.post.slug}`
                                        : '#'
                                "
                                class="group block rounded-xl border border-slate-200/80 bg-white p-3 shadow-xs transition hover:border-indigo-300 hover:shadow-xs sm:rounded-2xl sm:p-3.5 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-indigo-900/50"
                            >
                                <div
                                    class="flex items-start justify-between gap-2"
                                >
                                    <div class="min-w-0 flex-1 space-y-1">
                                        <div
                                            class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-500 dark:text-gray-400"
                                        >
                                            <MessageSquareCheck
                                                class="h-3.5 w-3.5 text-amber-600 dark:text-amber-400"
                                            />
                                            <span class="truncate"
                                                >Answer on:
                                                {{
                                                    ans.post?.title ||
                                                    'Question'
                                                }}</span
                                            >
                                        </div>

                                        <p
                                            class="line-clamp-2 text-xs text-slate-700 dark:text-gray-300"
                                        >
                                            {{ ans.body }}
                                        </p>
                                    </div>

                                    <ArrowUpRight
                                        class="h-3.5 w-3.5 shrink-0 text-slate-400 opacity-0 transition group-hover:opacity-100 dark:text-gray-500"
                                    />
                                </div>

                                <div
                                    class="mt-2 flex items-center justify-between text-[10px] font-medium text-slate-400 dark:text-gray-500"
                                >
                                    <span>{{ timeAgo(ans.created_at) }}</span>
                                </div>
                            </Link>

                            <div
                                v-if="
                                    stats.answersCount >
                                    (forumAnswers?.length || 0)
                                "
                                class="pt-1 text-center"
                            >
                                <Link
                                    href="/forum"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-700 hover:underline dark:text-indigo-400"
                                >
                                    <span
                                        >Explore discussions in Forum
                                        &rarr;</span
                                    >
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Compact Authored Articles -->
                <div v-else-if="activeTab === 'blogs'">
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between px-1">
                            <span
                                class="text-xs font-bold text-slate-700 dark:text-gray-300"
                            >
                                Published Guides & Notes
                            </span>
                            <Link
                                :href="`/blogs?q=${encodeURIComponent(profileUser.name)}`"
                                class="group inline-flex items-center gap-1 text-[11px] font-semibold text-indigo-600 transition hover:text-indigo-700 dark:text-indigo-400"
                            >
                                <span>See all blogs</span>
                                <ArrowRight
                                    class="h-3 w-3 transition-transform group-hover:translate-x-0.5"
                                />
                            </Link>
                        </div>

                        <div
                            v-if="blogs.length === 0"
                            class="rounded-2xl border border-dashed border-slate-200 bg-white p-7 text-center sm:rounded-3xl sm:p-8 dark:border-gray-800 dark:bg-gray-900"
                        >
                            <div
                                class="mx-auto flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 sm:h-10 sm:w-10 dark:bg-blue-950/60 dark:text-blue-400"
                            >
                                <FileText class="h-5 w-5 stroke-[1.8]" />
                            </div>
                            <h3
                                class="mt-2 text-xs font-bold text-slate-900 dark:text-gray-100"
                            >
                                No published articles yet
                            </h3>
                            <p
                                class="mx-auto mt-1 max-w-xs text-[11px] text-slate-500 dark:text-gray-400"
                            >
                                Educational blogs written by this author will
                                appear here.
                            </p>
                        </div>

                        <div v-else class="space-y-2">
                            <Link
                                v-for="blog in blogs"
                                :key="blog.id"
                                :href="`/blogs/${blog.slug}`"
                                class="group flex items-center gap-3 rounded-xl border border-slate-200/80 bg-white p-2.5 shadow-xs transition hover:border-indigo-300 hover:shadow-xs sm:rounded-2xl sm:p-3 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-indigo-900/50"
                            >
                                <!-- Compact Thumbnail -->
                                <div
                                    class="h-14 w-20 shrink-0 overflow-hidden rounded-lg bg-slate-100 sm:h-16 sm:w-24 sm:rounded-xl dark:bg-gray-800"
                                >
                                    <img
                                        :src="
                                            blog.featured_image ||
                                            'https://placehold.co/400x250'
                                        "
                                        :alt="blog.title"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                        loading="lazy"
                                    />
                                </div>

                                <!-- Info -->
                                <div
                                    class="min-w-0 flex-1 space-y-0.5 sm:space-y-1"
                                >
                                    <h3
                                        class="line-clamp-1 text-xs font-bold text-slate-900 group-hover:text-indigo-600 dark:text-gray-100 dark:group-hover:text-indigo-400"
                                    >
                                        {{ blog.title }}
                                    </h3>
                                    <p
                                        v-if="blog.excerpt"
                                        class="line-clamp-1 text-[11px] text-slate-500 dark:text-gray-400"
                                    >
                                        {{ blog.excerpt }}
                                    </p>
                                    <div
                                        class="flex items-center gap-2.5 text-[10px] font-medium text-slate-400 dark:text-gray-500"
                                    >
                                        <span class="flex items-center gap-1">
                                            <Eye class="h-3 w-3" />
                                            {{ blog.views }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <Heart class="h-3 w-3" />
                                            {{ blog.reactions_count }}
                                        </span>
                                    </div>
                                </div>

                                <ArrowUpRight
                                    class="h-3.5 w-3.5 shrink-0 text-slate-400 opacity-0 transition group-hover:opacity-100 dark:text-gray-500"
                                />
                            </Link>

                            <div
                                v-if="stats.blogsCount > (blogs?.length || 0)"
                                class="pt-1 text-center"
                            >
                                <Link
                                    :href="`/blogs?q=${encodeURIComponent(profileUser.name)}`"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-700 hover:underline dark:text-indigo-400"
                                >
                                    <span
                                        >View all
                                        {{ stats.blogsCount }} articles
                                        &rarr;</span
                                    >
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 3: Recent Activity -->
                <div v-else-if="activeTab === 'activity'">
                    <div
                        v-if="totalActivitiesCount === 0"
                        class="rounded-2xl border border-dashed border-slate-200 bg-white p-7 text-center sm:rounded-3xl sm:p-8 dark:border-gray-800 dark:bg-gray-900"
                    >
                        <div
                            class="mx-auto flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 sm:h-10 sm:w-10 dark:bg-indigo-950/60 dark:text-indigo-400"
                        >
                            <Activity class="h-5 w-5 stroke-[1.8]" />
                        </div>
                        <h3
                            class="mt-2 text-xs font-bold text-slate-900 dark:text-gray-100"
                        >
                            No recent activity yet
                        </h3>
                        <p
                            class="mx-auto mt-1 max-w-xs text-[11px] text-slate-500 dark:text-gray-400"
                        >
                            Forum questions, answers, and study notes will show
                            up here.
                        </p>
                    </div>

                    <div v-else class="space-y-2">
                        <div
                            v-for="(item, idx) in sortedActivities"
                            :key="item.type + '-' + idx"
                            class="flex flex-col gap-1 rounded-xl border border-slate-100 bg-white p-2.5 shadow-xs sm:rounded-2xl sm:p-3 dark:border-gray-800 dark:bg-gray-900"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex min-w-0 items-center gap-2">
                                    <!-- Forum Post -->
                                    <div
                                        v-if="item.type === 'forum_post'"
                                        class="flex h-6.5 w-6.5 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                                    >
                                        <HelpCircle
                                            class="h-3.5 w-3.5 stroke-[2.2]"
                                        />
                                    </div>

                                    <!-- Forum Answer -->
                                    <div
                                        v-else-if="item.type === 'forum_answer'"
                                        class="flex h-6.5 w-6.5 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400"
                                    >
                                        <MessageSquareCheck
                                            class="h-3.5 w-3.5 stroke-[2.2]"
                                        />
                                    </div>

                                    <!-- Folder -->
                                    <div
                                        v-else-if="item.type === 'folder'"
                                        class="flex h-6.5 w-6.5 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400"
                                    >
                                        <Folder
                                            class="h-3.5 w-3.5 stroke-[2.2]"
                                        />
                                    </div>

                                    <!-- Upload -->
                                    <div
                                        v-else-if="item.type === 'upload'"
                                        class="flex h-6.5 w-6.5 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400"
                                    >
                                        <UploadCloud class="h-3.5 w-3.5" />
                                    </div>

                                    <!-- Reaction / Like -->
                                    <div
                                        v-else-if="item.type === 'reaction'"
                                        class="flex h-6.5 w-6.5 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-500 dark:bg-rose-950/60 dark:text-rose-400"
                                    >
                                        <Heart
                                            class="h-3.5 w-3.5 fill-rose-500"
                                        />
                                    </div>

                                    <!-- Comment -->
                                    <div
                                        v-else-if="item.type === 'comment'"
                                        class="flex h-6.5 w-6.5 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                                    >
                                        <MessageSquare class="h-3.5 w-3.5" />
                                    </div>

                                    <!-- Appreciation -->
                                    <div
                                        v-else-if="item.type === 'appreciation'"
                                        class="flex h-6.5 w-6.5 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-500 dark:bg-rose-950/60 dark:text-rose-400"
                                    >
                                        <Heart
                                            class="h-3.5 w-3.5 fill-rose-500"
                                        />
                                    </div>

                                    <div class="min-w-0 flex-1 truncate">
                                        <span
                                            class="text-xs text-slate-500 dark:text-gray-400"
                                        >
                                            <template
                                                v-if="
                                                    item.type === 'forum_post'
                                                "
                                                >Asked in Forum
                                            </template>
                                            <template
                                                v-else-if="
                                                    item.type === 'forum_answer'
                                                "
                                                >Answered
                                            </template>
                                            <template
                                                v-else-if="
                                                    item.type === 'folder'
                                                "
                                                >Created folder
                                            </template>
                                            <template
                                                v-else-if="
                                                    item.type === 'upload'
                                                "
                                                >Uploaded
                                            </template>
                                            <template
                                                v-else-if="
                                                    item.type === 'reaction'
                                                "
                                                >Liked
                                            </template>
                                            <template
                                                v-else-if="
                                                    item.type === 'comment'
                                                "
                                                >Commented on
                                            </template>
                                            <template
                                                v-else-if="
                                                    item.type === 'appreciation'
                                                "
                                                >Appreciated
                                            </template>
                                        </span>

                                        <Link
                                            v-if="item.url"
                                            :href="item.url"
                                            class="text-xs font-bold text-slate-900 hover:text-indigo-600 dark:text-gray-100 dark:hover:text-indigo-400"
                                        >
                                            {{ item.title }}
                                        </Link>
                                        <span
                                            v-else
                                            class="text-xs font-bold text-slate-900 dark:text-gray-100"
                                        >
                                            {{ item.title }}
                                        </span>

                                        <span
                                            v-if="item.subtitle"
                                            class="ml-1 text-[10px] text-slate-400 dark:text-gray-500"
                                        >
                                            ({{ item.subtitle }})
                                        </span>
                                        <span
                                            v-else-if="item.username"
                                            class="ml-1 text-[10px] text-slate-400 dark:text-gray-500"
                                        >
                                            (@{{ item.username }})
                                        </span>
                                    </div>
                                </div>

                                <span
                                    class="shrink-0 pl-2 text-[10px] font-medium text-slate-400 dark:text-gray-500"
                                >
                                    {{ item.created_at }}
                                </span>
                            </div>

                            <!-- Answer text preview for forum answer items -->
                            <p
                                v-if="
                                    item.type === 'forum_answer' && item.content
                                "
                                class="line-clamp-2 pl-8.5 text-[11px] text-slate-600 dark:text-gray-400"
                            >
                                {{ item.content }}
                            </p>

                            <!-- Comment quote -->
                            <p
                                v-if="item.type === 'comment' && item.content"
                                class="line-clamp-1 pl-8.5 text-xs text-slate-600 italic dark:text-gray-300"
                            >
                                "{{ item.content }}"
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Suggested / Discover Other Learners & Contributors -->
            <div
                v-if="suggestedUsers && suggestedUsers.length > 0"
                class="space-y-3 pt-2"
            >
                <div class="flex items-center justify-between px-1">
                    <div
                        class="flex items-center gap-1.5 text-xs font-bold text-slate-700 dark:text-gray-300"
                    >
                        <Users
                            class="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400"
                        />
                        <span>Discover People</span>
                    </div>
                    <Link
                        href="/about-us"
                        class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                    >
                        Meet team →
                    </Link>
                </div>

                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                    <Link
                        v-for="person in suggestedUsers"
                        :key="person.id"
                        :href="`/u/${person.username}`"
                        class="group flex items-center justify-between rounded-xl border border-slate-200/80 bg-white p-3 shadow-xs transition hover:border-indigo-300 hover:bg-slate-50/70 sm:rounded-2xl dark:border-gray-800 dark:bg-gray-900 dark:hover:border-indigo-900/60 dark:hover:bg-gray-800/50"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="h-10 w-10 shrink-0 overflow-hidden rounded-xl bg-indigo-50 shadow-2xs ring-2 ring-slate-100 sm:h-11 sm:w-11 dark:bg-indigo-950/60 dark:ring-gray-800"
                            >
                                <img
                                    v-if="person.image_url"
                                    :src="person.image_url"
                                    :alt="person.name"
                                    class="h-full w-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center bg-gradient-to-br from-indigo-500 to-indigo-600 text-sm font-black text-white"
                                >
                                    {{ person.name.charAt(0).toUpperCase() }}
                                </div>
                            </div>
                            <div class="min-w-0 flex-1 space-y-0.5">
                                <div class="flex items-center gap-1.5">
                                    <p
                                        class="truncate text-xs font-bold text-slate-900 group-hover:text-indigo-600 dark:text-gray-100 dark:group-hover:text-indigo-400"
                                    >
                                        {{ person.name }}
                                    </p>
                                    <VerifiedBadge v-if="person.is_verified" />
                                </div>
                                <p
                                    v-if="person.institution"
                                    class="truncate text-[10px] font-medium text-slate-400 dark:text-gray-500"
                                >
                                    {{ person.institution }}
                                </p>
                                <p
                                    v-else
                                    class="truncate text-[10px] font-semibold text-slate-400 dark:text-gray-500"
                                >
                                    @{{ person.username }}
                                </p>
                            </div>
                        </div>

                        <ArrowUpRight
                            class="h-3.5 w-3.5 shrink-0 text-slate-400 opacity-0 transition group-hover:opacity-100 dark:text-gray-500"
                        />
                    </Link>
                </div>
            </div>
        </div>
    </div>

    <!-- Appreciators Modal -->
    <Teleport to="body">
        <div
            v-if="showAppreciatorsModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div
                @click="showAppreciatorsModal = false"
                class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity"
            ></div>

            <div
                class="relative w-full max-w-sm overflow-hidden rounded-3xl border border-slate-100 bg-white p-5 shadow-2xl transition-all sm:p-6 dark:border-gray-800 dark:bg-gray-900"
            >
                <button
                    @click="showAppreciatorsModal = false"
                    class="absolute top-3.5 right-3.5 cursor-pointer rounded-lg p-1 text-slate-400 hover:text-slate-600 dark:text-gray-500 dark:hover:text-gray-300"
                >
                    <X class="h-4 w-4" />
                </button>

                <div class="mb-4 flex items-center gap-2.5">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-rose-50 text-rose-500 dark:bg-rose-950/60 dark:text-rose-400"
                    >
                        <Heart class="h-4 w-4 fill-rose-500" />
                    </div>
                    <div>
                        <h3
                            class="text-sm font-bold text-slate-900 dark:text-gray-100"
                        >
                            Appreciated by
                        </h3>
                        <p
                            class="text-[11px] font-medium text-slate-500 dark:text-gray-400"
                        >
                            {{ localAppreciationsCount }} community
                            {{
                                localAppreciationsCount === 1
                                    ? 'member'
                                    : 'members'
                            }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="appreciators && appreciators.length > 0"
                    class="-mx-1 max-h-72 divide-y divide-slate-100 overflow-y-auto px-1 dark:divide-gray-800/80"
                >
                    <UserListItem
                        v-for="person in appreciators"
                        :key="person.id"
                        :user="person"
                        theme="rose"
                        @click="showAppreciatorsModal = false"
                    />

                    <div
                        v-if="localAppreciationsCount > appreciators.length"
                        class="py-3 text-center text-xs font-medium text-slate-500 dark:text-gray-400"
                    >
                        and
                        {{ localAppreciationsCount - appreciators.length }}
                        more...
                    </div>
                </div>

                <div
                    v-else
                    class="py-6 text-center text-xs text-slate-400 dark:text-gray-500"
                >
                    No appreciators yet.
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Appreciating Modal -->
    <Teleport to="body">
        <div
            v-if="showAppreciatingModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div
                @click="showAppreciatingModal = false"
                class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity"
            ></div>

            <div
                class="relative w-full max-w-sm overflow-hidden rounded-3xl border border-slate-100 bg-white p-5 shadow-2xl transition-all sm:p-6 dark:border-gray-800 dark:bg-gray-900"
            >
                <button
                    @click="showAppreciatingModal = false"
                    class="absolute top-3.5 right-3.5 cursor-pointer rounded-lg p-1 text-slate-400 hover:text-slate-600 dark:text-gray-500 dark:hover:text-gray-300"
                >
                    <X class="h-4 w-4" />
                </button>

                <div class="mb-4 flex items-center gap-2.5">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100 text-slate-600 dark:bg-gray-800 dark:text-gray-400"
                    >
                        <Heart class="h-4 w-4 fill-rose-500 text-rose-500" />
                    </div>
                    <div>
                        <h3
                            class="text-sm font-bold text-slate-900 dark:text-gray-100"
                        >
                            Appreciating
                        </h3>
                        <p
                            class="text-[11px] font-medium text-slate-500 dark:text-gray-400"
                        >
                            {{ appreciatingCount }} community
                            {{ appreciatingCount === 1 ? 'member' : 'members' }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="appreciating && appreciating.length > 0"
                    class="-mx-1 max-h-72 divide-y divide-slate-100 overflow-y-auto px-1 dark:divide-gray-800/80"
                >
                    <UserListItem
                        v-for="person in appreciating"
                        :key="person.id"
                        :user="person"
                        theme="indigo"
                        @click="showAppreciatingModal = false"
                    />

                    <div
                        v-if="appreciatingCount > appreciating.length"
                        class="py-3 text-center text-xs font-medium text-slate-500 dark:text-gray-400"
                    >
                        and
                        {{ appreciatingCount - appreciating.length }}
                        more...
                    </div>
                </div>

                <div
                    v-else
                    class="py-6 text-center text-xs text-slate-400 dark:text-gray-500"
                >
                    Not appreciating any users yet.
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Guest Sign-in Dialog Modal -->
    <Teleport to="body">
        <div
            v-if="showGuestModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div
                @click="showGuestModal = false"
                class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity"
            ></div>

            <div
                class="relative w-full max-w-sm overflow-hidden rounded-3xl border border-slate-100 bg-white p-6 text-center shadow-2xl transition-all sm:p-7 dark:border-gray-800 dark:bg-gray-900"
            >
                <button
                    @click="showGuestModal = false"
                    class="absolute top-3.5 right-3.5 cursor-pointer rounded-lg p-1 text-slate-400 hover:text-slate-600 dark:text-gray-500 dark:hover:text-gray-300"
                >
                    <X class="h-4 w-4" />
                </button>

                <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400"
                >
                    <Heart class="h-6 w-6 fill-rose-500" />
                </div>

                <h3
                    class="mt-3.5 text-base font-bold text-slate-900 dark:text-gray-100"
                >
                    Sign in to Appreciate
                </h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">
                    You need to be logged in to send appreciation and support
                    fellow students and contributors.
                </p>

                <div class="mt-5 flex gap-2.5">
                    <button
                        type="button"
                        @click="showGuestModal = false"
                        class="flex-1 cursor-pointer rounded-xl border border-slate-200 py-2.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Cancel
                    </button>
                    <Link
                        href="/login"
                        class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-slate-900 py-2.5 text-xs font-bold text-white shadow-xs transition hover:bg-slate-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200"
                    >
                        <LogIn class="h-3.5 w-3.5" />
                        <span>Sign In</span>
                    </Link>
                </div>
            </div>
        </div>
    </Teleport>
</template>
