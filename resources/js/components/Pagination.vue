<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

const props = withDefaults(
    defineProps<{
        links: PaginationLink[];
        from?: number | null;
        to?: number | null;
        total?: number | null;
        currentPage?: number | null;
        lastPage?: number | null;
        preserveScroll?: boolean;
        preserveState?: boolean;
        showSummary?: boolean;
    }>(),
    {
        from: null,
        to: null,
        total: null,
        currentPage: null,
        lastPage: null,
        preserveScroll: true,
        preserveState: true,
        showSummary: false,
    },
);

const hasEnoughLinks = computed(() => {
    return props.links && props.links.length > 3;
});

const prevLink = computed(() => {
    if (!props.links || props.links.length === 0) {
        return null;
    }

    return props.links[0];
});

const nextLink = computed(() => {
    if (!props.links || props.links.length === 0) {
        return null;
    }

    return props.links[props.links.length - 1];
});
</script>

<template>
    <nav
        v-if="hasEnoughLinks"
        aria-label="Pagination Navigation"
        class="flex flex-col gap-3 py-4"
    >
        <!-- Desktop / Tablet Pagination Bar (sm and up) -->
        <div
            class="hidden items-center justify-between gap-4 sm:flex"
            :class="{ '!justify-center': !showSummary && total === null }"
        >
            <!-- Summary Info (If total or explicit showSummary is provided) -->
            <p
                v-if="showSummary || (from !== null && total !== null)"
                class="text-xs text-slate-500 dark:text-gray-400"
            >
                Showing
                <span class="font-semibold text-slate-700 dark:text-gray-200">
                    {{ from ?? 0 }}
                </span>
                to
                <span class="font-semibold text-slate-700 dark:text-gray-200">
                    {{ to ?? 0 }}
                </span>
                of
                <span class="font-semibold text-slate-700 dark:text-gray-200">
                    {{ total ?? 0 }}
                </span>
                results
            </p>

            <!-- Numbered and Prev/Next Links -->
            <div class="flex flex-wrap items-center justify-center gap-1">
                <component
                    :is="link.url ? Link : 'span'"
                    v-for="(link, index) in links"
                    :key="`pagination-${index}`"
                    :href="link.url || undefined"
                    :preserve-scroll="preserveScroll"
                    :preserve-state="preserveState"
                    class="inline-flex min-h-8 min-w-8 items-center justify-center rounded-lg px-2.5 py-1 text-xs font-semibold transition-all"
                    :class="[
                        link.active
                            ? 'bg-indigo-600 text-white shadow-2xs'
                            : link.url
                              ? 'border border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50 active:scale-95 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800'
                              : 'cursor-not-allowed border border-slate-100 bg-slate-50/50 text-slate-300 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-600',
                    ]"
                >
                    <span v-html="link.label"></span>
                </component>
            </div>
        </div>

        <!-- Mobile Pagination Bar (Below sm) -->
        <div class="flex flex-col gap-2.5 sm:hidden">
            <div class="flex items-center justify-between gap-2">
                <!-- Mobile Prev -->
                <component
                    :is="prevLink?.url ? Link : 'span'"
                    :href="prevLink?.url || undefined"
                    :preserve-scroll="preserveScroll"
                    :preserve-state="preserveState"
                    class="flex flex-1 items-center justify-center rounded-xl border px-3 py-2 text-center text-xs font-semibold shadow-2xs transition active:scale-95"
                    :class="
                        prevLink?.url
                            ? 'border-slate-200 bg-white text-slate-700 active:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:active:bg-gray-800'
                            : 'cursor-not-allowed border-slate-100 bg-slate-50 text-slate-300 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-600'
                    "
                >
                    &larr; Prev
                </component>

                <!-- Page indicator -->
                <div
                    v-if="currentPage !== null"
                    class="shrink-0 rounded-xl border border-slate-100 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-700 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-300"
                >
                    Page {{ currentPage }}
                    <span v-if="lastPage"> of {{ lastPage }}</span>
                </div>

                <!-- Mobile Next -->
                <component
                    :is="nextLink?.url ? Link : 'span'"
                    :href="nextLink?.url || undefined"
                    :preserve-scroll="preserveScroll"
                    :preserve-state="preserveState"
                    class="flex flex-1 items-center justify-center rounded-xl border px-3 py-2 text-center text-xs font-semibold shadow-2xs transition active:scale-95"
                    :class="
                        nextLink?.url
                            ? 'border-slate-200 bg-white text-slate-700 active:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:active:bg-gray-800'
                            : 'cursor-not-allowed border-slate-100 bg-slate-50 text-slate-300 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-600'
                    "
                >
                    Next &rarr;
                </component>
            </div>

            <!-- Mobile Summary Info -->
            <p
                v-if="showSummary || (from !== null && total !== null)"
                class="text-center text-[11px] text-slate-400 dark:text-gray-500"
            >
                Showing {{ from ?? 0 }} to {{ to ?? 0 }} of
                {{ total ?? 0 }} results
            </p>
        </div>
    </nav>
</template>
