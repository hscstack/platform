<script setup>
import { computed } from 'vue';
import { Plus, FolderPlus, ArrowLeft } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';
import EmptyState from '@/components/EmptyState.vue';
import ResourceRow from '@/components/ResourceRow.vue';
import NodeRow from '@/components/NodeRow.vue';

const props = defineProps({
    subject: Object,
    nodes: Array,
    resources: Array,
    parent: Object,
});


const totalItemsCount = computed(
    () => (props.nodes?.length ?? 0) + (props.resources?.length ?? 0),
);

const handleBack = () => {
    window.history.back();
};
</script>

<template>
    <div
        class="flex w-full flex-1 flex-col rounded-xl border border-gray-100 bg-white p-6 shadow-sm"
    >
        <div
            class="mb-6 flex shrink-0 flex-col gap-4 border-b border-gray-100 pb-5 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex items-center gap-3">
                <button
                    @click="handleBack"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white p-2 text-gray-500 shadow-sm transition-colors duration-150 hover:bg-gray-50 hover:text-gray-700"
                >
                    <ArrowLeft class="h-4 w-4" :stroke-width="2.5" />
                </button>

                <div>
                    <h3
                        class="text-lg font-semibold tracking-tight text-gray-900"
                    >
                        {{ parent?.name ? parent.name : subject.name }}
                    </h3>
                    <p class="mt-0.5 text-sm text-gray-500">
                        Curriculum structure and related resources.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3 self-start sm:self-center">
                <div
                    class="rounded-full border border-blue-100 bg-blue-50 px-3 py-1"
                >
                    <span class="text-xs font-medium text-blue-700">
                        Total Items: {{ totalItemsCount }}
                    </span>
                </div>

                <a
                    :href="
                        parent
                            ? `/admin/subjects/${subject.slug}/nodes/create?parent_id=${parent.id}`
                            : `/admin/subjects/${subject.slug}/nodes/create`
                    "
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3.5 py-1.5 text-xs font-medium text-gray-700 shadow-sm transition-colors duration-150 hover:bg-gray-50"
                >
                    <FolderPlus
                        class="h-3.5 w-3.5 text-gray-500"
                        :stroke-width="2"
                    />
                    Add Folder
                </a>

                <Link
                    href="#"
                    type="button"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-blue-600 px-3.5 py-1.5 text-xs font-medium text-white shadow-sm transition-colors duration-150 hover:bg-blue-700"
                >
                    <Plus class="h-3.5 w-3.5" :stroke-width="2.5" />
                    Add File
                </Link>
            </div>
        </div>

        <div class="flex flex-1 flex-col">
            <template v-if="totalItemsCount > 0">
                <div
                    class="flex shrink-0 items-center justify-between rounded-t-lg border-b border-gray-100 bg-gray-50/70 px-4 py-2.5 text-xs font-semibold tracking-wider text-gray-400 uppercase"
                >
                    <span>Name</span>
                    <span class="hidden sm:inline">Type</span>
                </div>

                <div
                    class="divide-y divide-gray-100 overflow-hidden rounded-b-lg border-x border-b border-gray-100"
                >
                    <NodeRow
                        v-for="node in nodes"
                        :key="`node-${node.id}`"
                        :node="node"
                        class="transition-colors hover:bg-gray-50/50"
                    />
                    <ResourceRow
                        v-for="resource in resources"
                        :key="`resource-${resource.id}`"
                        :resource="resource"
                        class="transition-colors hover:bg-gray-50/50"
                    />
                </div>
            </template>

            <div v-else class="flex flex-1 items-center justify-center py-12">
                <EmptyState />
            </div>
        </div>
    </div>
</template>
