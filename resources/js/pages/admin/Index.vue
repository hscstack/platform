<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { kBlock, kBlockTitle, kBadge } from 'konsta/vue';
import { Plus } from 'lucide-vue-next';
import SubjectCard from '@/components/admin/SubjectCard.vue';
import EmptyState from '@/components/EmptyState.vue';

defineProps({
    subjects: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <kBlock>
        <div
            class="mb-6 flex shrink-0 flex-col gap-4 border-b border-gray-100 pb-5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700"
        >
            <div>
                <kBlockTitle> Manage Subjects </kBlockTitle>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                    View, organize, and configure active curriculum subjects.
                </p>
            </div>

            <div class="flex items-center gap-3 self-start sm:self-center">
                <kBadge>
                    <span
                        class="text-xs font-medium text-blue-700 dark:text-blue-400"
                    >
                        Total: {{ subjects.length }}
                    </span>
                </kBadge>

                <Link
                    href="/admin/subjects/create"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3.5 py-1.5 text-xs font-medium text-white shadow-sm transition-colors duration-150 hover:bg-blue-700"
                >
                    <Plus class="h-3.5 w-3.5" :stroke-width="2.5" />
                    Create Subject
                </Link>
            </div>
        </div>

        <div class="flex flex-1 flex-col">
            <div
                v-if="subjects.length > 0"
                class="grid auto-rows-max grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6"
            >
                <SubjectCard
                    v-for="subject in subjects"
                    :key="subject.id || subject.name"
                    :admin="true"
                    :subject="subject"
                />
            </div>

            <EmptyState v-else />
        </div>
    </kBlock>
</template>
