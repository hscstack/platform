<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Award,
    Calendar,
    Clock,
    Crown,
    Flame,
    Info,
    Medal,
    ShieldCheck,
    Trophy,
    UploadCloud,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref } from 'vue';
import BaseModal from '@/components/BaseModal.vue';
import VerifiedBadge from '@/components/VerifiedBadge.vue';
import { useAuth } from '@/lib/useAuth';

export interface LeaderboardUser {
    rank: number;
    id: number;
    name: string;
    username: string;
    image_url: string | null;
    institution: string | null;
    title: string | null;
    curriculum?: 'hsc' | 'ssc' | null;
    group?: string | null;
    is_verified?: boolean;
    score: number;
    completed_chapters_count?: number;
}

export interface CurrentUserRank {
    rank: number | string;
    score: number;
}

interface Props {
    tab: 'students' | 'contributors';
    timeframe: 'this_week' | 'this_month' | 'all_time';
    leaderboard: LeaderboardUser[];
    currentUserRank: CurrentUserRank | null;
}

const props = defineProps<Props>();

const { user } = useAuth();

function switchTab(newTab: 'students' | 'contributors') {
    if (props.tab === newTab) {
        return;
    }

    router.get(
        '/leaderboard',
        { tab: newTab, timeframe: props.timeframe },
        { preserveState: true, preserveScroll: true },
    );
}

function switchTimeframe(
    newTimeframe: 'this_week' | 'this_month' | 'all_time',
) {
    if (props.timeframe === newTimeframe) {
        return;
    }

    router.get(
        '/leaderboard',
        { tab: props.tab, timeframe: newTimeframe },
        { preserveState: true, preserveScroll: true },
    );
}

function formatStudyTime(seconds: number): string {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);

    if (hours === 0) {
        return `${minutes}m`;
    }

    return minutes > 0 ? `${hours}h ${minutes}m` : `${hours}h`;
}

function formatScore(score: number): string {
    if (props.tab === 'students') {
        return formatStudyTime(score);
    }

    return `${score} ${score === 1 ? 'Resource' : 'Resources'}`;
}

const topThree = computed(() => {
    return props.leaderboard.slice(0, 3);
});

// Arrange podium as: 2nd place (left), 1st place (center), 3rd place (right)
const podiumUsers = computed(() => {
    const first = topThree.value[0] || null;
    const second = topThree.value[1] || null;
    const third = topThree.value[2] || null;

    return [
        { user: second, place: 2 },
        { user: first, place: 1 },
        { user: third, place: 3 },
    ];
});

const highlightedUserId = ref<number | null>(null);
let highlightTimeout: ReturnType<typeof setTimeout> | null = null;
const showInfoModal = ref(false);

function highlightUserInList(userId: number) {
    highlightedUserId.value = userId;

    if (highlightTimeout) {
        clearTimeout(highlightTimeout);
    }

    const element = document.getElementById(`leaderboard-row-${userId}`);

    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    highlightTimeout = setTimeout(() => {
        highlightedUserId.value = null;
    }, 1800);
}

onBeforeUnmount(() => {
    if (highlightTimeout) {
        clearTimeout(highlightTimeout);
    }
});
</script>

<template>
    <Head>
        <title>Community Leaderboard - HSC Stack</title>
        <meta
            name="description"
            content="See top students and top resource contributors on HSC Stack. Track study time, chapter progress, and community contributions."
        />
    </Head>

    <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 sm:py-8">
        <!-- Header -->
        <div
            class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex items-center gap-3">
                <div
                    class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-500 text-white shadow-md shadow-amber-500/20 dark:bg-amber-500 dark:shadow-amber-500/10"
                >
                    <Trophy class="h-6 w-6" />
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1
                            class="text-xl font-black tracking-tight text-slate-900 sm:text-2xl dark:text-gray-100"
                        >
                            Community Leaderboard
                        </h1>
                        <button
                            type="button"
                            @click="showInfoModal = true"
                            class="group inline-flex h-7 w-7 cursor-pointer items-center justify-center rounded-xl border border-slate-200/90 bg-white text-slate-500 shadow-2xs transition hover:border-amber-300 hover:bg-amber-50/70 hover:text-amber-700 active:scale-95 dark:border-gray-800 dark:bg-gray-800/90 dark:text-gray-400 dark:hover:border-amber-600/50 dark:hover:bg-amber-950/40 dark:hover:text-amber-300"
                            title="লিডারবোর্ড সম্পর্কিত তথ্য ও নিয়মাবলী"
                            aria-label="How leaderboard works"
                        >
                            <Info
                                class="h-3.5 w-3.5 transition group-hover:scale-110"
                            />
                        </button>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-gray-400">
                        শীর্ষ অধ্যয়নকারী শিক্ষার্থী ও রিসোর্স কন্ট্রিবিউটরদের
                        তালিকা
                    </p>
                </div>
            </div>

            <!-- Timeframe Pills -->
            <div
                class="inline-flex self-start rounded-2xl border border-slate-200/80 bg-white p-1 shadow-2xs sm:self-auto dark:border-gray-800 dark:bg-gray-900"
            >
                <button
                    type="button"
                    @click="switchTimeframe('this_week')"
                    :class="[
                        timeframe === 'this_week'
                            ? 'bg-indigo-600 text-white shadow-2xs'
                            : 'text-slate-600 hover:text-slate-900 dark:text-gray-400 dark:hover:text-white',
                    ]"
                    class="cursor-pointer rounded-xl px-3 py-1.5 text-xs font-semibold transition"
                >
                    এই সপ্তাহ
                </button>
                <button
                    type="button"
                    @click="switchTimeframe('this_month')"
                    :class="[
                        timeframe === 'this_month'
                            ? 'bg-indigo-600 text-white shadow-2xs'
                            : 'text-slate-600 hover:text-slate-900 dark:text-gray-400 dark:hover:text-white',
                    ]"
                    class="cursor-pointer rounded-xl px-3 py-1.5 text-xs font-semibold transition"
                >
                    এই মাস
                </button>
                <button
                    type="button"
                    @click="switchTimeframe('all_time')"
                    :class="[
                        timeframe === 'all_time'
                            ? 'bg-indigo-600 text-white shadow-2xs'
                            : 'text-slate-600 hover:text-slate-900 dark:text-gray-400 dark:hover:text-white',
                    ]"
                    class="cursor-pointer rounded-xl px-3 py-1.5 text-xs font-semibold transition"
                >
                    সর্বকালের
                </button>
            </div>
        </div>

        <!-- Dual Tabs: Students vs Contributors -->
        <div
            class="mb-3 grid grid-cols-2 gap-2 rounded-2xl border border-slate-200/80 bg-slate-100/70 p-1.5 sm:gap-3 dark:border-gray-800 dark:bg-gray-900/60"
        >
            <button
                type="button"
                @click="switchTab('students')"
                :class="[
                    tab === 'students'
                        ? 'border-slate-200/90 bg-white text-indigo-950 shadow-xs ring-1 ring-indigo-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:ring-indigo-400/20'
                        : 'text-slate-600 hover:text-slate-900 dark:text-gray-400 dark:hover:text-gray-200',
                ]"
                class="flex cursor-pointer items-center justify-center gap-2 rounded-xl py-2.5 text-xs font-bold transition sm:text-sm"
            >
                <Clock class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                <span>শীর্ষ শিক্ষার্থী</span>
            </button>

            <button
                type="button"
                @click="switchTab('contributors')"
                :class="[
                    tab === 'contributors'
                        ? 'border-slate-200/90 bg-white text-indigo-950 shadow-xs ring-1 ring-indigo-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:ring-indigo-400/20'
                        : 'text-slate-600 hover:text-slate-900 dark:text-gray-400 dark:hover:text-gray-200',
                ]"
                class="flex cursor-pointer items-center justify-center gap-2 rounded-xl py-2.5 text-xs font-bold transition sm:text-sm"
            >
                <UploadCloud
                    class="h-4 w-4 text-emerald-600 dark:text-emerald-400"
                />
                <span>শীর্ষ কন্ট্রিবিউটর</span>
            </button>
        </div>

        <!-- Tab 1-line Description -->
        <p
            class="mb-7 px-1 text-center text-xs text-slate-500 dark:text-gray-400"
        >
            <span v-if="tab === 'students'">
                নির্ধারিত সময়ের মধ্যে সবচেয়ে বেশি পড়ার রেকর্ড অনুসারে। সময়
                রেকর্ড করুন
                <Link
                    href="/tracker"
                    class="font-semibold text-indigo-600 underline underline-offset-2 hover:text-indigo-700 dark:text-indigo-400"
                    >/tracker</Link
                >
                থেকে।
            </span>
            <span v-else>
                নির্ধারিত সময়ে সবচেয়ে বেশি নোট ও রিসোর্স আপলোড অনুসারে।
            </span>
        </p>

        <!-- Current User Rank Bar (if logged in) -->
        <div
            v-if="user"
            @click="currentUserRank ? highlightUserInList(user.id) : null"
            class="mb-6 flex items-center justify-between gap-3 rounded-2xl border border-slate-200/80 bg-white px-3.5 py-2.5 shadow-2xs sm:mb-8 sm:p-4 dark:border-gray-800/90 dark:bg-gray-900"
            :class="[
                currentUserRank
                    ? 'cursor-pointer transition hover:border-indigo-300 hover:shadow-xs active:scale-[0.99] dark:hover:border-indigo-700/60'
                    : '',
            ]"
            :title="
                currentUserRank ? 'তালিকায় নিজের অবস্থান দেখুন' : undefined
            "
        >
            <div class="flex min-w-0 items-center gap-2.5 sm:gap-3">
                <div class="relative shrink-0">
                    <img
                        v-if="user.image_url"
                        :src="user.image_url"
                        :alt="user.name"
                        class="h-8 w-8 rounded-full object-cover ring-2 ring-indigo-500/20 sm:h-10 sm:w-10"
                    />
                    <div
                        v-else
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-50 text-xs font-bold text-indigo-600 sm:h-10 sm:w-10 dark:bg-indigo-950/60 dark:text-indigo-400"
                    >
                        {{ user.name.charAt(0) }}
                    </div>
                    <span
                        v-if="currentUserRank"
                        class="absolute -right-1 -bottom-1 flex min-h-4.5 min-w-4.5 items-center justify-center rounded-full bg-indigo-600 px-1 text-[9px] font-black text-white shadow-xs sm:min-h-5 sm:min-w-5 sm:text-[10px]"
                    >
                        #{{ currentUserRank.rank }}
                    </span>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-1.5">
                        <span
                            class="truncate text-xs font-bold text-slate-900 sm:text-sm dark:text-white"
                        >
                            {{ user.name }}
                        </span>
                    </div>
                    <p
                        class="truncate text-[11px] text-slate-500 dark:text-gray-400"
                    >
                        <span v-if="currentUserRank">
                            <span class="hidden sm:inline"
                                >আপনার বর্তমান অবস্থান:
                            </span>
                            <strong
                                class="font-bold text-indigo-600 dark:text-indigo-400"
                            >
                                #{{ currentUserRank.rank }}
                            </strong>
                            ({{ formatScore(currentUserRank.score) }})
                        </span>
                        <span v-else>
                            {{
                                tab === 'students'
                                    ? 'স্টাডি ট্র্যাকারে সময় যোগ করে লিডারবোর্ডে যুক্ত হোন!'
                                    : 'রিসোর্স শেয়ার করে লিডারবোর্ডে যুক্ত হোন!'
                            }}
                        </span>
                    </p>
                </div>
            </div>

            <Link
                v-if="tab === 'students'"
                href="/tracker"
                @click.stop
                class="inline-flex shrink-0 cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white shadow-2xs transition hover:bg-slate-800 active:scale-95 sm:px-3.5 sm:py-2 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100"
            >
                <span>ট্র্যাকারে যান</span>
            </Link>
        </div>

        <!-- Empty State -->
        <div
            v-if="leaderboard.length === 0"
            class="flex flex-col items-center justify-center rounded-3xl border border-dashed border-slate-200 bg-white p-12 text-center shadow-2xs dark:border-gray-800 dark:bg-gray-900"
        >
            <div
                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-slate-400 dark:bg-gray-800 dark:text-gray-500"
            >
                <Flame class="h-6 w-6" />
            </div>
            <h3 class="mt-4 text-sm font-bold text-slate-900 dark:text-white">
                এই সময়সীমায় কোনো রেকর্ড পাওয়া যায়নি
            </h3>
            <p class="mt-1 max-w-sm text-xs text-slate-500 dark:text-gray-400">
                {{
                    tab === 'students'
                        ? 'স্টাডি ট্র্যাকারে পড়ার সময় ট্র্যাক করে প্রথম শিক্ষার্থী হিসেবে শীর্ষে আসুন!'
                        : 'রিসোর্স শেয়ার করে প্রথম কন্ট্রিবিউটর হিসেবে শীর্ষে আসুন!'
                }}
            </p>
        </div>

        <div v-else class="space-y-6">
            <!-- Top 3 Podium (Visible on sm screens & up) -->
            <div
                v-if="topThree.length > 0"
                class="grid grid-cols-3 items-end gap-2 pt-4 pb-2 sm:gap-4"
            >
                <template
                    v-for="{ user: pUser, place } in podiumUsers"
                    :key="place"
                >
                    <div
                        v-if="pUser"
                        class="relative flex flex-col items-center text-center transition"
                        :class="[
                            place === 1
                                ? 'z-10 order-2 -mt-4'
                                : place === 2
                                  ? 'order-1'
                                  : 'order-3',
                        ]"
                    >
                        <!-- Place Badge -->
                        <div
                            class="mb-1.5 cursor-pointer transition hover:opacity-90 active:scale-95 sm:mb-2"
                            @click="highlightUserInList(pUser.id)"
                            title="তালিকায় দেখুন"
                        >
                            <span
                                v-if="pUser.rank === 1"
                                class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-black text-amber-800 shadow-2xs sm:px-2.5 sm:text-[11px] dark:bg-amber-950/60 dark:text-amber-300"
                            >
                                <Crown
                                    class="h-3 w-3 fill-amber-500 text-amber-500 sm:h-3.5 sm:w-3.5"
                                />
                                <span class="sm:hidden">#1</span>
                                <span class="hidden sm:inline">১ম স্থান</span>
                            </span>
                            <span
                                v-else-if="pUser.rank === 2"
                                class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-700 sm:px-2.5 sm:text-[11px] dark:bg-gray-800 dark:text-gray-300"
                            >
                                <Medal
                                    class="h-3 w-3 text-slate-400 sm:h-3.5 sm:w-3.5"
                                />
                                <span class="sm:hidden">#2</span>
                                <span class="hidden sm:inline">২য় স্থান</span>
                            </span>
                            <span
                                v-else-if="pUser.rank === 3"
                                class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-900 sm:px-2.5 sm:text-[11px] dark:bg-amber-950/40 dark:text-amber-400"
                            >
                                <Award
                                    class="h-3 w-3 text-amber-600 sm:h-3.5 sm:w-3.5 dark:text-amber-500"
                                />
                                <span class="sm:hidden">#3</span>
                                <span class="hidden sm:inline">৩য় স্থান</span>
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-700 sm:px-2.5 sm:text-[11px] dark:bg-gray-800 dark:text-gray-300"
                            >
                                <span>#{{ pUser.rank }}</span>
                            </span>
                        </div>

                        <!-- Podium User Card -->
                        <button
                            type="button"
                            @click="highlightUserInList(pUser.id)"
                            class="group flex w-full cursor-pointer flex-col items-center rounded-2xl border bg-white p-2 text-center shadow-2xs transition hover:shadow-xs active:scale-98 sm:p-3.5 dark:bg-gray-900"
                            :class="[
                                place === 1
                                    ? 'border-amber-300/80 bg-gradient-to-b from-amber-50/50 to-white ring-2 ring-amber-400/40 dark:border-amber-700/60 dark:from-amber-950/20 dark:to-gray-900'
                                    : 'border-slate-200/80 dark:border-gray-800',
                            ]"
                            title="তালিকায় হাইলাইট করুন"
                        >
                            <!-- Avatar with Rank Ring -->
                            <div class="relative mb-2">
                                <img
                                    v-if="pUser.image_url"
                                    :src="pUser.image_url"
                                    :alt="pUser.name"
                                    class="rounded-full object-cover transition group-hover:scale-105"
                                    :class="[
                                        place === 1
                                            ? 'h-14 w-14 shadow-md ring-4 ring-amber-400 sm:h-20 sm:w-20'
                                            : 'h-10 w-10 ring-2 ring-slate-300 sm:h-16 sm:w-16 dark:ring-gray-700',
                                    ]"
                                />
                                <div
                                    v-else
                                    class="flex items-center justify-center rounded-full bg-indigo-50 font-black text-indigo-600 transition group-hover:scale-105 dark:bg-indigo-950/60 dark:text-indigo-400"
                                    :class="[
                                        place === 1
                                            ? 'h-14 w-14 text-lg shadow-md ring-4 ring-amber-400 sm:h-20 sm:w-20 sm:text-xl'
                                            : 'h-10 w-10 text-xs ring-2 ring-slate-300 sm:h-16 sm:w-16 sm:text-base dark:ring-gray-700',
                                    ]"
                                >
                                    {{ pUser.name.charAt(0) }}
                                </div>
                            </div>

                            <!-- Name & Verified -->
                            <div
                                class="flex max-w-full items-center justify-center gap-1"
                            >
                                <span
                                    class="line-clamp-2 text-center font-bold break-words text-slate-900 group-hover:text-indigo-600 dark:text-white dark:group-hover:text-indigo-400"
                                    :class="[
                                        place === 1
                                            ? 'text-[11px] font-extrabold sm:text-sm'
                                            : 'text-[10px] sm:text-xs',
                                    ]"
                                >
                                    {{ pUser.name }}
                                </span>
                                <VerifiedBadge
                                    v-if="pUser.is_verified"
                                    size="h-3 w-3 shrink-0"
                                />
                            </div>

                            <!-- College / Institution -->
                            <p
                                v-if="pUser.institution"
                                class="mt-0.5 line-clamp-1 text-center text-[9px] text-slate-500 sm:text-[10px] dark:text-gray-400"
                            >
                                {{ pUser.institution }}
                            </p>

                            <!-- Score Pill -->
                            <div class="mt-2 sm:mt-2.5">
                                <span
                                    class="inline-flex items-center gap-1 rounded-lg px-1.5 py-0.5 font-black shadow-2xs sm:px-2"
                                    :class="[
                                        place === 1
                                            ? 'bg-amber-500 text-[10px] text-white sm:text-sm'
                                            : 'bg-indigo-50 text-[9px] text-indigo-700 sm:text-xs dark:bg-indigo-950/60 dark:text-indigo-300',
                                    ]"
                                >
                                    <Clock
                                        v-if="tab === 'students'"
                                        class="h-2.5 w-2.5 sm:h-3 sm:w-3"
                                    />
                                    <UploadCloud
                                        v-else
                                        class="h-2.5 w-2.5 sm:h-3 sm:w-3"
                                    />
                                    <span>{{ formatScore(pUser.score) }}</span>
                                </span>
                            </div>
                        </button>
                    </div>
                </template>
            </div>

            <!-- Full Ranked List -->
            <div
                class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-2xs dark:border-gray-800/90 dark:bg-gray-900"
            >
                <div
                    class="border-b border-slate-100 bg-slate-50/60 px-4 py-3 sm:px-6 dark:border-gray-800 dark:bg-gray-900/60"
                >
                    <h2
                        class="text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400"
                    >
                        {{
                            tab === 'students'
                                ? 'র‍্যাঙ্কিং তালিকা'
                                : 'কন্ট্রিবিউটর তালিকা'
                        }}
                    </h2>
                </div>

                <div class="divide-y divide-slate-100 dark:divide-gray-800/60">
                    <Link
                        v-for="u in leaderboard"
                        :key="u.id"
                        :id="`leaderboard-row-${u.id}`"
                        :href="`/u/${u.username}`"
                        class="group flex scroll-mt-24 items-center justify-between gap-3 p-3.5 transition-all duration-700 ease-out hover:bg-slate-50/80 sm:scroll-mt-28 sm:px-6 dark:hover:bg-gray-800/40"
                        :class="[
                            highlightedUserId === u.id
                                ? 'bg-amber-100/40 ring-1 ring-amber-400/50 ring-inset dark:bg-amber-950/30 dark:ring-amber-500/40'
                                : user && user.id === u.id
                                  ? 'bg-indigo-50/50 dark:bg-indigo-950/20'
                                  : '',
                        ]"
                    >
                        <!-- Left: Rank + Avatar + Name Info -->
                        <div class="flex min-w-0 items-center gap-3 sm:gap-4">
                            <!-- Rank Number -->
                            <div
                                class="flex w-7 shrink-0 items-center justify-center text-center sm:w-8"
                            >
                                <span
                                    v-if="u.rank === 1"
                                    class="flex h-6 w-6 items-center justify-center rounded-full bg-amber-100 text-xs font-black text-amber-800 dark:bg-amber-950/60 dark:text-amber-300"
                                >
                                    🥇
                                </span>
                                <span
                                    v-else-if="u.rank === 2"
                                    class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-xs font-black text-slate-700 dark:bg-gray-800 dark:text-gray-300"
                                >
                                    🥈
                                </span>
                                <span
                                    v-else-if="u.rank === 3"
                                    class="flex h-6 w-6 items-center justify-center rounded-full bg-amber-50 text-xs font-black text-amber-900 dark:bg-amber-950/40 dark:text-amber-400"
                                >
                                    🥉
                                </span>
                                <span
                                    v-else
                                    class="text-xs font-bold text-slate-400 sm:text-sm dark:text-gray-500"
                                >
                                    #{{ u.rank }}
                                </span>
                            </div>

                            <!-- Avatar -->
                            <div class="relative shrink-0">
                                <img
                                    v-if="u.image_url"
                                    :src="u.image_url"
                                    :alt="u.name"
                                    class="h-9 w-9 rounded-full object-cover ring-1 ring-slate-200 sm:h-10 sm:w-10 dark:ring-gray-700"
                                />
                                <div
                                    v-else
                                    class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-50 text-xs font-bold text-indigo-600 sm:h-10 sm:w-10 dark:bg-indigo-950/60 dark:text-indigo-400"
                                >
                                    {{ u.name.charAt(0) }}
                                </div>
                            </div>

                            <!-- Name & Meta -->
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <span
                                        class="truncate text-xs font-bold text-slate-900 group-hover:text-indigo-600 sm:text-sm dark:text-white dark:group-hover:text-indigo-400"
                                    >
                                        {{ u.name }}
                                    </span>
                                    <VerifiedBadge
                                        v-if="u.is_verified"
                                        size="h-3 w-3"
                                    />
                                    <span
                                        v-if="user && user.id === u.id"
                                        class="rounded-md bg-indigo-100 px-1.5 py-0.5 text-[10px] font-bold text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300"
                                    >
                                        You
                                    </span>
                                </div>

                                <p
                                    v-if="u.institution"
                                    class="max-w-[200px] truncate text-[11px] text-slate-500 sm:max-w-[320px] dark:text-gray-400"
                                >
                                    {{ u.institution }}
                                </p>
                            </div>
                        </div>

                        <!-- Right: Score Badge -->
                        <div class="shrink-0 text-right">
                            <span
                                class="inline-flex items-center gap-1 rounded-xl bg-slate-50 px-2.5 py-1 text-xs font-bold text-slate-800 shadow-2xs group-hover:bg-indigo-50 group-hover:text-indigo-700 dark:bg-gray-800/80 dark:text-gray-200 dark:group-hover:bg-indigo-950/60 dark:group-hover:text-indigo-300"
                            >
                                <Clock
                                    v-if="tab === 'students'"
                                    class="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400"
                                />
                                <UploadCloud
                                    v-else
                                    class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400"
                                />
                                <span>{{ formatScore(u.score) }}</span>
                            </span>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </div>

    <!-- Info / FAQ Modal -->
    <BaseModal
        v-model:is-open="showInfoModal"
        title="লিডারবোর্ড সম্পর্কিত তথ্য ও নিয়মাবলী"
        description="র‍্যাঙ্কিং নির্ধারণের নিয়ম, অ্যালগরিদম ও সাধারণ প্রশ্নোত্তর"
        max-width="xl"
    >
        <template #icon>
            <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300"
            >
                <Trophy class="h-4.5 w-4.5" />
            </div>
        </template>

        <div
            class="space-y-3.5 p-4 text-xs leading-relaxed text-slate-600 sm:p-6 sm:text-sm dark:text-gray-300"
        >
            <!-- 1. Top Students -->
            <div
                class="rounded-2xl border border-slate-100 bg-slate-50/70 p-3.5 sm:p-4 dark:border-gray-800 dark:bg-gray-800/50"
            >
                <div
                    class="flex items-center gap-2 font-bold text-slate-900 dark:text-white"
                >
                    <Clock
                        class="h-4 w-4 text-indigo-600 dark:text-indigo-400"
                    />
                    <h4>শীর্ষ শিক্ষার্থী কীভাবে নির্ধারিত হয়?</h4>
                </div>
                <p class="mt-1.5 text-xs text-slate-600 dark:text-gray-300">
                    স্টাডি ট্র্যাকারে (<Link
                        href="/tracker"
                        class="font-semibold text-indigo-600 underline hover:text-indigo-700 dark:text-indigo-400"
                        >/tracker</Link
                    >) স্টপওয়াচ অথবা ম্যানুয়াল সময় লগিংয়ের মাধ্যমে রেকর্ড করা
                    মোট পড়াশোনার সময় অনুসারে শিক্ষার্থীদের র‍্যাঙ্ক দেওয়া হয়।
                </p>
            </div>

            <!-- 2. Top Contributors -->
            <div
                class="rounded-2xl border border-slate-100 bg-slate-50/70 p-3.5 sm:p-4 dark:border-gray-800 dark:bg-gray-800/50"
            >
                <div
                    class="flex items-center gap-2 font-bold text-slate-900 dark:text-white"
                >
                    <UploadCloud
                        class="h-4 w-4 text-emerald-600 dark:text-emerald-400"
                    />
                    <h4>শীর্ষ কন্ট্রিবিউটর কীভাবে নির্ধারিত হয়?</h4>
                </div>
                <p class="mt-1.5 text-xs text-slate-600 dark:text-gray-300">
                    এইচএসসি ও এসএসসি সিলেবাসের বিভিন্ন অধ্যায়ে সহপাঠীদের জন্য
                    অনুমোদিত স্টাডি নোট, প্রশ্নব্যাংক ও রিসোর্স আপলোডের সংখ্যার
                    ভিত্তিতে কন্ট্রিবিউটর তালিকা প্রস্তুত হয়।
                </p>
            </div>

            <!-- 3. Timeframes & Calendar Week -->
            <div
                class="rounded-2xl border border-slate-100 bg-slate-50/70 p-3.5 sm:p-4 dark:border-gray-800 dark:bg-gray-800/50"
            >
                <div
                    class="flex items-center gap-2 font-bold text-slate-900 dark:text-white"
                >
                    <Calendar
                        class="h-4 w-4 text-amber-600 dark:text-amber-400"
                    />
                    <h4>সময়সীমা গণনা</h4>
                </div>
                <ul
                    class="mt-2 space-y-1.5 text-xs text-slate-600 dark:text-gray-300"
                >
                    <li>
                        <strong
                            class="font-semibold text-slate-800 dark:text-gray-200"
                            >র‍্যাঙ্কিং আপডেট:</strong
                        >
                        প্রতি ৩০ মিনিট পরপর লিডারবোর্ড র‍্যাঙ্কিং স্বয়ংক্রিয়ভাবে
                        আপডেট হয়।
                    </li>
                    <li>
                        <strong
                            class="font-semibold text-slate-800 dark:text-gray-200"
                            >এই সপ্তাহ:</strong
                        >
                        বাংলাদেশের ক্যালেন্ডার অনুযায়ী প্রতি
                        <strong>শনিবার রাত ১২:০০</strong> থেকে শুরু হয়ে বর্তমান
                        সময় পর্যন্ত।
                    </li>
                    <li>
                        <strong
                            class="font-semibold text-slate-800 dark:text-gray-200"
                            >এই মাস:</strong
                        >
                        চলতি ক্যালেন্ডার মাসের
                        <strong>১ তারিখ রাত ১২:০০</strong> থেকে বর্তমান সময়
                        পর্যন্ত (ডিফল্ট ফিল্টার)।
                    </li>
                    <li>
                        <strong
                            class="font-semibold text-slate-800 dark:text-gray-200"
                            >সর্বকালের:</strong
                        >
                        অ্যাকাউন্ট তৈরির পর থেকে সম্পূর্ণ মোট রেকর্ড।
                    </li>
                </ul>
            </div>

            <!-- 4. Tie Breaker & Fair Play -->
            <div
                class="rounded-2xl border border-slate-100 bg-slate-50/70 p-3.5 sm:p-4 dark:border-gray-800 dark:bg-gray-800/50"
            >
                <div
                    class="flex items-center gap-2 font-bold text-slate-900 dark:text-white"
                >
                    <ShieldCheck
                        class="h-4 w-4 text-sky-600 dark:text-sky-400"
                    />
                    <h4>র‍্যাঙ্কিং টাই ও সুরক্ষা</h4>
                </div>
                <ul
                    class="mt-2 space-y-1.5 text-xs text-slate-600 dark:text-gray-300"
                >
                    <li>
                        <strong
                            class="font-semibold text-slate-800 dark:text-gray-200"
                            >টাই র‍্যাঙ্কিং:</strong
                        >
                        একাধিক শিক্ষার্থীর পড়াশোনার সময় বা রিসোর্স সংখ্যা হুবহু
                        সমান হলে তারা একই র‍্যাঙ্ক অবস্থান শেয়ার করে।
                    </li>
                    <li>
                        <strong
                            class="font-semibold text-slate-800 dark:text-gray-200"
                            >দৈনিক ক্যাপ:</strong
                        >
                        অবাস্তব সময় যোগ রোধ করতে এক ক্যালেন্ডার দিনে সর্বোচ্চ ১৬
                        ঘণ্টার বেশি লগ করা সম্ভব নয়।
                    </li>
                    <li>
                        <strong
                            class="font-semibold text-slate-800 dark:text-gray-200"
                            >ফেয়ার-প্লে পলিসি:</strong
                        >
                        অস্বাভাবিক বা সন্দেহজনক কোনো লগিং কার্যকলাপ পরিলক্ষিত
                        হলে সংশ্লিষ্ট ব্যবহারকারীকে স্বয়ংক্রিয়ভাবে লিডারবোর্ড
                        র‍্যাঙ্কিং থেকে বাদ দেওয়া হবে।
                    </li>
                    <li>
                        <strong
                            class="font-semibold text-slate-800 dark:text-gray-200"
                            >প্রাইভেসি সেটিং:</strong
                        >
                        প্রোফাইল থেকে Activity Privacy ‘Private’ করা থাকলে
                        লিডারবোর্ডে নাম ও রেকর্ড গোপন রাখা হয়।
                    </li>
                </ul>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end p-3 sm:px-6">
                <button
                    type="button"
                    @click="showInfoModal = false"
                    class="cursor-pointer rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white shadow-2xs transition hover:bg-slate-800 active:scale-95 sm:text-sm dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100"
                >
                    বুঝেছি
                </button>
            </div>
        </template>
    </BaseModal>
</template>
