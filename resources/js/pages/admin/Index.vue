<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import SubjectCard from '@/components/admin/SubjectCard.vue';
import EmptyState from '@/components/EmptyState.vue';

const props = defineProps({
    subjects: {
        type: Array as () => any[],
        default: () => [],
    },
});

const activeCourse = ref<'all' | 'hsc' | 'ssc'>('all');

const filteredSubjects = computed(() => {
    if (activeCourse.value === 'all') {
        return props.subjects;
    }
    return props.subjects.filter(
        (s) => s.course?.toLowerCase() === activeCourse.value.toLowerCase(),
    );
});
</script>

<template>
    <Head title="Manage Contents" />

    <div class="flex w-full flex-1 flex-col">
        <div
            class="mb-6 flex shrink-0 flex-col gap-4 border-b border-gray-100 pb-5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800"
        >
            <div>
                <h3
                    class="text-lg font-semibold tracking-tight text-gray-900 dark:text-gray-100"
                >
                    Manage Subjects
                </h3>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                    View, organize, and configure active curriculum subjects.
                </p>
            </div>

            <div class="flex items-center gap-3 self-start sm:self-center">
                <Link
                    href="/admin/subjects/create"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3.5 py-1.5 text-xs font-medium text-white shadow-sm transition-colors duration-150 hover:bg-blue-700"
                >
                    <Plus class="h-3.5 w-3.5" :stroke-width="2.5" />
                    Create Subject
                </Link>
            </div>
        </div>

        <!-- Filter Pills (All / HSC / SSC) -->
        <div class="mb-5 flex items-center gap-1.5 border-b border-slate-100 pb-3.5 dark:border-gray-800">
            <button
                type="button"
                @click="activeCourse = 'all'"
                :class="[
                    activeCourse === 'all'
                        ? 'bg-slate-900 text-white dark:bg-gray-100 dark:text-gray-900'
                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700',
                    'rounded-lg px-3 py-1 text-xs font-semibold transition-colors',
                ]"
            >
                All ({{ subjects.length }})
            </button>

            <button
                type="button"
                @click="activeCourse = 'hsc'"
                :class="[
                    activeCourse === 'hsc'
                        ? 'bg-indigo-600 text-white dark:bg-indigo-500 dark:text-white'
                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700',
                    'rounded-lg px-3 py-1 text-xs font-semibold transition-colors',
                ]"
            >
                HSC ({{ subjects.filter((s) => s.course?.toLowerCase() === 'hsc').length }})
            </button>

            <button
                type="button"
                @click="activeCourse = 'ssc'"
                :class="[
                    activeCourse === 'ssc'
                        ? 'bg-amber-600 text-white dark:bg-amber-500 dark:text-white'
                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700',
                    'rounded-lg px-3 py-1 text-xs font-semibold transition-colors',
                ]"
            >
                SSC ({{ subjects.filter((s) => s.course?.toLowerCase() === 'ssc').length }})
            </button>
        </div>

        <div class="flex flex-1 flex-col">
            <div
                v-if="filteredSubjects.length > 0"
                class="flex flex-col gap-2.5 sm:gap-3"
            >
                <SubjectCard
                    v-for="subject in filteredSubjects"
                    :key="subject.id || subject.name"
                    :admin="true"
                    :subject="subject"
                />
            </div>

            <EmptyState v-else />
        </div>
    </div>
</template>
