<script setup lang="ts">
import {
    ExternalLink,
    Megaphone,
    Sparkles,
    X,
    BookOpen,
    Tv,
    GraduationCap,
} from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    variant?:
        | 'leaderboard'
        | 'in-feed'
        | 'sidebar'
        | 'compact'
        | 'grid-tile'
        | 'floating-bottom'
        | 'in-article'
        | 'video-overlay'
        | 'native-peer'
        | 'native-product'
        | 'native-blog';
    title?: string;
    subtitle?: string;
    ctaText?: string;
    ctaHref?: string;
    badgeText?: string;
}

withDefaults(defineProps<Props>(), {
    variant: 'leaderboard',
    title: 'Your Ad Goes Here',
    subtitle:
        'Promote your books, courses, coaching, or tools to thousands of active students.',
    ctaText: 'Advertise Here',
    ctaHref: 'https://facebook.com/hscstackbd',
    badgeText: 'Sponsored',
});

const isDismissed = ref(false);
</script>

<template>
    <div
        v-if="!isDismissed"
        :class="[
            variant === 'native-blog' || variant === 'native-product'
                ? 'flex h-full w-full flex-col'
                : 'w-full',
        ]"
    >
        <!-- 1. Leaderboard / Full-Width Horizontal Banner -->
        <aside
            v-if="variant === 'leaderboard'"
            aria-label="Sponsored advertisement"
            class="group relative overflow-hidden rounded-2xl border border-dashed border-amber-300/80 bg-gradient-to-r from-amber-500/10 via-amber-400/5 to-orange-500/10 p-4 transition-all duration-300 hover:border-amber-400 sm:p-5 dark:border-amber-500/30 dark:from-amber-500/10 dark:via-yellow-500/5 dark:to-orange-500/10"
        >
            <div
                class="pointer-events-none absolute -right-10 -bottom-10 h-32 w-32 rounded-full bg-amber-400/15 blur-2xl transition-all duration-300 group-hover:scale-125 dark:bg-amber-400/10"
            ></div>

            <div
                class="relative z-10 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-start gap-3 sm:items-center">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-md shadow-amber-500/20"
                    >
                        <Megaphone class="h-5 w-5" />
                    </div>

                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span
                                class="inline-block rounded-md bg-amber-500/20 px-1.5 py-0.5 text-[10px] font-extrabold tracking-wider text-amber-800 uppercase dark:bg-amber-400/20 dark:text-amber-300"
                            >
                                {{ badgeText }}
                            </span>
                            <h4
                                class="text-sm font-bold tracking-tight text-slate-900 sm:text-base dark:text-gray-100"
                            >
                                {{ title }}
                            </h4>
                        </div>
                        <p
                            class="mt-0.5 text-xs text-slate-600 dark:text-gray-400"
                        >
                            {{ subtitle }}
                        </p>
                    </div>
                </div>

                <div
                    class="flex shrink-0 items-center gap-2 self-start sm:self-auto"
                >
                    <a
                        :href="ctaHref"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-amber-600 to-orange-600 px-3.5 py-2 text-xs font-bold text-white shadow-xs transition-all hover:from-amber-700 hover:to-orange-700 hover:shadow-md active:scale-95"
                    >
                        <span>{{ ctaText }}</span>
                        <ExternalLink class="h-3.5 w-3.5" />
                    </a>
                </div>
            </div>
        </aside>

        <!-- 2. In-Feed Native Card (matches Forum & Blog card lists) -->
        <aside
            v-else-if="variant === 'in-feed'"
            aria-label="Sponsored post advertisement"
            class="group relative overflow-hidden rounded-2xl border border-dashed border-amber-300/80 bg-gradient-to-br from-amber-50/70 via-white to-orange-50/40 p-4 shadow-2xs transition-all duration-200 hover:border-amber-400 dark:border-amber-500/30 dark:from-gray-900 dark:via-gray-900/90 dark:to-amber-950/20"
        >
            <div class="mb-2 flex items-center">
                <span
                    class="inline-flex items-center rounded-md bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800 uppercase dark:bg-amber-950/60 dark:text-amber-300"
                >
                    {{ badgeText }}
                </span>
            </div>

            <div class="flex items-start gap-3">
                <div
                    class="hidden h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 sm:flex dark:bg-amber-900/30 dark:text-amber-400"
                >
                    <Megaphone class="h-6 w-6" />
                </div>
                <div class="flex-1">
                    <h4
                        class="text-sm font-bold text-slate-900 sm:text-base dark:text-gray-100"
                    >
                        {{ title }}
                    </h4>
                    <p
                        class="mt-1 text-xs leading-relaxed text-slate-600 dark:text-gray-400"
                    >
                        {{ subtitle }}
                    </p>
                    <div class="mt-3">
                        <a
                            :href="ctaHref"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-xs font-bold text-amber-900 shadow-2xs transition hover:bg-amber-50 active:scale-95 dark:border-amber-700/60 dark:bg-gray-800 dark:text-amber-200 dark:hover:bg-gray-700"
                        >
                            <span>{{ ctaText }}</span>
                            <ExternalLink class="h-3 w-3" />
                        </a>
                    </div>
                </div>
            </div>
        </aside>

        <!-- 3. Sidebar / Skyscraper Card (for desktop right column / sidebars) -->
        <aside
            v-else-if="variant === 'sidebar'"
            aria-label="Sponsored advertisement"
            class="group relative overflow-hidden rounded-2xl border border-dashed border-amber-300/80 bg-gradient-to-b from-amber-50/70 via-white to-amber-50/40 p-5 shadow-xs transition-all duration-300 hover:border-amber-400 dark:border-amber-500/30 dark:from-amber-950/20 dark:via-gray-900 dark:to-gray-900"
        >
            <div class="mb-3 flex items-center justify-between">
                <span
                    class="rounded-md bg-amber-500/15 px-2 py-0.5 text-[10px] font-extrabold tracking-wider text-amber-800 uppercase dark:text-amber-300"
                >
                    {{ badgeText }}
                </span>
                <span
                    class="text-[10px] font-medium text-slate-400 dark:text-gray-500"
                    >Sponsored Widget</span
                >
            </div>

            <div class="text-center">
                <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-tr from-amber-500 to-orange-500 text-white shadow-md shadow-amber-500/20"
                >
                    <Megaphone class="h-6 w-6" />
                </div>

                <h4
                    class="mt-3.5 text-sm font-bold text-slate-900 dark:text-gray-100"
                >
                    {{ title }}
                </h4>
                <p
                    class="mt-1.5 text-xs leading-relaxed text-slate-600 dark:text-gray-400"
                >
                    {{ subtitle }}
                </p>

                <div
                    class="mt-4 rounded-xl border border-amber-200/60 bg-white/80 p-2.5 text-center dark:border-amber-900/40 dark:bg-gray-800/80"
                >
                    <span
                        class="text-[11px] font-semibold text-amber-700 dark:text-amber-300"
                    >
                        ⭐ Special Student Offer
                    </span>
                </div>

                <a
                    :href="ctaHref"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="mt-4 inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-gradient-to-r from-amber-600 to-orange-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs transition hover:from-amber-700 hover:to-orange-700 active:scale-95"
                >
                    <span>{{ ctaText }}</span>
                    <ExternalLink class="h-3.5 w-3.5" />
                </a>
            </div>
        </aside>

        <!-- 4. Compact Inline Ribbon / Header Announcement Bar -->
        <aside
            v-else-if="variant === 'compact'"
            aria-label="Sponsored advertisement banner"
            class="group flex items-center justify-between gap-3 rounded-xl border border-dashed border-amber-300/80 bg-gradient-to-r from-amber-50/80 via-white to-amber-50/80 px-3.5 py-2 transition-all hover:border-amber-400 dark:border-amber-500/30 dark:from-amber-950/30 dark:via-gray-900 dark:to-amber-950/20"
        >
            <div class="flex items-center gap-2.5 overflow-hidden">
                <span
                    class="shrink-0 rounded bg-amber-500/20 px-1.5 py-0.5 text-[9px] font-black tracking-wide text-amber-800 uppercase dark:bg-amber-400/20 dark:text-amber-300"
                >
                    {{ badgeText }}
                </span>
                <span
                    class="truncate text-xs font-medium text-slate-800 dark:text-gray-200"
                >
                    <strong class="font-bold text-slate-900 dark:text-white">{{
                        title
                    }}</strong>
                    — {{ subtitle }}
                </span>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <a
                    :href="ctaHref"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-xs font-bold text-amber-700 transition hover:text-amber-800 hover:underline dark:text-amber-400"
                >
                    {{ ctaText }} →
                </a>
                <button
                    type="button"
                    @click="isDismissed = true"
                    class="cursor-pointer rounded p-0.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                    aria-label="Dismiss demo ad"
                >
                    <X class="h-3.5 w-3.5" />
                </button>
            </div>
        </aside>

        <!-- 5. Grid Native Tile (Matches SubjectCard exactly: horizontal, ~75px height) -->
        <a
            v-else-if="variant === 'grid-tile'"
            :href="ctaHref"
            target="_blank"
            rel="noopener noreferrer"
            aria-label="Sponsored course tile"
            class="group relative flex touch-manipulation items-center justify-between overflow-hidden rounded-xl border border-dashed border-amber-300/90 bg-gradient-to-r from-amber-50/70 via-white to-orange-50/40 px-5 py-4.5 transition-all duration-200 hover:border-amber-400 hover:shadow-sm active:scale-[0.99] dark:border-amber-500/40 dark:from-amber-950/20 dark:via-gray-900 dark:to-gray-900"
        >
            <div class="flex min-w-0 items-center gap-4">
                <!-- Icon matching SubjectCard proportions -->
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 transition-colors duration-200 dark:bg-amber-900/40 dark:text-amber-400"
                >
                    <BookOpen class="h-5 w-5" />
                </div>

                <!-- Content matching SubjectCard structure -->
                <div class="min-w-0">
                    <div class="flex items-center gap-1.5">
                        <h3
                            class="truncate text-base font-bold text-slate-800 transition-colors group-hover:text-amber-600 dark:text-gray-200 dark:group-hover:text-amber-400"
                        >
                            {{ title }}
                        </h3>
                        <span
                            class="shrink-0 rounded-md bg-amber-500/20 px-1.5 py-0.5 text-[9px] font-black tracking-wider text-amber-800 uppercase dark:bg-amber-400/20 dark:text-amber-300"
                        >
                            {{ badgeText }}
                        </span>
                    </div>
                    <p
                        class="mt-0.5 truncate text-xs font-semibold text-slate-400 dark:text-gray-500"
                    >
                        {{ subtitle }}
                    </p>
                </div>
            </div>

            <!-- Chevron indicator matching SubjectCard -->
            <div class="flex items-center pl-4">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5 text-amber-500 transition-all duration-200 group-hover:translate-x-0.5 dark:text-amber-400"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                >
                    <path
                        fill-rule="evenodd"
                        d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                        clip-rule="evenodd"
                    />
                </svg>
            </div>
        </a>

        <!-- 6. Floating Bottom Sticky Dock Pill -->
        <aside
            v-else-if="variant === 'floating-bottom'"
            aria-label="Sponsored floating announcement"
            class="fixed bottom-4 left-1/2 z-40 w-[92%] max-w-xl -translate-x-1/2 shadow-2xl transition-all"
        >
            <div
                class="flex items-center justify-between gap-3 rounded-2xl border border-amber-300/90 bg-white/95 px-4 py-3 backdrop-blur-xl dark:border-amber-500/40 dark:bg-gray-900/95"
            >
                <div class="flex min-w-0 items-center gap-2.5">
                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white"
                    >
                        <Megaphone class="h-4 w-4" />
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-1.5">
                            <span
                                class="rounded bg-amber-100 px-1.5 py-0.5 text-[9px] font-bold text-amber-800 uppercase dark:bg-amber-900/50 dark:text-amber-300"
                            >
                                {{ badgeText }}
                            </span>
                            <span
                                class="truncate text-xs font-bold text-slate-900 dark:text-white"
                            >
                                {{ title }}
                            </span>
                        </div>
                        <p
                            class="truncate text-[11px] text-slate-500 dark:text-gray-400"
                        >
                            {{ subtitle }}
                        </p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <a
                        :href="ctaHref"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="rounded-xl bg-amber-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-amber-700"
                    >
                        {{ ctaText }}
                    </a>
                    <button
                        type="button"
                        @click="isDismissed = true"
                        class="cursor-pointer rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-gray-800"
                        title="Close ad"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </aside>

        <!-- 7. In-Article Editorial Box (Native callout between reading paragraphs) -->
        <aside
            v-else-if="variant === 'in-article'"
            aria-label="Sponsored recommendation"
            class="my-6 rounded-2xl border-l-4 border-amber-500 bg-amber-50/60 p-4.5 transition hover:bg-amber-50 dark:bg-amber-950/20 dark:hover:bg-amber-950/30"
        >
            <div class="mb-1.5 flex items-center justify-between">
                <span
                    class="inline-flex items-center gap-1 text-[11px] font-bold tracking-wide text-amber-800 uppercase dark:text-amber-300"
                >
                    <Sparkles class="h-3 w-3" />
                    {{ badgeText }} Recommendation
                </span>
                <span class="text-[10px] text-slate-400 dark:text-gray-500"
                    >Sponsored Notice</span
                >
            </div>
            <h4 class="text-sm font-bold text-slate-900 dark:text-gray-100">
                {{ title }}
            </h4>
            <p
                class="mt-1 text-xs leading-relaxed text-slate-700 dark:text-gray-300"
            >
                {{ subtitle }}
            </p>
            <div class="mt-3">
                <a
                    :href="ctaHref"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-1 text-xs font-bold text-amber-700 hover:text-amber-800 hover:underline dark:text-amber-400"
                >
                    <span>{{ ctaText }}</span>
                    <ExternalLink class="h-3 w-3" />
                </a>
            </div>
        </aside>

        <!-- 8. Video Player Overlay Banner (Attached to media canvas) -->
        <aside
            v-else-if="variant === 'video-overlay'"
            aria-label="Sponsored video advertisement"
            class="flex items-center justify-between gap-3 rounded-xl border border-amber-300/80 bg-gradient-to-r from-amber-600 to-orange-600 p-2.5 text-white shadow-sm dark:border-amber-500/50"
        >
            <div class="flex min-w-0 items-center gap-2.5">
                <div
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white/20 text-white"
                >
                    <Tv class="h-4 w-4" />
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-1.5">
                        <span
                            class="rounded bg-black/30 px-1.5 py-0.5 text-[9px] font-black tracking-wider uppercase"
                        >
                            {{ badgeText }}
                        </span>
                        <span class="truncate text-xs font-bold">{{
                            title
                        }}</span>
                    </div>
                    <p class="truncate text-[10px] text-amber-100">
                        {{ subtitle }}
                    </p>
                </div>
            </div>

            <a
                :href="ctaHref"
                target="_blank"
                rel="noopener noreferrer"
                class="shrink-0 rounded-lg bg-white px-3 py-1 text-xs font-bold text-amber-900 shadow-2xs transition hover:bg-amber-50"
            >
                {{ ctaText }}
            </a>
        </aside>

        <!-- 9. Native Peer Ad (Matches People / Peers list row) -->
        <a
            v-else-if="variant === 'native-peer'"
            :href="ctaHref"
            target="_blank"
            rel="noopener noreferrer"
            class="group flex items-center justify-between gap-3.5 p-3.5 transition hover:bg-slate-50/70 sm:p-4 dark:hover:bg-gray-800/40"
        >
            <!-- Left: Avatar + Details -->
            <div class="flex min-w-0 items-center gap-3.5">
                <!-- Avatar: Clean circular avatar matching organic peer rows -->
                <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-indigo-700 text-base font-black text-white ring-2 ring-slate-100 transition group-hover:ring-indigo-200 sm:h-12 sm:w-12 dark:ring-gray-800 dark:group-hover:ring-indigo-900/60"
                >
                    M
                </div>

                <!-- User Info -->
                <div class="min-w-0 flex-1 space-y-0.5">
                    <!-- Name + Sponsored Badge -->
                    <div class="flex items-center gap-1.5">
                        <span
                            class="truncate text-sm font-bold text-slate-900 transition-colors group-hover:text-indigo-600 dark:text-gray-100 dark:group-hover:text-indigo-400"
                        >
                            {{ title }}
                        </span>
                        <span
                            class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-500 dark:bg-gray-800 dark:text-gray-400"
                        >
                            {{ badgeText }}
                        </span>
                    </div>

                    <!-- Institution / Details Row -->
                    <div
                        class="flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-gray-400"
                    >
                        <span class="max-w-[220px] truncate sm:max-w-xs">
                            {{ subtitle }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Right: Action Button matching organic row style -->
            <div class="shrink-0 pl-2">
                <span
                    class="inline-flex h-8 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-2.5 text-xs font-semibold text-slate-700 shadow-2xs transition group-hover:border-indigo-300 group-hover:bg-indigo-50 group-hover:text-indigo-600 sm:px-3 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:group-hover:border-indigo-500/40 dark:group-hover:bg-indigo-950/40 dark:group-hover:text-indigo-300"
                >
                    <span>{{ ctaText }}</span>
                    <ExternalLink class="h-3 w-3 stroke-[2]" />
                </span>
            </div>
        </a>

        <!-- 10. Native Product / Partner Card (Matches More From Us / Products card grid) -->
        <div
            v-else-if="variant === 'native-product'"
            class="group flex h-full flex-1 flex-col justify-between overflow-hidden rounded-2xl border border-dashed border-amber-300/90 bg-gradient-to-br from-amber-50/70 via-white to-orange-50/40 p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:border-amber-400 hover:shadow-md sm:p-6 dark:border-amber-500/40 dark:from-gray-900 dark:via-gray-900/90 dark:to-amber-950/20"
        >
            <div class="flex flex-1 flex-col">
                <!-- Image / Thumbnail Header matching aspect-[16/9] -->
                <div
                    class="relative flex aspect-[16/9] flex-col items-center justify-center overflow-hidden rounded-xl border border-amber-200/50 bg-gradient-to-br from-amber-500/20 via-orange-500/10 to-amber-600/20 p-6 text-center dark:border-amber-500/20"
                >
                    <div
                        class="mb-2 flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-md shadow-amber-500/25 transition-transform duration-200 group-hover:scale-110"
                    >
                        <GraduationCap class="h-6 w-6 stroke-[2.2]" />
                    </div>
                    <span
                        class="text-xs font-black tracking-wider text-amber-800 uppercase dark:text-amber-300"
                    >
                        Featured Educational Partner
                    </span>
                    <span
                        class="text-[11px] font-medium text-amber-700/80 dark:text-amber-400/80"
                    >
                        Verified Learning & Study Tool
                    </span>
                </div>

                <!-- Title & Badge -->
                <div class="mt-5 flex items-center justify-between gap-2">
                    <h2
                        class="min-w-0 text-xl font-bold text-slate-900 transition-colors group-hover:text-amber-700 dark:text-gray-100 dark:group-hover:text-amber-400"
                    >
                        {{ title }}
                    </h2>
                    <span
                        class="inline-flex shrink-0 items-center rounded-lg bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800 uppercase dark:bg-amber-950/60 dark:text-amber-300"
                    >
                        {{ badgeText }}
                    </span>
                </div>

                <!-- Description -->
                <p
                    class="mt-2.5 text-sm leading-relaxed text-slate-600 dark:text-gray-400"
                >
                    {{ subtitle }}
                </p>
            </div>

            <!-- Action Button matching ProductCard -->
            <div
                class="mt-6 border-t border-amber-200/60 pt-4 sm:mt-auto dark:border-amber-500/20"
            >
                <a
                    :href="ctaHref"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-amber-600 to-orange-600 px-4 py-2.5 text-sm font-bold text-white shadow-xs transition-all hover:from-amber-700 hover:to-orange-700 active:scale-98"
                >
                    <span>{{ ctaText }}</span>
                    <ExternalLink class="h-4 w-4" />
                </a>
            </div>
        </div>

        <!-- 11. Native Blog Card (Matches BlogCard.vue responsive structure & height) -->
        <a
            v-else-if="variant === 'native-blog'"
            :href="ctaHref"
            target="_blank"
            rel="noopener noreferrer"
            class="group relative flex h-full flex-1 touch-manipulation flex-row items-center overflow-hidden rounded-2xl border border-dashed border-amber-300/80 bg-gradient-to-br from-amber-50/50 via-white to-orange-50/30 p-3 shadow-xs transition-all duration-200 hover:border-amber-400 hover:shadow-sm active:scale-[0.99] sm:flex-col sm:items-stretch sm:p-0 sm:active:scale-100 dark:border-amber-500/30 dark:from-gray-900 dark:via-gray-900/95 dark:to-amber-950/20"
        >
            <!-- Image / Thumbnail Header matching BlogCard -->
            <div
                class="relative flex h-20 w-24 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-gradient-to-br from-amber-500/20 to-orange-500/20 sm:aspect-[16/9] sm:h-auto sm:w-full sm:rounded-none"
            >
                <div
                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-xs transition-transform duration-300 group-hover:scale-110 sm:h-12 sm:w-12"
                >
                    <BookOpen class="h-5 w-5 sm:h-6 sm:w-6" />
                </div>

                <!-- Desktop Sponsored Badge (on image) -->
                <div
                    class="absolute top-2.5 left-2.5 hidden rounded-md bg-amber-600 px-2 py-0.5 text-[10px] font-black tracking-wider text-white uppercase shadow-xs sm:block"
                >
                    {{ badgeText }}
                </div>
            </div>

            <!-- Card Body -->
            <div
                class="flex min-w-0 flex-1 flex-col justify-between pl-3 sm:p-4.5"
            >
                <div>
                    <!-- Top Row: Mobile Sponsored Badge -->
                    <div
                        class="mb-1 flex items-center gap-1.5 text-[11px] sm:hidden"
                    >
                        <span
                            class="rounded-md bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-800 dark:bg-amber-950/80 dark:text-amber-300"
                        >
                            {{ badgeText }}
                        </span>
                        <span class="text-slate-400 dark:text-gray-500"
                            >Partner Guide</span
                        >
                    </div>

                    <!-- Title -->
                    <h3
                        class="line-clamp-2 text-sm leading-snug font-bold text-slate-900 transition-colors duration-150 group-hover:text-amber-700 sm:text-base dark:text-gray-100 dark:group-hover:text-amber-400"
                    >
                        {{ title }}
                    </h3>

                    <!-- Subtitle / Excerpt -->
                    <p
                        class="mt-1.5 line-clamp-2 hidden text-xs leading-relaxed text-slate-600 sm:block dark:text-gray-400"
                    >
                        {{ subtitle }}
                    </p>
                </div>

                <!-- Footer Action & Baseline Alignment -->
                <div
                    class="mt-1.5 flex items-center justify-between pt-0.5 sm:mt-auto sm:border-t sm:border-amber-200/60 sm:pt-3 dark:sm:border-amber-500/20"
                >
                    <span
                        class="text-xs font-bold text-amber-700 dark:text-amber-400"
                        >{{ ctaText }} →</span
                    >
                    <span
                        class="text-[11px] font-medium text-slate-400 dark:text-gray-500"
                        >Sponsored</span
                    >
                </div>
            </div>
        </a>
    </div>
</template>
