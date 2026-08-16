<script setup lang="ts">
import {} from '@inertiajs/vue3';
import {
    kBlock,
    kBlockTitle,
    kBadge,
    kPopover,
    kList,
    kListItem,
    kButton,
} from 'konsta/vue';
import { FolderPlus, ArrowLeft } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import NodeRow from '@/components/admin/NodeRow.vue';
import ResourceRow from '@/components/admin/ResourceRow.vue';
import EmptyState from '@/components/EmptyState.vue';

const props = defineProps({
    subject: Object,
    nodes: Array,
    resources: Array,
    parent: Object,
});

const showBulkActions = ref(false);

const totalItemsCount = computed(
    () => (props.nodes?.length ?? 0) + (props.resources?.length ?? 0),
);

const handleBack = () => {
    const url = new URL(window.location.href);
    const segments = url.pathname.split('/').filter(Boolean);

    // Already at /admin/subjects
    if (segments.join('/') === 'admin/subjects') {
        return;
    }

    const nodesIndex = segments.indexOf('nodes');

    // At /admin/subjects/{subject}/nodes
    if (nodesIndex === segments.length - 1) {
        window.location.href = '/admin/subjects';

        return;
    }

    // Remove the last nested node slug
    segments.pop();

    window.location.href = '/' + segments.join('/');
};

const currentUrl = typeof window !== 'undefined' ? window.location.href : '';
</script>

<template>
    <kBlock>
        <div
            class="mb-6 flex shrink-0 flex-col gap-4 border-b border-gray-100 pb-5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700"
        >
            <div class="flex items-center gap-3">
                <k-button clear small @click="handleBack">
                    <ArrowLeft class="h-4 w-4" :stroke-width="2.5" />
                </k-button>

                <div>
                    <kBlockTitle>
                        {{ parent?.name ? parent.name : subject.name }}
                    </kBlockTitle>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                        Curriculum structure and related resources.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3 self-start sm:self-center">
                <kBadge>
                    <span
                        class="text-xs font-medium text-blue-700 dark:text-blue-400"
                    >
                        Total: {{ totalItemsCount }}
                    </span>
                </kBadge>

                <k-button
                    fill
                    rounded
                    :href="
                        parent
                            ? `/admin/subjects/${subject.slug}/nodes/create?parent_id=${parent.id}`
                            : `/admin/subjects/${subject.slug}/nodes/create`
                    "
                >
                    <FolderPlus class="h-3.5 w-3.5" :stroke-width="2" />
                    Add Folder
                </k-button>

                <!-- Add Resource Dropdown -->
                <k-popover
                    v-if="parent?.id"
                    :opened="showBulkActions"
                    @close="showBulkActions = false"
                >
                    <template #trigger>
                        <k-button
                            outline
                            @click="showBulkActions = !showBulkActions"
                        >
                            Add Resource
                        </k-button>
                    </template>
                    <k-list class="m-0">
                        <k-list-item
                            title="Single File"
                            :link="`/admin/resources/create?node_id=${parent.id}`"
                            @click="showBulkActions = false"
                        />
                        <k-list-item
                            title="Upload Bulk Images"
                            :link="`/admin/resources/create/bulk/images?node_id=${parent.id}&redirect=${currentUrl}`"
                            @click="showBulkActions = false"
                        />
                        <k-list-item
                            title="Upload Bulk Videos"
                            :link="`/admin/resources/create/bulk/videos?node_id=${parent.id}&redirect=${currentUrl}`"
                            @click="showBulkActions = false"
                        />
                    </k-list>
                </k-popover>
            </div>
        </div>

        <div class="flex flex-1 flex-col">
            <template v-if="totalItemsCount > 0">
                <div
                    class="flex shrink-0 items-center justify-between rounded-t-lg border-b border-gray-100 bg-gray-50/70 px-4 py-2.5 text-xs font-semibold tracking-wider text-gray-400 uppercase dark:border-gray-700 dark:bg-gray-800/70 dark:text-gray-500"
                >
                    <span>Resources</span>
                </div>

                <div
                    class="grid grid-cols-2 gap-4 rounded-b-lg border border-gray-100 p-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 dark:border-gray-700"
                >
                    <NodeRow
                        v-for="node in nodes"
                        :key="`node-${node.id}`"
                        :node="node"
                    />
                    <ResourceRow
                        v-for="resource in resources"
                        :key="`resource-${resource.id}`"
                        :resource="resource"
                    />
                </div>
            </template>

            <div v-else class="flex flex-1 items-center justify-center py-12">
                <EmptyState />
            </div>
        </div>
    </kBlock>
</template>
