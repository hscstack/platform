<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowBigUp,
    ArrowBigDown,
    ArrowRight,
    ArrowUpRight,
    BadgeCheck,
    Calendar,
    CheckCircle2,
    ChevronRight,
    Edit3,
    Eye,
    Facebook,
    FileText,
    FlaskConical,
    Github,
    GraduationCap,
    Heart,
    HelpCircle,
    Instagram,
    Loader2,
    Lock,
    LogIn,
    MessageSquare,
    MessageSquareCheck,
    NotebookPen,
    Users,
    Video,
    X,
    Zap,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import BaseModal from '@/components/BaseModal.vue';
import EmptyState from '@/components/EmptyState.vue';
import ImageViewerModal from '@/components/ImageViewerModal.vue';
import StudyHeatmap from '@/components/tracker/StudyHeatmap.vue';
import type {
    HeatmapItem,
    TrackerStats,
} from '@/components/tracker/StudyHeatmap.vue';
import SyllabusBreakdownModal from '@/components/tracker/SyllabusBreakdownModal.vue';
import SyllabusShareModal from '@/components/tracker/SyllabusShareModal.vue';
import UserListItem from '@/components/UserListItem.vue';
import VerifiedBadge from '@/components/VerifiedBadge.vue';
import { formatTimeAgo } from '@/lib/useDate';

const props = defineProps<{
    profileUser: {
        id: number;
        name: string;
        username: string;
        about: string | null;
        institution: string | null;
        curriculum?: 'hsc' | 'ssc';
        image_url: string | null;
        facebook: string | null;
        instagram: string | null;
        github: string | null;
        created_at: string;
        is_verified?: boolean;
        is_online?: boolean;
        last_active_at?: string | null;
    };
    appreciationsCount: number;
    appreciatingCount: number;
    isAppreciated: boolean;
    isLocked?: boolean;
    lockReason?: 'private' | 'appreciators_only' | null;
    activityPrivacy?: string;
    pokeData?: {
        enabled: boolean;
        canPoke: boolean;
        isCooldown: boolean;
        cooldownUntil: number | null;
        presets: Array<{
            id: string;
            icon: string;
            message: string;
        }>;
    };
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
        unappreciated?: boolean;
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
    notes?: Array<{
        id: number;
        title: string;
        resource_type: string;
        items_count?: number;
        vote_score?: number;
        created_at: string;
        node?: {
            id: number;
            name: string;
            slug: string;
            subject?: {
                id: number;
                name: string;
                course: string;
                slug: string;
            } | null;
        } | null;
    }>;
    notesCount?: number;
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
    studyHeatmap?: {
        heatmapData: HeatmapItem[];
        stats: TrackerStats;
    };
    syllabusProgress?: {
        course: string;
        group?: string;
        overallPercent: number;
        completedChapters: number;
        totalChapters: number;
        subjects: Array<{
            id: number;
            name: string;
            slug: string;
            course: string;
            tailwind_format: string;
            icon: string;
            completed: number;
            total: number;
            percent: number;
            chapters?: Array<{
                id: number;
                name: string;
                weight: number;
                is_completed: boolean;
            }>;
        }>;
    };
}>();

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);
const isOwnProfile = computed(
    () => currentUser.value?.id === props.profileUser.id,
);

const showSyllabusModal = ref(false);
const showShareModal = ref(false);
const showVerifiedPrompt = ref(false);
const verifiedBadgeContainerRef = ref<HTMLElement | null>(null);

const handleVerifiedClickOutside = (e: MouseEvent) => {
    if (
        showVerifiedPrompt.value &&
        verifiedBadgeContainerRef.value &&
        !verifiedBadgeContainerRef.value.contains(e.target as Node)
    ) {
        showVerifiedPrompt.value = false;
    }
};

const handleVerifiedKeyDown = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && showVerifiedPrompt.value) {
        showVerifiedPrompt.value = false;
    }
};

onMounted(() => {
    if (typeof window !== 'undefined') {
        window.addEventListener('click', handleVerifiedClickOutside);
        window.addEventListener('keydown', handleVerifiedKeyDown);
    }
});

onBeforeUnmount(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('click', handleVerifiedClickOutside);
        window.removeEventListener('keydown', handleVerifiedKeyDown);
    }
});

const activeTab = ref<'notes' | 'forum' | 'blogs'>('notes');
const forumSubTab = ref<'questions' | 'answers'>('questions');

const localIsAppreciated = ref(props.isAppreciated);
const localAppreciationsCount = ref(props.appreciationsCount);
const showAppreciatorsModal = ref(false);
const showAppreciatingModal = ref(false);
const showGuestModal = ref(false);
const guestModalAction = ref<'appreciate' | 'poke'>('appreciate');
const showAvatarModal = ref(false);

// Paginated appreciators
const appreciatorsList = ref<
    Array<{
        id: number;
        name: string;
        username: string;
        image_path: string | null;
        institution: string | null;
        is_verified?: boolean;
    }>
>([...(props.appreciators || [])]);
const appreciatorsPage = ref(1);
const appreciatorsHasMore = ref(
    props.appreciationsCount > (props.appreciators?.length || 0),
);
const isLoadingMoreAppreciators = ref(false);

watch(
    () => props.appreciators,
    (newVal) => {
        appreciatorsList.value = [...(newVal || [])];
        appreciatorsPage.value = 1;
        appreciatorsHasMore.value =
            localAppreciationsCount.value > appreciatorsList.value.length;
    },
    { deep: true },
);

const loadMoreAppreciators = async () => {
    if (isLoadingMoreAppreciators.value || !appreciatorsHasMore.value) {
        return;
    }

    isLoadingMoreAppreciators.value = true;
    const nextPage = appreciatorsPage.value + 1;

    try {
        const res = await fetch(
            `/u/${props.profileUser.id}/appreciators?page=${nextPage}`,
            {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            },
        );

        if (res.ok) {
            const data = await res.json();
            const newUsers = data.users || [];
            appreciatorsList.value.push(...newUsers);
            appreciatorsPage.value = data.current_page || nextPage;
            appreciatorsHasMore.value = Boolean(data.has_more);
        }
    } catch (e) {
        console.error('Failed to load more appreciators:', e);
    } finally {
        isLoadingMoreAppreciators.value = false;
    }
};

// Paginated appreciating
const appreciatingList = ref<
    Array<{
        id: number;
        name: string;
        username: string;
        image_path: string | null;
        institution: string | null;
        is_verified?: boolean;
        unappreciated?: boolean;
    }>
>([...(props.appreciating || [])]);
const appreciatingPage = ref(1);
const appreciatingHasMore = ref(
    props.appreciatingCount > (props.appreciating?.length || 0),
);
const isLoadingMoreAppreciating = ref(false);

watch(
    () => props.appreciating,
    (newVal) => {
        appreciatingList.value = [...(newVal || [])];
        appreciatingPage.value = 1;
        appreciatingHasMore.value =
            props.appreciatingCount > appreciatingList.value.length;
    },
    { deep: true },
);

const loadMoreAppreciating = async () => {
    if (isLoadingMoreAppreciating.value || !appreciatingHasMore.value) {
        return;
    }

    isLoadingMoreAppreciating.value = true;
    const nextPage = appreciatingPage.value + 1;

    try {
        const res = await fetch(
            `/u/${props.profileUser.id}/appreciating?page=${nextPage}`,
            {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            },
        );

        if (res.ok) {
            const data = await res.json();
            const newUsers = data.users || [];
            appreciatingList.value.push(...newUsers);
            appreciatingPage.value = data.current_page || nextPage;
            appreciatingHasMore.value = Boolean(data.has_more);
        }
    } catch (e) {
        console.error('Failed to load more appreciating users:', e);
    } finally {
        isLoadingMoreAppreciating.value = false;
    }
};

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
        appreciatorsHasMore.value = val > appreciatorsList.value.length;
    },
);

const showPokeModal = ref(false);
const selectedPresetId = ref<string>('');
const isSubmittingPoke = ref(false);
const localPokeCooldown = ref(props.pokeData?.isCooldown ?? false);

watch(
    () => props.pokeData?.isCooldown,
    (val) => {
        localPokeCooldown.value = val ?? false;
    },
);

const handleOpenPokeModal = () => {
    if (!currentUser.value) {
        guestModalAction.value = 'poke';
        showGuestModal.value = true;

        return;
    }

    if (props.pokeData?.presets && props.pokeData.presets.length > 0) {
        if (!selectedPresetId.value) {
            selectedPresetId.value = props.pokeData.presets[0].id;
        }
    }

    showPokeModal.value = true;
};

const handleSendPoke = () => {
    if (!selectedPresetId.value) {
        return;
    }

    isSubmittingPoke.value = true;
    router.post(
        `/u/${props.profileUser.id}/poke`,
        {
            preset_id: selectedPresetId.value,
        },
        {
            preserveScroll: true,
            onSuccess: (page) => {
                if ((page.props as any).flash?.success) {
                    showPokeModal.value = false;
                    localPokeCooldown.value = true;
                }
            },
            onFinish: () => {
                isSubmittingPoke.value = false;
            },
        },
    );
};

const handleAppreciate = () => {
    if (!currentUser.value) {
        guestModalAction.value = 'appreciate';
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
            preserveState: !props.isLocked,
            except: ['suggestedUsers'],
            onError: () => {
                localIsAppreciated.value = props.isAppreciated;
                localAppreciationsCount.value = props.appreciationsCount;
            },
        },
    );
};

const timeAgo = formatTimeAgo;
const formatGroupName = (group?: string) => {
    if (!group) {
        return '';
    }

    return group.charAt(0).toUpperCase() + group.slice(1);
};
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
                class="relative rounded-2xl border border-slate-200/80 bg-white p-4.5 shadow-xs sm:rounded-3xl sm:p-7 dark:border-gray-800 dark:bg-gray-900"
            >
                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between sm:gap-5"
                >
                    <!-- Left: Avatar & Bio Details -->
                    <div class="flex min-w-0 items-start gap-3.5 sm:gap-4.5">
                        <!-- Avatar -->
                        <div class="relative shrink-0">
                            <div
                                @click="
                                    profileUser.image_url
                                        ? (showAvatarModal = true)
                                        : undefined
                                "
                                :class="[
                                    'h-16 w-16 overflow-hidden rounded-2xl shadow-sm ring-4 ring-slate-100 sm:h-20 sm:w-20 dark:ring-gray-800',
                                    profileUser.image_url
                                        ? 'cursor-pointer transition hover:opacity-90 hover:ring-indigo-300 active:scale-95 dark:hover:ring-indigo-700'
                                        : '',
                                ]"
                                :title="
                                    profileUser.image_url
                                        ? 'View profile picture'
                                        : undefined
                                "
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

                                <div
                                    v-if="profileUser.is_verified"
                                    ref="verifiedBadgeContainerRef"
                                    class="relative inline-flex items-center"
                                >
                                    <button
                                        type="button"
                                        @click.stop="
                                            showVerifiedPrompt =
                                                !showVerifiedPrompt
                                        "
                                        class="inline-flex cursor-pointer rounded-full transition-transform hover:scale-110 focus:outline-none"
                                        :aria-expanded="showVerifiedPrompt"
                                        aria-haspopup="dialog"
                                        aria-label="Verified contributor details"
                                        title="Verified Contributor"
                                    >
                                        <VerifiedBadge size="h-5 w-5" />
                                    </button>

                                    <!-- Floating verification card / popover -->
                                    <Transition
                                        enter-active-class="transition duration-150 ease-out"
                                        enter-from-class="opacity-0 translate-y-1 scale-95"
                                        enter-to-class="opacity-100 translate-y-0 scale-100"
                                        leave-active-class="transition duration-100 ease-in"
                                        leave-from-class="opacity-100 translate-y-0 scale-100"
                                        leave-to-class="opacity-0 translate-y-1 scale-95"
                                    >
                                        <div
                                            v-if="showVerifiedPrompt"
                                            class="absolute top-full -left-20 z-50 mt-2 w-64 max-w-[calc(100vw-4rem)] rounded-2xl border border-slate-200/90 bg-white/95 p-3.5 shadow-xl shadow-slate-900/10 backdrop-blur-xl sm:left-0 sm:w-80 dark:border-gray-800 dark:bg-gray-900/95 dark:shadow-2xl"
                                            role="dialog"
                                            aria-label="Verified contributor information"
                                        >
                                            <div
                                                class="flex items-start gap-2.5"
                                            >
                                                <div
                                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400"
                                                >
                                                    <BadgeCheck
                                                        class="h-4 w-4"
                                                    />
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div
                                                        class="flex items-center justify-between gap-1.5"
                                                    >
                                                        <p
                                                            class="truncate text-xs font-bold text-slate-900 dark:text-gray-100"
                                                        >
                                                            {{
                                                                profileUser.name
                                                            }}
                                                        </p>
                                                        <span
                                                            class="inline-flex shrink-0 items-center rounded-full bg-blue-50 px-1.5 py-0.5 text-[9px] font-semibold text-blue-700 dark:bg-blue-950/60 dark:text-blue-300"
                                                        >
                                                            Verified
                                                        </span>
                                                    </div>
                                                    <p
                                                        class="mt-1 text-[11px] leading-relaxed text-slate-500 dark:text-gray-400"
                                                    >
                                                        প্ল্যাটফর্মে মানসম্মত
                                                        কন্টেন্ট ও রিসোর্স শেয়ার
                                                        করার জন্য HSCStack
                                                        কর্তৃক এই অ্যাকাউন্টটি
                                                        যাচাইকৃত।
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </Transition>
                                </div>
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
                                class="flex items-start gap-1.5 pt-0.5 text-xs font-medium text-slate-600 dark:text-gray-300"
                            >
                                <GraduationCap
                                    class="mt-0.5 h-3.5 w-3.5 shrink-0 text-slate-400 dark:text-gray-500"
                                />
                                <span class="leading-snug break-words">{{
                                    profileUser.institution
                                }}</span>
                            </div>

                            <!-- Online / Last Active Status -->
                            <div
                                class="flex items-center gap-1.5 pt-0.5 text-xs font-medium"
                                :class="
                                    profileUser.is_online
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-slate-500 dark:text-gray-400'
                                "
                            >
                                <span class="relative flex h-2 w-2">
                                    <span
                                        v-if="profileUser.is_online"
                                        class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                                    ></span>
                                    <span
                                        class="relative inline-flex h-2 w-2 rounded-full"
                                        :class="
                                            profileUser.is_online
                                                ? 'bg-emerald-500'
                                                : 'bg-slate-300 dark:bg-gray-600'
                                        "
                                    ></span>
                                </span>
                                <span v-if="profileUser.is_online">Online</span>
                                <span v-else-if="profileUser.last_active_at"
                                    >Active
                                    {{
                                        formatTimeAgo(
                                            profileUser.last_active_at,
                                        )
                                    }}</span
                                >
                                <span v-else>Long time ago</span>
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

                        <template v-else>
                            <button
                                v-if="pokeData?.enabled && pokeData?.canPoke"
                                @click="handleOpenPokeModal"
                                type="button"
                                :disabled="localPokeCooldown"
                                class="group inline-flex h-8.5 cursor-pointer items-center gap-1.5 rounded-xl px-3 text-xs font-bold transition-all duration-150 select-none active:scale-95 disabled:cursor-not-allowed disabled:opacity-60 sm:h-9"
                                :class="[
                                    localPokeCooldown
                                        ? 'border border-slate-200 bg-slate-100 text-slate-400 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-500'
                                        : 'border border-amber-200 bg-amber-50/70 text-amber-700 shadow-xs hover:border-amber-300 hover:bg-amber-100/80 hover:text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-300 dark:hover:bg-amber-950/70',
                                ]"
                                :title="
                                    localPokeCooldown
                                        ? 'Poked recently (Cooldown active)'
                                        : 'Send a Poke to ' + profileUser.name
                                "
                            >
                                <Zap
                                    class="h-3.5 w-3.5 fill-amber-500/30 text-amber-500"
                                />
                                <span>{{
                                    localPokeCooldown ? 'Poked' : 'Poke'
                                }}</span>
                            </button>

                            <button
                                @click="handleAppreciate"
                                type="button"
                                class="group inline-flex h-8.5 cursor-pointer items-center gap-1.5 rounded-xl px-3.5 text-xs font-bold transition-all duration-150 select-none active:scale-95 sm:h-9"
                                :class="[
                                    localIsAppreciated
                                        ? 'border border-rose-200 bg-rose-50 text-rose-600 shadow-xs dark:border-rose-900/60 dark:bg-rose-950/60 dark:text-rose-400'
                                        : 'border border-slate-200 bg-white text-slate-700 shadow-xs hover:border-rose-200 hover:bg-rose-50/40 hover:text-rose-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-rose-900/50 dark:hover:bg-rose-950/30 dark:hover:text-rose-400',
                                ]"
                                :title="
                                    localIsAppreciated
                                        ? 'Appreciating (click to remove)'
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
                                        ? 'Appreciating'
                                        : 'Appreciate'
                                }}</span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Bio / About -->
                <div
                    v-if="profileUser.about"
                    class="mt-4 rounded-xl border border-slate-100 bg-slate-50/60 p-3 text-xs leading-relaxed text-slate-600 sm:rounded-2xl sm:p-3.5 dark:border-gray-800/80 dark:bg-gray-800/30 dark:text-gray-300"
                >
                    <p class="whitespace-pre-line">{{ profileUser.about }}</p>
                </div>

                <!-- Appreciators & Appreciating Minimal Row -->
                <div
                    class="mt-3.5 flex items-center gap-2.5 text-xs text-slate-500 dark:text-gray-400"
                >
                    <button
                        type="button"
                        @click="showAppreciatorsModal = true"
                        class="inline-flex cursor-pointer items-center gap-1 transition hover:text-slate-900 hover:underline dark:hover:text-gray-200"
                    >
                        <span
                            class="font-bold text-slate-900 dark:text-gray-100"
                            >{{ localAppreciationsCount }}</span
                        >
                        <span>{{
                            localAppreciationsCount === 1
                                ? 'Appreciator'
                                : 'Appreciators'
                        }}</span>
                    </button>

                    <span class="text-slate-300 select-none dark:text-gray-600"
                        >·</span
                    >

                    <button
                        type="button"
                        @click="showAppreciatingModal = true"
                        class="inline-flex cursor-pointer items-center gap-1 transition hover:text-slate-900 hover:underline dark:hover:text-gray-200"
                    >
                        <span
                            class="font-bold text-slate-900 dark:text-gray-100"
                            >{{ appreciatingCount }}</span
                        >
                        <span>Appreciating</span>
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

            <!-- Unlocked Activity & Heatmap Section -->
            <template v-if="!isLocked">
                <!-- Syllabus Completion Progress Card (Ultra-Minimal) -->
                <div
                    v-if="
                        syllabusProgress && syllabusProgress.totalChapters > 0
                    "
                    @click="showSyllabusModal = true"
                    class="group relative flex cursor-pointer items-center justify-between gap-3 overflow-hidden rounded-xl border border-slate-200/80 bg-white px-4 py-3 shadow-2xs transition-all duration-150 hover:border-slate-300 sm:rounded-2xl sm:px-4.5 sm:py-3.5 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-gray-700"
                >
                    <!-- Left: Percentage + Label + Meta -->
                    <div class="flex min-w-0 items-center gap-2 sm:gap-2.5">
                        <span
                            class="text-xs font-black text-indigo-600 sm:text-sm dark:text-indigo-400"
                        >
                            {{ syllabusProgress.overallPercent }}%
                        </span>

                        <span
                            class="text-slate-300 select-none dark:text-gray-700"
                            >·</span
                        >

                        <span
                            class="truncate text-xs font-semibold text-slate-800 dark:text-gray-200"
                        >
                            Syllabus Completed ({{
                                syllabusProgress.course.toUpperCase()
                            }}{{
                                syllabusProgress.group
                                    ? ' ' +
                                      formatGroupName(syllabusProgress.group)
                                    : ''
                            }})
                        </span>

                        <span
                            class="hidden text-xs text-slate-400 sm:inline dark:text-gray-500"
                        >
                            ({{ syllabusProgress.completedChapters }}/{{
                                syllabusProgress.totalChapters
                            }}
                            chapters)
                        </span>
                    </div>

                    <!-- Right: Inline Progress Bar + Chevron -->
                    <div class="flex shrink-0 items-center gap-2.5">
                        <div
                            class="h-1.5 w-20 overflow-hidden rounded-full bg-slate-100 sm:w-32 dark:bg-gray-800"
                        >
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-emerald-400 transition-all duration-500"
                                :style="{
                                    width: `${syllabusProgress.overallPercent}%`,
                                }"
                            />
                        </div>

                        <ChevronRight
                            class="h-3.5 w-3.5 text-slate-400 transition-transform group-hover:translate-x-0.5 group-hover:text-indigo-600 dark:text-gray-500 dark:group-hover:text-indigo-400"
                        />
                    </div>
                </div>

                <!-- Study Activity Heatmap Graph (GitHub Style) -->
                <div
                    v-if="
                        studyHeatmap?.heatmapData &&
                        studyHeatmap.heatmapData.length > 0
                    "
                >
                    <StudyHeatmap
                        :heatmap-data="studyHeatmap.heatmapData"
                        :stats="studyHeatmap.stats"
                    />
                </div>

                <!-- Tabbed Content Section (3 Tabs: Forum, Articles, Notes) -->
                <div class="space-y-3.5">
                    <!-- Navigation Tabs Bar -->
                    <div
                        class="flex items-center gap-1 rounded-xl border border-slate-200/80 bg-white p-1 shadow-xs sm:rounded-2xl dark:border-gray-800 dark:bg-gray-900"
                    >
                        <button
                            @click="activeTab = 'notes'"
                            type="button"
                            class="flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-xs font-bold transition sm:rounded-xl sm:px-3"
                            :class="
                                activeTab === 'notes'
                                    ? 'bg-slate-900 text-white shadow-xs dark:bg-gray-100 dark:text-gray-900'
                                    : 'text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800'
                            "
                        >
                            <NotebookPen class="h-3.5 w-3.5 shrink-0" />
                            <span>Notes</span>
                        </button>

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
                    </div>

                    <!-- Tab 1: Notes & Study Materials -->
                    <div v-if="activeTab === 'notes'">
                        <div
                            v-if="!notes || notes.length === 0"
                            class="rounded-2xl border border-dashed border-slate-200 bg-white p-7 text-center sm:rounded-3xl sm:p-8 dark:border-gray-800 dark:bg-gray-900"
                        >
                            <div
                                class="mx-auto flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 sm:h-10 sm:w-10 dark:bg-amber-950/60 dark:text-amber-400"
                            >
                                <NotebookPen class="h-5 w-5 stroke-[1.8]" />
                            </div>
                            <h3
                                class="mt-2 text-xs font-bold text-slate-900 dark:text-gray-100"
                            >
                                No notes or study materials shared yet
                            </h3>
                            <p
                                class="mx-auto mt-1 max-w-xs text-[11px] text-slate-500 dark:text-gray-400"
                            >
                                Study notes, lecture videos, and PDF materials
                                uploaded by {{ profileUser.name }} will appear
                                here.
                            </p>
                        </div>

                        <div v-else class="space-y-2.5">
                            <Link
                                v-for="note in notes"
                                :key="note.id"
                                :href="`/resources/${note.id}`"
                                class="group flex items-center justify-between gap-3 rounded-2xl border border-slate-200/80 bg-white p-3.5 shadow-2xs transition hover:border-slate-300 hover:shadow-xs dark:border-gray-800 dark:bg-gray-900 dark:hover:border-gray-700"
                            >
                                <div class="flex min-w-0 items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border"
                                        :class="
                                            note.resource_type === 'video'
                                                ? 'border-rose-200/50 bg-rose-50 text-rose-600 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-400'
                                                : note.resource_type ===
                                                    'practical'
                                                  ? 'border-teal-200/50 bg-teal-50 text-teal-600 dark:border-teal-500/20 dark:bg-teal-500/10 dark:text-teal-400'
                                                  : note.resource_type ===
                                                      'other'
                                                    ? 'border-slate-200 bg-slate-100 text-slate-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300'
                                                    : 'border-amber-200/50 bg-amber-50 text-amber-600 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-400'
                                        "
                                    >
                                        <Video
                                            v-if="
                                                note.resource_type === 'video'
                                            "
                                            class="h-4.5 w-4.5 stroke-[2.2]"
                                        />
                                        <FlaskConical
                                            v-else-if="
                                                note.resource_type ===
                                                'practical'
                                            "
                                            class="h-4.5 w-4.5 stroke-[2.2]"
                                        />
                                        <FileText
                                            v-else-if="
                                                note.resource_type === 'other'
                                            "
                                            class="h-4.5 w-4.5 stroke-[2.2]"
                                        />
                                        <NotebookPen
                                            v-else
                                            class="h-4.5 w-4.5 stroke-[2.2]"
                                        />
                                    </div>

                                    <div class="min-w-0">
                                        <h4
                                            class="truncate text-xs font-bold text-slate-900 transition group-hover:text-indigo-600 sm:text-sm dark:text-gray-100 dark:group-hover:text-indigo-400"
                                        >
                                            {{ note.title }}
                                        </h4>
                                        <div
                                            class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] text-slate-400 dark:text-gray-500"
                                        >
                                            <span
                                                v-if="note.node?.subject"
                                                class="font-medium text-slate-600 dark:text-gray-300"
                                            >
                                                {{ note.node.subject.name }}
                                            </span>
                                            <span v-if="note.node"
                                                >&middot;
                                                {{ note.node.name }}</span
                                            >
                                            <span v-if="note.items_count"
                                                >&middot; {{ note.items_count }}
                                                {{
                                                    note.resource_type ===
                                                    'video'
                                                        ? 'classes'
                                                        : 'items'
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                </div>

                                <div class="flex shrink-0 items-center gap-2">
                                    <div
                                        class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-bold transition-colors"
                                        :class="
                                            (note.vote_score || 0) > 0
                                                ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400'
                                                : (note.vote_score || 0) < 0
                                                  ? 'bg-rose-50 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400'
                                                  : 'bg-slate-100 text-slate-500 dark:bg-gray-800 dark:text-gray-400'
                                        "
                                        title="Vote score"
                                    >
                                        <ArrowBigUp
                                            class="h-3.5 w-3.5"
                                            :class="
                                                (note.vote_score || 0) > 0
                                                    ? 'fill-current'
                                                    : ''
                                            "
                                        />
                                        <span class="tabular-nums">{{
                                            note.vote_score || 0
                                        }}</span>
                                    </div>

                                    <ChevronRight
                                        class="h-4 w-4 text-slate-400 transition-transform group-hover:translate-x-0.5 group-hover:text-indigo-600 dark:text-gray-500 dark:group-hover:text-indigo-400"
                                    />
                                </div>
                            </Link>

                            <div
                                v-if="
                                    (notesCount ?? notes.length) > notes.length
                                "
                                class="pt-1.5 text-center"
                            >
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full border border-slate-200/80 bg-slate-50 px-3.5 py-1 text-xs font-medium text-slate-500 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-400"
                                >
                                    and
                                    {{
                                        (notesCount ?? notes.length) -
                                        notes.length
                                    }}
                                    other
                                    {{
                                        (notesCount ?? notes.length) -
                                            notes.length ===
                                        1
                                            ? 'note'
                                            : 'notes'
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: Forum Questions & Answers -->
                    <div v-else-if="activeTab === 'forum'" class="space-y-3">
                        <!-- Sub-navigation: Questions vs Answers -->
                        <div
                            class="flex items-center justify-between gap-2 px-1"
                        >
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
                            <EmptyState
                                v-if="!forumPosts || forumPosts.length === 0"
                                :icon="HelpCircle"
                                variant="dashed"
                                title="No questions asked yet"
                                description="Questions posted to the academic forum will appear here."
                            />

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
                                        <span>{{
                                            timeAgo(post.created_at)
                                        }}</span>
                                    </div>
                                </Link>

                                <div
                                    v-if="
                                        (stats?.questionsCount ?? 0) >
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
                                            {{ stats?.questionsCount }}
                                            questions in Forum &rarr;</span
                                        >
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Answers Sub-panel -->
                        <div v-else-if="forumSubTab === 'answers'">
                            <EmptyState
                                v-if="
                                    !forumAnswers || forumAnswers.length === 0
                                "
                                :icon="MessageSquareCheck"
                                variant="dashed"
                                title="No answers contributed yet"
                                description="Solutions provided to questions will appear here."
                            />

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
                                        <span>{{
                                            timeAgo(ans.created_at)
                                        }}</span>
                                    </div>
                                </Link>

                                <div
                                    v-if="
                                        (stats?.answersCount ?? 0) >
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

                            <EmptyState
                                v-if="blogs.length === 0"
                                :icon="FileText"
                                variant="dashed"
                                title="No published articles yet"
                                description="Educational blogs written by this author will appear here."
                            />

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
                                            <span
                                                class="flex items-center gap-1"
                                            >
                                                <Eye class="h-3 w-3" />
                                                {{ blog.views }}
                                            </span>
                                            <span
                                                class="flex items-center gap-1"
                                            >
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
                                    v-if="
                                        (stats?.blogsCount ?? 0) >
                                        (blogs?.length || 0)
                                    "
                                    class="pt-1 text-center"
                                >
                                    <Link
                                        :href="`/blogs?q=${encodeURIComponent(profileUser.name)}`"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-700 hover:underline dark:text-indigo-400"
                                    >
                                        <span
                                            >View all
                                            {{ stats?.blogsCount }} articles
                                            &rarr;</span
                                        >
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Locked State Screen -->
            <div
                v-else
                class="flex flex-col items-center justify-center rounded-2xl border border-slate-200/80 bg-white p-8 text-center shadow-xs sm:p-12 dark:border-gray-800 dark:bg-gray-900"
            >
                <div
                    class="flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 shadow-2xs ring-4 ring-indigo-50/50 dark:bg-indigo-950/60 dark:text-indigo-400 dark:ring-indigo-950/30"
                >
                    <Lock class="h-7 w-7 stroke-[2]" />
                </div>

                <h3
                    class="mt-4 text-base font-bold tracking-tight text-slate-900 dark:text-gray-100"
                >
                    Activity & Contributions Locked
                </h3>

                <p
                    class="mt-1.5 max-w-md text-xs leading-relaxed text-slate-500 dark:text-gray-400"
                >
                    <template v-if="lockReason === 'private'">
                        {{ profileUser.name }} has set their study progress,
                        questions, and articles to private.
                    </template>
                    <template v-else>
                        {{ profileUser.name }} has locked their activity.
                        Appreciate this member to view their questions, answers,
                        and study progress.
                    </template>
                </p>

                <!-- Unlock button if lockReason is 'appreciators_only' -->
                <div v-if="lockReason === 'appreciators_only'" class="mt-5">
                    <button
                        @click="handleAppreciate"
                        type="button"
                        class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-indigo-700 active:scale-95 dark:bg-indigo-500 dark:hover:bg-indigo-400"
                    >
                        <Heart class="h-4 w-4 fill-white" />
                        <span>Appreciate to Unlock</span>
                    </button>
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
                        href="/peers"
                        class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                    >
                        Find more →
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
                    v-if="appreciatorsList && appreciatorsList.length > 0"
                    class="-mx-1 max-h-72 divide-y divide-slate-100 overflow-y-auto px-1 dark:divide-gray-800/80"
                >
                    <UserListItem
                        v-for="person in appreciatorsList"
                        :key="person.id"
                        :user="person"
                        theme="rose"
                        @click="showAppreciatorsModal = false"
                    />

                    <div v-if="appreciatorsHasMore" class="py-3 text-center">
                        <button
                            @click="loadMoreAppreciators"
                            :disabled="isLoadingMoreAppreciators"
                            type="button"
                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold text-rose-600 transition-colors hover:bg-rose-50 hover:text-rose-700 disabled:opacity-50 dark:text-rose-400 dark:hover:bg-rose-950/40"
                        >
                            <Loader2
                                v-if="isLoadingMoreAppreciators"
                                class="h-3.5 w-3.5 animate-spin"
                            />
                            <span>{{
                                isLoadingMoreAppreciators
                                    ? 'Loading...'
                                    : 'Load more'
                            }}</span>
                        </button>
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
                    v-if="appreciatingList && appreciatingList.length > 0"
                    class="-mx-1 max-h-72 divide-y divide-slate-100 overflow-y-auto px-1 dark:divide-gray-800/80"
                >
                    <div
                        v-for="person in appreciatingList"
                        :key="person.id"
                        class="flex items-center justify-between gap-2"
                    >
                        <UserListItem
                            :user="person"
                            theme="indigo"
                            class="min-w-0 flex-1"
                            @click="showAppreciatingModal = false"
                        />
                        <button
                            v-if="isOwnProfile"
                            type="button"
                            @click.stop="
                                person.unappreciated = !person.unappreciated;
                                router.post(
                                    `/u/${person.id}/appreciate`,
                                    {},
                                    {
                                        preserveScroll: true,
                                        preserveState: true,
                                        except: [
                                            'appreciating',
                                            'suggestedUsers',
                                        ],
                                        onError: () => {
                                            person.unappreciated =
                                                !person.unappreciated;
                                        },
                                    },
                                );
                            "
                            class="cursor-pointer p-1.5 transition active:scale-90"
                            :title="
                                person.unappreciated
                                    ? 'Appreciate'
                                    : 'Appreciating'
                            "
                        >
                            <Heart
                                class="h-4 w-4"
                                :class="
                                    person.unappreciated
                                        ? 'stroke-[2] text-slate-400 dark:text-gray-500'
                                        : 'fill-rose-500 text-rose-500'
                                "
                            />
                        </button>
                    </div>

                    <div v-if="appreciatingHasMore" class="py-3 text-center">
                        <button
                            @click="loadMoreAppreciating"
                            :disabled="isLoadingMoreAppreciating"
                            type="button"
                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold text-indigo-600 transition-colors hover:bg-indigo-50 hover:text-indigo-700 disabled:opacity-50 dark:text-indigo-400 dark:hover:bg-indigo-950/40"
                        >
                            <Loader2
                                v-if="isLoadingMoreAppreciating"
                                class="h-3.5 w-3.5 animate-spin"
                            />
                            <span>{{
                                isLoadingMoreAppreciating
                                    ? 'Loading...'
                                    : 'Load more'
                            }}</span>
                        </button>
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
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl"
                    :class="
                        guestModalAction === 'poke'
                            ? 'bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400'
                            : 'bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400'
                    "
                >
                    <Zap
                        v-if="guestModalAction === 'poke'"
                        class="h-6 w-6 fill-amber-500 text-amber-500"
                    />
                    <Heart v-else class="h-6 w-6 fill-rose-500" />
                </div>

                <h3
                    class="mt-3.5 text-base font-bold text-slate-900 dark:text-gray-100"
                >
                    {{
                        guestModalAction === 'poke'
                            ? 'Sign in to Poke'
                            : 'Sign in to Appreciate'
                    }}
                </h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">
                    {{
                        guestModalAction === 'poke'
                            ? 'You need to be logged in to send pokes to fellow peers.'
                            : 'You need to be logged in to send appreciation and support fellow students and contributors.'
                    }}
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
                        :href="`/login?redirect=${encodeURIComponent($page.url)}`"
                        class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-slate-900 py-2.5 text-xs font-bold text-white shadow-xs transition hover:bg-slate-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200"
                    >
                        <LogIn class="h-3.5 w-3.5" />
                        <span>Sign In</span>
                    </Link>
                </div>
            </div>
        </div>
    </Teleport>

    <BaseModal
        :is-open="showPokeModal"
        title="Send a Poke"
        :description="`Choose a message to poke @${profileUser.username}`"
        max-width="md"
        position="responsive"
        @close="showPokeModal = false"
    >
        <template #icon>
            <div
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400"
            >
                <Zap class="h-4 w-4 fill-amber-500 text-amber-500" />
            </div>
        </template>

        <div class="space-y-2 p-4 sm:p-5">
            <button
                v-for="preset in pokeData?.presets || []"
                :key="preset.id"
                type="button"
                @click="selectedPresetId = preset.id"
                class="flex w-full cursor-pointer items-center gap-3 rounded-xl border bg-white p-3 text-left transition select-none active:scale-[0.99] dark:bg-gray-900"
                :class="
                    selectedPresetId === preset.id
                        ? 'border-amber-500 ring-1 ring-amber-500 dark:border-amber-500 dark:ring-amber-500'
                        : 'border-slate-200/80 hover:border-slate-300 dark:border-gray-800 dark:hover:border-gray-700'
                "
            >
                <span class="shrink-0 text-xl">{{ preset.icon }}</span>
                <span
                    class="flex-1 text-xs leading-relaxed font-medium text-slate-700 dark:text-gray-300"
                >
                    {{ preset.message }}
                </span>
            </button>
        </div>

        <template #footer>
            <div class="flex items-center justify-end gap-2">
                <button
                    type="button"
                    @click="showPokeModal = false"
                    class="cursor-pointer rounded-xl border border-slate-200 px-3.5 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    @click="handleSendPoke"
                    :disabled="!selectedPresetId || isSubmittingPoke"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-amber-600 active:scale-95 disabled:opacity-50 dark:bg-amber-500 dark:hover:bg-amber-600"
                >
                    <Zap class="h-3.5 w-3.5 fill-current" />
                    <span>{{
                        isSubmittingPoke ? 'Sending...' : 'Send Poke'
                    }}</span>
                </button>
            </div>
        </template>
    </BaseModal>

    <!-- Syllabus Breakdown Modal -->
    <SyllabusBreakdownModal
        v-if="syllabusProgress"
        v-model="showSyllabusModal"
        :user="profileUser"
        :syllabus-progress="syllabusProgress"
        :is-own-profile="isOwnProfile"
        @share="showShareModal = true"
    />

    <!-- Social Syllabus Share Modal -->
    <SyllabusShareModal
        v-if="syllabusProgress"
        v-model="showShareModal"
        :data="{
            user: {
                name: profileUser.name,
                username: profileUser.username,
                image_url: profileUser.image_url,
                institution: profileUser.institution,
            },
            course: syllabusProgress.course,
            group: syllabusProgress.group,
            overallPercent: syllabusProgress.overallPercent,
            completedChapters: syllabusProgress.completedChapters,
            totalChapters: syllabusProgress.totalChapters,
            subjects: syllabusProgress.subjects,
        }"
    />

    <!-- Avatar Image Previewer Modal -->
    <ImageViewerModal
        v-if="profileUser.image_url"
        v-model="showAvatarModal"
        :src="profileUser.image_url"
        :alt="profileUser.name"
        :title="profileUser.name"
    />
</template>
