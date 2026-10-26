<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, BookOpen, Layers, Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import BulkChapterModal from '@/components/admin/BulkChapterModal.vue';
import ChapterRow from '@/components/admin/ChapterRow.vue';
import CreateChapterModal from '@/components/admin/CreateChapterModal.vue';
import EmptyState from '@/components/EmptyState.vue';
import { usePermissions } from '@/lib/usePermissions';

const { can } = usePermissions();

const props = defineProps<{
    subject: any;
    chapters?: any[];
    nodes?: any[];
}>();

const chapterList = computed(() => props.chapters || props.nodes || []);

const isBulkModalOpen = ref(false);
const isSingleModalOpen = ref(false);
const editingChapter = ref<any | null>(null);

const openCreateChapterModal = () => {
    editingChapter.value = null;
    isSingleModalOpen.value = true;
};

const openEditChapterModal = (chapter: any) => {
    editingChapter.value = chapter;
    isSingleModalOpen.value = true;
};

const handleChapterModalClose = () => {
    isSingleModalOpen.value = false;
    editingChapter.value = null;
};
</script>

<template>
    <Head :title="`${subject?.name || 'Subject'} - Chapters`" />

    <div class="flex w-full flex-1 flex-col">
        <!-- Page Header & Action Toolbar -->
        <div
            class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex items-center gap-3">
                <Link
                    href="/admin/subjects"
                    class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800"
                    title="Back to Subjects"
                >
                    <ArrowLeft class="h-4 w-4" />
                </Link>

                <div>
                    <h1
                        class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl dark:text-gray-100"
                    >
                        {{ subject?.name }}
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-gray-400">
                        Level: {{ (subject?.course || 'hsc').toUpperCase() }} •
                        Group: {{ subject?.group || 'science' }} •
                        {{ chapterList.length }} Chapters
                    </p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div
                v-if="can('manage curriculum')"
                class="flex items-center gap-2"
            >
                <button
                    type="button"
                    @click="isBulkModalOpen = true"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
                >
                    <Layers class="h-3.5 w-3.5" />
                    <span>Batch Generate</span>
                </button>

                <button
                    type="button"
                    @click="openCreateChapterModal"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-2xs transition hover:bg-indigo-700"
                >
                    <Plus class="h-3.5 w-3.5" />
                    <span>Add Chapter</span>
                </button>
            </div>
        </div>

        <!-- Modals -->
        <BulkChapterModal
            :is-open="isBulkModalOpen"
            :subject="subject"
            @close="isBulkModalOpen = false"
        />

        <CreateChapterModal
            :is-open="isSingleModalOpen"
            :subject="subject"
            :chapter="editingChapter"
            @close="handleChapterModalClose"
        />

        <!-- Chapters List -->
        <div class="flex flex-1 flex-col gap-2">
            <div v-if="chapterList.length > 0" class="flex flex-col gap-2">
                <ChapterRow
                    v-for="chapter in chapterList"
                    :key="`chapter-${chapter.id}`"
                    :chapter="chapter"
                    @edit="openEditChapterModal"
                />
            </div>

            <EmptyState
                v-else
                :icon="BookOpen"
                title="No chapters yet"
                description="This subject does not have any chapters created. Click 'Add Chapter' or 'Batch Generate' to create chapters."
            />
        </div>
    </div>
</template>
