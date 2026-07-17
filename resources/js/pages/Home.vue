<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import BlogCard from '@/components/BlogCard.vue';
import HomeHeader from '@/components/HomeHeader.vue';
import NoticeDialog from '@/components/NoticeDialog.vue';
import RepositoryStas from '@/components/RepositoryStas.vue';
import SubjectCard from '@/components/SubjectCard.vue';

const props = defineProps({
    subjects: Array,
    subjectCount: Number,
    resourceCount: Number,
    contributorCount: Number,
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

    <HomeHeader v-model="searchQuery" />

    <main class="mx-auto max-w-7xl px-4 pb-20 sm:px-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <SubjectCard
                v-for="subject in filteredSubjects"
                :key="subject.id"
                :subject="subject"
            />
        </div>

        <div
            v-if="filteredSubjects.length === 0"
            class="rounded-xl border border-dashed border-slate-200 bg-white/50 py-12 text-center"
        >
            <p class="text-sm font-semibold text-slate-400">
                No subjects found matching "{{ searchQuery }}"
            </p>
            <button
                @click="searchQuery = ''"
                class="mt-2 text-xs font-bold text-indigo-600 hover:underline"
            >
                Show all subjects
            </button>
        </div>

        <div
            v-if="featured_blogs?.length"
            class="mt-16 border-t border-slate-100 pt-12"
        >
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h2
                        class="text-2xl font-bold tracking-tight text-slate-900"
                    >
                        Featured Blogs
                    </h2>
                    <p class="text-sm text-slate-500">
                        Read our latest articles and updates
                    </p>
                </div>
                <Link
                    href="/blogs"
                    class="group inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 transition hover:text-indigo-700"
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

    <RepositoryStas
        :total-subjects="subjectCount"
        :total-resources="resourceCount"
        :total-users="contributorCount"
    />
</template>
