<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import CreateSubjectModal from '@/components/admin/CreateSubjectModal.vue';
import SubjectCard from '@/components/admin/SubjectCard.vue';
import EmptyState from '@/components/EmptyState.vue';
import { usePermissions } from '@/lib/usePermissions';

const { can } = usePermissions();

const props = defineProps({
    subjects: {
        type: Array as () => any[],
        default: () => [],
    },
});

const isCreateModalOpen = ref(false);
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
        <!-- Compact Page Title Bar -->
        <div
            class="mb-3.5 flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 pb-3 dark:border-gray-800"
        >
            <div class="flex min-w-0 items-center gap-2.5">
                <h3
                    class="truncate text-base font-bold tracking-tight text-slate-900 dark:text-gray-100"
                >
                    Manage Subjects
                </h3>
            </div>

            <div v-if="can('create subjects')" class="flex items-center gap-2">
                <button
                    type="button"
                    @click="isCreateModalOpen = true"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-2xs transition-colors duration-150 hover:bg-indigo-700"
                >
                    <Plus class="h-3.5 w-3.5" :stroke-width="2.2" />
                    <span>Create Subject</span>
                </button>
            </div>
        </div>

        <!-- Create Subject Modal -->
        <CreateSubjectModal
            :is-open="isCreateModalOpen"
            @close="isCreateModalOpen = false"
        />

        <!-- Filter Pills (All / HSC / SSC) -->
        <div class="mb-4 flex items-center gap-1.5">
            <button
                type="button"
                @click="activeCourse = 'all'"
                :class="[
                    activeCourse === 'all'
                        ? 'bg-slate-900 text-white dark:bg-gray-100 dark:text-gray-900'
                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700',
                    'rounded-lg px-2.5 py-1 text-xs font-semibold transition-colors',
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
                    'rounded-lg px-2.5 py-1 text-xs font-semibold transition-colors',
                ]"
            >
                HSC ({{
                    subjects.filter((s) => s.course?.toLowerCase() === 'hsc')
                        .length
                }})
            </button>

            <button
                type="button"
                @click="activeCourse = 'ssc'"
                :class="[
                    activeCourse === 'ssc'
                        ? 'bg-amber-600 text-white dark:bg-amber-500 dark:text-white'
                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700',
                    'rounded-lg px-2.5 py-1 text-xs font-semibold transition-colors',
                ]"
            >
                SSC ({{
                    subjects.filter((s) => s.course?.toLowerCase() === 'ssc')
                        .length
                }})
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
