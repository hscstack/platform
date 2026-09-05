<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { computed } from 'vue';
import BlogCard from '@/components/BlogCard.vue';
import CourseSwitcher from '@/components/CourseSwitcher.vue';
import EmptyState from '@/components/EmptyState.vue';
import ForumPostCard from '@/components/forum/ForumPostCard.vue';
import HomeHeader from '@/components/HomeHeader.vue';
import NoticeDialog from '@/components/NoticeDialog.vue';
import PwaInstallPrompt from '@/components/PwaInstallPrompt.vue';
import SubjectCard from '@/components/SubjectCard.vue';

import { globalSearchQuery } from '@/lib/searchStore';

const props = defineProps({
    subjects: Array,
    notice: Object,
    featured_blogs: Array,
    trending_posts: Array,
});

const subjects = props.subjects as Array<{
    id: number;
    name: string;
    english_name?: string | null;
    slug: string;
    course?: string;
    tailwind_format: string;
    icon: string;
    nodes_count?: number;
}>;

const filteredSubjects = computed(() => {
    const q = globalSearchQuery.value.toLowerCase().trim();

    if (!q) {
        return subjects;
    }

    return subjects.filter(
        (subject) =>
            (subject.name && subject.name.toLowerCase().includes(q)) ||
            (subject.english_name &&
                subject.english_name.toLowerCase().includes(q)) ||
            (subject.slug && subject.slug.toLowerCase().includes(q)),
    );
});
</script>

<template>
    <Head>
        <title>HSC & SSC Study Resources, Video Lectures & Notes</title>
        <meta
            name="description"
            content="Free curated open learning platform for HSC and SSC students in Bangladesh with topic-wise video lectures, PDFs, notes, and question banks."
        />
        <meta
            property="og:title"
            content="HSCStack - HSC & SSC Learning Platform"
        />
        <meta
            property="og:description"
            content="Free curated open learning platform for HSC and SSC students in Bangladesh with topic-wise video lectures, PDFs, notes, and question banks."
        />
    </Head>

    <NoticeDialog v-if="notice" :notice="notice" />

    <HomeHeader />

    <main class="mx-auto max-w-7xl px-4 pb-20 sm:px-6">
        <PwaInstallPrompt variant="banner" class="mb-6" />
        <CourseSwitcher />
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <SubjectCard
                v-for="subject in filteredSubjects"
                :key="subject.id"
                :subject="subject"
            />
        </div>

        <EmptyState
            v-if="filteredSubjects.length === 0"
            :icon="Search"
            variant="dashed"
            :title="`No subjects found matching &quot;${globalSearchQuery}&quot;`"
        >
            <button
                type="button"
                @click="globalSearchQuery = ''"
                class="cursor-pointer text-xs font-bold text-indigo-600 hover:underline dark:text-indigo-400"
            >
                Show all subjects
            </button>
        </EmptyState>

        <div
            v-if="trending_posts?.length"
            class="mt-10 border-t border-slate-100 pt-8 sm:mt-12 sm:pt-10 dark:border-gray-800"
        >
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h2
                        class="text-2xl font-bold tracking-tight text-slate-900 dark:text-gray-100"
                    >
                        Trending Discussions
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-gray-400">
                        ফোরামের সাম্প্রতিক প্রশ্ন ও উত্তরগুলো দেখুন
                    </p>
                </div>
                <!-- Desktop Link -->
                <Link
                    href="/forum"
                    class="group hidden items-center gap-2 text-sm font-semibold text-indigo-600 transition hover:text-indigo-700 sm:inline-flex dark:text-indigo-400 dark:hover:text-indigo-300"
                >
                    <span>Visit Forum</span>
                    <span
                        class="transition-transform duration-200 group-hover:translate-x-1"
                    >
                        →
                    </span>
                </Link>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4">
                <ForumPostCard
                    v-for="post in trending_posts"
                    :key="post.id"
                    :post="post"
                />
            </div>

            <!-- Mobile Bottom Link -->
            <div class="mt-4 text-center sm:hidden">
                <Link
                    href="/forum"
                    class="group inline-flex items-center gap-1.5 py-1 text-xs font-bold text-indigo-600 transition hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
                >
                    <span>Visit Forum</span>
                    <span
                        class="transition-transform duration-200 group-hover:translate-x-1"
                    >
                        →
                    </span>
                </Link>
            </div>
        </div>

        <div
            v-if="featured_blogs?.length"
            class="mt-10 border-t border-slate-100 pt-8 sm:mt-12 sm:pt-10 dark:border-gray-800"
        >
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h2
                        class="text-2xl font-bold tracking-tight text-slate-900 dark:text-gray-100"
                    >
                        Featured Blogs
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-gray-400">
                        <Link
                            href="/about-us"
                            class="underline decoration-slate-300 underline-offset-2 transition-colors hover:text-indigo-600 hover:decoration-indigo-400 dark:decoration-gray-600 dark:hover:text-indigo-400"
                        >
                            কন্ট্রিবিউটরদের
                        </Link>
                        সাম্প্রতিক লেখাগুলো পড়ুন
                    </p>
                </div>
                <!-- Desktop Link (sm and up) -->
                <Link
                    href="/blogs"
                    class="group hidden items-center gap-2 text-sm font-semibold text-indigo-600 transition hover:text-indigo-700 sm:inline-flex dark:text-indigo-400 dark:hover:text-indigo-300"
                >
                    <span>See all articles</span>
                    <span
                        class="transition-transform duration-200 group-hover:translate-x-1"
                    >
                        →
                    </span>
                </Link>
            </div>

            <div
                class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3"
            >
                <BlogCard
                    v-for="blog in featured_blogs"
                    :key="blog.id"
                    :blog="blog"
                />
            </div>

            <!-- Mobile Bottom Link (sm:hidden) -->
            <div class="mt-4 text-center sm:hidden">
                <Link
                    href="/blogs"
                    class="group inline-flex items-center gap-1.5 py-1 text-xs font-bold text-indigo-600 transition hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
                >
                    <span>See all articles</span>
                    <span
                        class="transition-transform duration-200 group-hover:translate-x-1"
                    >
                        →
                    </span>
                </Link>
            </div>
        </div>
    </main>
</template>
