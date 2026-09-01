<script setup lang="ts">
import { router, Head } from '@inertiajs/vue3';
import { Search, X, ArrowRight, AlertTriangle } from 'lucide-vue-next';
import { ref } from 'vue';
import BlogCard from '@/components/BlogCard.vue';
import Pagination from '@/components/Pagination.vue';

defineProps({
    blogs: Object,
});

const searchQuery = ref(
    new URLSearchParams(window.location.search).get('q') || '',
);

const handleSearch = () => {
    router.get('/blogs', { q: searchQuery.value }, { preserveState: true });
};

const clearSearch = () => {
    searchQuery.value = '';
    router.get('/blogs', { q: '' });
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

    <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div
            class="mb-10 flex flex-col gap-6 border-b border-slate-100 pb-6 lg:flex-row lg:items-center lg:justify-between dark:border-gray-800"
        >
            <div>
                <h1
                    class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-gray-100"
                >
                    Our Journal
                </h1>
                <p class="mt-2 text-sm text-slate-500 dark:text-gray-400">
                    পড়াশোনার টিপস, শিক্ষাসংক্রান্ত খবর এবং অন্যান্য
                    গুরুত্বপূর্ণ তথ্য পড়ুন।
                </p>
            </div>

            <div class="w-full lg:max-w-md">
                <div class="flex flex-col gap-2.5 sm:flex-row">
                    <div class="relative flex-1">
                        <div
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 dark:text-gray-500"
                        >
                            <Search class="h-5 w-5" />
                        </div>

                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="আর্টিকেল খুঁজুন..."
                            @keyup.enter="handleSearch"
                            class="w-full rounded-xl border border-slate-200 bg-white py-3 pr-10 pl-11 text-sm text-slate-900 shadow-sm transition-all placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500"
                        />

                        <button
                            v-if="searchQuery"
                            @click="clearSearch"
                            type="button"
                            class="absolute top-1/2 right-3 -translate-y-1/2 rounded-md p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-400"
                            aria-label="Clear search"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <button
                        @click="handleSearch"
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 active:scale-[0.98]"
                    >
                        <span>Search</span>
                        <ArrowRight class="hidden h-4 w-4 sm:block" />
                    </button>
                </div>
            </div>
        </div>

        <div
            v-if="blogs.data.length > 0"
            class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3 lg:gap-8"
        >
            <BlogCard v-for="blog in blogs.data" :key="blog.id" :blog="blog" />
        </div>

        <div
            v-else
            class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-16 text-center dark:border-gray-700 dark:bg-gray-950"
        >
            <div
                class="mb-4 rounded-xl bg-white p-3 text-slate-400 shadow-sm dark:bg-gray-900 dark:text-gray-500"
            >
                <AlertTriangle class="h-8 w-8" />
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-gray-100">
                আপনার অনুসন্ধানের সাথে মিল থাকা কোনো আর্টিকেল পাওয়া যায়নি।
            </h3>
            <p class="mt-1 max-w-sm text-sm text-slate-500 dark:text-gray-400">
                "{{ searchQuery }}"-এর সাথে মিল থাকা কোনো আর্টিকেল পাওয়া
                যায়নি। বানান যাচাই করুন অথবা অনুসন্ধান মুছে আবার চেষ্টা করুন।
            </p>
            <button
                @click="clearSearch"
                class="mt-5 inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 active:scale-[0.98]"
            >
                সব আর্টিকেল দেখুন
            </button>
        </div>

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
