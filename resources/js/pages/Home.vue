<script setup lang="ts">
import {} from '@inertiajs/vue3';
import { kButton, kBlock } from 'konsta/vue';
import { ref, computed } from 'vue';
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

        <k-block v-if="filteredSubjects.length === 0" strong inset>
            <p class="text-sm font-semibold text-slate-400 dark:text-gray-500">
                No subjects found matching "{{ searchQuery }}"
            </p>
            <k-button @click="searchQuery = ''"> Show all subjects </k-button>
        </k-block>

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
                <k-button outline rounded href="/blogs">
                    See all articles →
                </k-button>
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
