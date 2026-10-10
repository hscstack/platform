<script setup lang="ts">
import { router, Head, Link } from '@inertiajs/vue3';
import { Search, X, AlertTriangle, Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AdUnit from '@/components/AdUnit.vue';
import BlogCard from '@/components/BlogCard.vue';
import EmptyState from '@/components/EmptyState.vue';
import Pagination from '@/components/Pagination.vue';
import { useAuth } from '@/lib/useAuth';
import { usePermissions } from '@/lib/usePermissions';

const { can } = usePermissions();
const { user } = useAuth();

const props = defineProps<{
    blogs: any;
    filters?: {
        q?: string;
        mine?: boolean;
    };
}>();

const isMine = computed(() => Boolean(props.filters?.mine));
const searchQuery = ref(props.filters?.q || '');

const applyFilters = (newParams: Record<string, any> = {}) => {
    const params: Record<string, any> = {
        q: searchQuery.value.trim() || undefined,
        mine: isMine.value ? '1' : undefined,
        ...newParams,
    };

    Object.keys(params).forEach((k) => {
        if (!params[k]) {
            delete params[k];
        }
    });

    router.get('/blogs', params, { preserveState: true, preserveScroll: true });
};

const setMine = (val: boolean) => {
    applyFilters({ mine: val ? '1' : undefined });
};

const handleSearch = () => {
    applyFilters({ q: searchQuery.value });
};

const clearSearch = () => {
    searchQuery.value = '';
    applyFilters({ q: undefined });
};
</script>

<template>
    <Head>
        <title>Educational Blogs & Study Guides</title>
        <meta
            name="description"
            content="Read study tips, educational articles, subject advice, and preparation guides for HSC and SSC students on HSCStack."
        />
        <meta
            property="og:title"
            content="Educational Blogs & Study Guides - HSCStack"
        />
        <meta
            property="og:description"
            content="Read study tips, educational articles, subject advice, and preparation guides for HSC and SSC students on HSCStack."
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
                    HSCStack <span class="text-indigo-600">Blogs</span>
                </h1>
                <p
                    class="hidden text-xs text-slate-500 sm:mt-1 sm:block sm:text-sm dark:text-gray-400"
                >
                    পড়াশোনার টিপস, শিক্ষাসংক্রান্ত খবর এবং অন্যান্য
                    গুরুত্বপূর্ণ তথ্য পড়ুন।
                </p>
            </div>

            <Link
                v-if="can('create blogs') || can('manage blogs')"
                href="/blogs/create"
                class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-2xs transition-colors hover:bg-indigo-700 sm:text-sm"
            >
                <Plus class="h-4 w-4 stroke-[2.2]" />
                <span>Write Blog</span>
            </Link>
        </div>

        <!-- Search Bar Row -->
        <div class="mb-4 flex items-center gap-2 sm:mb-6 sm:gap-3">
            <div class="relative flex-1">
                <div
                    class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 dark:text-gray-500"
                >
                    <Search class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                </div>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search articles..."
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

            <!-- Mine Filter (only shown when logged in) -->
            <button
                v-if="user"
                type="button"
                @click="setMine(!isMine)"
                class="inline-flex shrink-0 cursor-pointer items-center justify-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-bold shadow-2xs transition active:scale-95 sm:px-3.5 sm:py-2.5"
                :class="[
                    isMine
                        ? 'border-indigo-600 bg-indigo-600 text-white'
                        : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800',
                ]"
                title="Show only my blogs"
            >
                <span>Mine</span>
            </button>
        </div>

        <div
            v-if="blogs.data.length > 0"
            class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3 lg:gap-8"
        >
            <template v-for="(blog, index) in blogs.data" :key="blog.id">
                <BlogCard :blog="blog" />

                <!-- Native Blog Ad Card (Placed after 2nd blog) -->
                <AdUnit
                    v-if="
                        index === 1 || (index === 0 && blogs.data.length === 1)
                    "
                    variant="native-blog"
                    title="Your Ad Goes Here — Reach Readers & Learners"
                    subtitle="Highlight your educational books, courses, or college programs to thousands of eager students."
                    cta-text="Learn More"
                    badge-text="Sponsored"
                />
            </template>
        </div>

        <EmptyState
            v-else
            :icon="AlertTriangle"
            variant="dashed"
            :title="
                isMine
                    ? 'আপনার এখনো কোনো আর্টিকেল নেই।'
                    : 'আপনার অনুসন্ধানের সাথে মিল থাকা কোনো আর্টিকেল পাওয়া যায়নি।'
            "
            :description="
                isMine
                    ? 'নতুন আর্টিকেল লিখতে উপরে &quot;Write Blog&quot; বাটনে ক্লিক করুন।'
                    : `&quot;${searchQuery}&quot;-এর সাথে মিল থাকা কোনো আর্টিকেল পাওয়া যায়নি। বানান যাচাই করুন অথবা অনুসন্ধান মুছে আবার চেষ্টা করুন।`
            "
        >
            <button
                type="button"
                @click="isMine ? setMine(false) : clearSearch()"
                class="cursor-pointer rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white shadow-2xs transition hover:bg-indigo-700 active:scale-95"
            >
                সব আর্টিকেল দেখুন
            </button>
        </EmptyState>

        <div
            v-if="blogs.links && blogs.links.length > 3"
            class="mt-16 border-t border-slate-100 pt-6 dark:border-gray-800"
        >
            <Pagination
                :links="blogs.links"
                :current-page="blogs.current_page"
                :last-page="blogs.last_page"
            />
        </div>
    </main>
</template>
