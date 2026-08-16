<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { kButton } from 'konsta/vue';
import AIBanner from '@/components/AIBanner.vue';
import BlogCard from '@/components/BlogCard.vue';
import CourseSwitcher from '@/components/CourseSwitcher.vue';
import FAQSection from '@/components/FAQSection.vue';
import HomeHeader from '@/components/HomeHeader.vue';
import NoticeDialog from '@/components/NoticeDialog.vue';
import PwaInstallPrompt from '@/components/PwaInstallPrompt.vue';
import SubjectCard from '@/components/SubjectCard.vue';

const props = defineProps({
    subjects: Array,
    notice: Object,
    featured_blogs: Array,
});

const subjects = props.subjects;
const searchQuery = ref('');

const filteredSubjects = computed(() => {
    return subjects.filter((subject) =>
        subject.name.toLowerCase().includes(searchQuery.value.toLowerCase()),
    );
});
</script>

<template>
    <NoticeDialog v-if="notice" :notice="notice" />

    <PwaInstallPrompt v-if="!notice" variant="modal" />

    <HomeHeader v-model="searchQuery" />

    <main class="mx-auto max-w-7xl px-4 pb-20 sm:px-6">
        <PwaInstallPrompt variant="banner" class="mb-6" />
        <AIBanner />
        <CourseSwitcher />
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <SubjectCard
                v-for="subject in filteredSubjects"
                :key="subject.id"
                :subject="subject"
            />
        </div>

        <div
            v-if="filteredSubjects.length === 0"
            class="rounded-xl border border-dashed border-slate-200 bg-white/50 py-12 text-center dark:border-gray-700 dark:bg-gray-900/50"
        >
            <p class="text-sm font-semibold text-slate-400 dark:text-gray-500">
                No subjects found matching "{{ searchQuery }}"
            </p>
            <k-button @click="searchQuery = ''"> Show all subjects </k-button>
        </div>

        <div
            v-if="featured_blogs?.length"
            class="mt-16 border-t border-slate-100 pt-12 dark:border-gray-800"
        >
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h2
                        class="text-2xl font-bold tracking-tight text-slate-900 dark:text-gray-100"
                    >
                        Featured Blogs
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-gray-400">
                        Read our latest articles and updates
                    </p>
                </div>
                <Link
                    href="/blogs"
                    class="group inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 transition hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
                >
                    See all articles
                    <span
                        class="transition-transform duration-200 group-hover:translate-x-1"
                    >
                        →
                    </span>
                </Link>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <BlogCard
                    v-for="blog in featured_blogs"
                    :key="blog.id"
                    :blog="blog"
                />
            </div>
        </div>
    </main>
    <!--
    <RepositoryStas
        :total-subjects="subjectCount"
        :total-resources="resourceCount"
        :total-users="contributorCount"
    /> -->

    <FAQSection />
</template>
