<script setup lang="ts">
import { router, Link } from '@inertiajs/vue3';
import { ArrowRight, AlertTriangle } from 'lucide-vue-next';
import { ref } from 'vue';
import { kButton, kSearchbar } from 'konsta/vue';
import BlogCard from '@/components/BlogCard.vue';

defineProps({
    blogs: Object,
});

const search = ref(new URLSearchParams(window.location.search).get('q') || '');

const handleSearch = () => {
    router.get('/blogs', { q: search.value }, { preserveState: true });
};

const clearSearch = () => {
    search.value = '';
    router.get('/blogs', { q: '' });
};
</script>

<template>
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
                <k-searchbar
                    v-model="search"
                    placeholder="Search blogs..."
                    :disable-button="false"
                    @click:clear="clearSearch"
                />
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
                "{{ search }}"-এর সাথে মিল থাকা কোনো আর্টিকেল পাওয়া যায়নি।
                বানান যাচাই করুন অথবা অনুসন্ধান মুছে আবার চেষ্টা করুন।
            </p>
            <k-button @click="clearSearch"> সব আর্টিকেল দেখুন </k-button>
        </div>

        <div
            v-if="blogs.links && blogs.links.length > 3"
            class="mt-16 border-t border-slate-100 pt-6 dark:border-gray-800"
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
                            'border border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:bg-gray-800':
                                !link.active && link.url,
                            'cursor-not-allowed border border-slate-100 bg-slate-50/50 text-slate-300 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-600':
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
                            ? 'border-slate-200 bg-white text-slate-700 active:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:active:bg-gray-800'
                            : 'cursor-not-allowed border-slate-100 bg-slate-50 text-slate-300 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-600'
                    "
                >
                    &larr; Previous
                </component>
                <div
                    class="px-4 text-xs font-semibold text-slate-500 dark:text-gray-400"
                >
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
                            ? 'border-slate-200 bg-white text-slate-700 active:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:active:bg-gray-800'
                            : 'cursor-not-allowed border-slate-100 bg-slate-50 text-slate-300 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-600'
                    "
                >
                    Next &rarr;
                </component>
            </div>
        </div>
    </main>
</template>
