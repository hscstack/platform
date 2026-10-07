<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { ref } from 'vue';
import CreateSubjectModal from '@/components/admin/CreateSubjectModal.vue';
import SubjectCard from '@/components/admin/SubjectCard.vue';
import EmptyState from '@/components/EmptyState.vue';
import { usePermissions } from '@/lib/usePermissions';

const { can } = usePermissions();

defineProps<{
    subjects?: any[];
    current_course?: string | null;
    current_group?: string | null;
}>();

const isCreateModalOpen = ref(false);
const editingSubject = ref<any | null>(null);

const openCreateModal = () => {
    editingSubject.value = null;
    isCreateModalOpen.value = true;
};

const openEditModal = (subject: any) => {
    editingSubject.value = subject;
    isCreateModalOpen.value = true;
};

const handleModalClose = () => {
    isCreateModalOpen.value = false;
    editingSubject.value = null;
};

const getFilterUrl = (course: string, group: string) => {
    return `/admin/subjects?course=${course}&group=${group}`;
};
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
                    @click="openCreateModal"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-2xs transition-colors duration-150 hover:bg-indigo-700"
                >
                    <Plus class="h-3.5 w-3.5" :stroke-width="2.2" />
                    <span>Create Subject</span>
                </button>
            </div>
        </div>

        <!-- Create/Edit Subject Modal -->
        <CreateSubjectModal
            :is-open="isCreateModalOpen"
            :subject="editingSubject"
            @close="handleModalClose"
        />

        <!-- Filter Pills Bar (Server Query Driven, Defaults to HSC & Science) -->
        <div class="mb-4 flex flex-wrap items-center gap-x-4 gap-y-2">
            <!-- Curriculum Filter Pills -->
            <div class="flex flex-wrap items-center gap-1.5">
                <span
                    class="text-xs font-semibold text-slate-500 dark:text-gray-400"
                >
                    কারিকুলাম:
                </span>
                <Link
                    v-for="c in [
                        { key: 'hsc', label: 'HSC' },
                        { key: 'ssc', label: 'SSC' },
                    ]"
                    :key="c.key"
                    :href="getFilterUrl(c.key, current_group || 'science')"
                    preserve-scroll
                    class="inline-flex cursor-pointer items-center rounded-xl px-3 py-1 text-xs transition select-none"
                    :class="[
                        (current_course || 'hsc') === c.key
                            ? 'bg-indigo-600 font-bold text-white shadow-2xs'
                            : 'border border-slate-200/80 bg-white font-medium text-slate-600 hover:bg-slate-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700',
                    ]"
                >
                    <span>{{ c.label }}</span>
                </Link>
            </div>

            <!-- Group Filter Pills -->
            <div class="flex flex-wrap items-center gap-1.5">
                <span
                    class="text-xs font-semibold text-slate-500 dark:text-gray-400"
                >
                    বিভাগ:
                </span>
                <Link
                    v-for="g in [
                        { key: 'science', label: 'বিজ্ঞান' },
                        { key: 'humanities', label: 'মানবিক' },
                        { key: 'commerce', label: 'ব্যবসায় শিক্ষা' },
                    ]"
                    :key="g.key"
                    :href="getFilterUrl(current_course || 'hsc', g.key)"
                    preserve-scroll
                    class="inline-flex cursor-pointer items-center rounded-xl px-3 py-1 text-xs transition select-none"
                    :class="[
                        (current_group || 'science') === g.key
                            ? 'bg-indigo-600 font-bold text-white shadow-2xs'
                            : 'border border-slate-200/80 bg-white font-medium text-slate-600 hover:bg-slate-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700',
                    ]"
                >
                    <span>{{ g.label }}</span>
                </Link>
            </div>
        </div>

        <div class="flex flex-1 flex-col">
            <div
                v-if="subjects && subjects.length > 0"
                class="flex flex-col gap-2.5 sm:gap-3"
            >
                <SubjectCard
                    v-for="subject in subjects"
                    :key="subject.id || subject.name"
                    :admin="true"
                    :subject="subject"
                    @edit="openEditModal"
                />
            </div>

            <EmptyState
                v-else
                title="No subjects found"
                description="No subjects have been created in this category yet."
                :show-cta="false"
            />
        </div>
    </div>
</template>
