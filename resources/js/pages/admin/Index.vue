<script setup>
import SubjectCard from '@/components/admin/SubjectCard.vue';
import EmptyState from '@/components/EmptyState.vue';
import { Link } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';

const props = defineProps({
    subjects: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <div
        class="flex w-full flex-1 flex-col rounded-xl border border-gray-100 bg-white p-6 shadow-sm"
    >
        <div
            class="mb-6 flex shrink-0 flex-col gap-4 border-b border-gray-100 pb-5 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h3 class="text-lg font-semibold tracking-tight text-gray-900">
                    Manage Subjects
                </h3>
                <p class="mt-0.5 text-sm text-gray-500">
                    View, organize, and configure active curriculum subjects.
                </p>
            </div>

            <div class="flex items-center gap-3 self-start sm:self-center">
                <div
                    class="rounded-full border border-blue-100 bg-blue-50 px-3 py-1"
                >
                    <span class="text-xs font-medium text-blue-700">
                        Total: {{ subjects.length }}
                    </span>
                </div>

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

            <EmptyState v-else/>
        </div>
    </div>
</template>