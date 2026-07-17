<script setup lang="ts">
import { router, Link } from '@inertiajs/vue3';
import { Search, X, ArrowRight, AlertTriangle } from 'lucide-vue-next';
import { ref } from 'vue';
import BlogCard from '@/components/BlogCard.vue';

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
    <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div
            class="mb-10 flex flex-col gap-6 border-b border-slate-100 pb-6 lg:flex-row lg:items-center lg:justify-between"
        >
            <div>
                <h1
                    class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl"
                >
                    Our Journal
                </h1>
                <p class="mt-2 text-sm text-slate-500">
                    পড়াশোনার টিপস, শিক্ষাসংক্রান্ত খবর এবং অন্যান্য
                    গুরুত্বপূর্ণ তথ্য পড়ুন।
                </p>
            </div>

            <div class="w-full lg:max-w-md">
                <div class="flex flex-col gap-2.5 sm:flex-row">
                    <div class="relative flex-1">
                        <div
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400"
                        >
                            <Search class="h-5 w-5" />
                        </div>

                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="আর্টিকেল খুঁজুন..."
                            @keyup.enter="handleSearch"
                            class="w-full rounded-xl border border-slate-200 py-3 pr-10 pl-11 text-sm text-slate-900 shadow-sm transition-all placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none"
                        />

                        <button
                            v-if="searchQuery"
                            @click="clearSearch"
                            type="button"
                            class="absolute top-1/2 right-3 -translate-y-1/2 rounded-md p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
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
            class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3"
        >
            <BlogCard v-for="blog in blogs.data" :key="blog.id" :blog="blog" />
        </div>

        <div
            v-else
            class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-16 text-center"
        >
            <div class="mb-4 rounded-xl bg-white p-3 text-slate-400 shadow-sm">
                <AlertTriangle class="h-8 w-8" />
            </div>
            <h3 class="text-lg font-bold text-slate-900">
                আপনার অনুসন্ধানের সাথে মিল থাকা কোনো আর্টিকেল পাওয়া যায়নি।
            </h3>
            <p class="mt-1 max-w-sm text-sm text-slate-500">
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
            class="mt-16 border-t border-slate-100 pt-6"
        >
            <div class="hidden sm:flex sm:flex-wrap sm:justify-center sm:gap-2">
                <div
                    class="hidden sm:flex sm:flex-wrap sm:justify-center sm:gap-2"
                >
                    <component
                        :is="link.url ? Link : 'span'"
                        v-for="(link, index) in blogs.links"
                        :key="index"
                        :href="link.url"
                        class="rounded-xl px-4 py-2 text-sm font-medium transition"
                        :class="{
                            'bg-indigo-600 text-white shadow-sm': link.active,
                            'border border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50':
                                !link.active && link.url,
                            'cursor-not-allowed border border-slate-100 bg-slate-50/50 text-slate-300':
                                !link.url,
                        }"
                    >
                        <span v-html="link.label"></span>
                    </component>
                </div>
            </div>

            <div class="flex items-center justify-between px-2 sm:hidden">
                <component
                    :is="blogs.links[0].url ? Link : 'span'"
                    :href="blogs.links[0].url"
                    class="flex-1 rounded-xl border px-4 py-3 text-center text-sm font-semibold transition"
                    :class="
                        blogs.links[0].url
                            ? 'border-slate-200 bg-white text-slate-700 active:bg-slate-50'
                            : 'cursor-not-allowed border-slate-100 bg-slate-50 text-slate-300'
                    "
                >
                    &larr; Previous
                </component>
                <div class="px-4 text-xs font-semibold text-slate-500">
                    Page {{ blogs.current_page }}
                </div>
                <component
                    :is="
                        blogs.links[blogs.links.length - 1].url ? Link : 'span'
                    "
                    :href="blogs.links[blogs.links.length - 1].url"
                    class="flex-1 rounded-xl border px-4 py-3 text-center text-sm font-semibold transition"
                    :class="
                        blogs.links[blogs.links.length - 1].url
                            ? 'border-slate-200 bg-white text-slate-700 active:bg-slate-50'
                            : 'cursor-not-allowed border-slate-100 bg-slate-50 text-slate-300'
                    "
                >
                    Next &rarr;
                </component>
            </div>
        </div>
    </main>
</template>
