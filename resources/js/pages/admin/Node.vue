<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import BulkNodeModal from '@/components/admin/BulkNodeModal.vue';
import CreateNodeModal from '@/components/admin/CreateNodeModal.vue';
import NodeHeader from '@/components/admin/NodeHeader.vue';
import NodeRow from '@/components/admin/NodeRow.vue';
import ResourceRow from '@/components/admin/ResourceRow.vue';
import EmptyState from '@/components/EmptyState.vue';

const page = usePage();

const props = defineProps<{
    subject: any;
    nodes?: any[];
    resources?: any[];
    parent?: any;
    breadcrumb?: any[];
}>();

const isDirectlyFrozen = computed(() => Boolean(props.parent?.is_frozen));
const isInheritedFrozen = computed(
    () =>
        !isDirectlyFrozen.value && Boolean(props.parent?.is_effectively_frozen),
);
const isFrozen = computed(
    () => isDirectlyFrozen.value || isInheritedFrozen.value,
);

const isTogglingFreeze = ref(false);

const toggleFreeze = () => {
    if (!props.parent?.id) {
        return;
    }

    let message = 'Are you sure you want to freeze this folder?';

    if (props.parent.is_frozen) {
        message = 'Are you sure you want to unfreeze this folder?';
    } else if (isInheritedFrozen.value) {
        message =
            'প্যারেন্ট ফোল্ডার আনলক করা হলেও এই ফোল্ডারটি যাতে লক থাকে, সেজন্য কি এটিকে আলাদাভাবে ফ্রিজ করতে চান?';
    }

    if (confirm(message)) {
        isTogglingFreeze.value = true;
        router.post(
            `/admin/nodes/${props.parent.id}/toggle-freeze`,
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    isTogglingFreeze.value = false;
                },
            },
        );
    }
};

const isBulkModalOpen = ref(false);
const isSingleModalOpen = ref(false);
const editingNode = ref<any | null>(null);

const openCreateNodeModal = () => {
    editingNode.value = null;
    isSingleModalOpen.value = true;
};

const openEditNodeModal = (node: any) => {
    editingNode.value = node;
    isSingleModalOpen.value = true;
};

const handleNodeModalClose = () => {
    isSingleModalOpen.value = false;
    editingNode.value = null;
};

const totalItemsCount = computed(
    () => (props.nodes?.length ?? 0) + (props.resources?.length ?? 0),
);

const backUrl = computed(() => {
    const rawPath = (page.url || '').split('?')[0];
    const segments = rawPath.split('/').filter(Boolean);

    const nodesIndex = segments.indexOf('nodes');

    if (nodesIndex === -1 || nodesIndex === segments.length - 1) {
        return '/admin/subjects';
    }

    segments.pop();

    return '/' + segments.join('/');
});

interface BreadcrumbItem {
    name: string;
    link: string;
}

const adminBreadcrumbs = computed<BreadcrumbItem[]>(() => {
    const items: BreadcrumbItem[] = [
        {
            name: 'Subjects',
            link: '/admin/subjects',
        },
        {
            name: (props.subject as any)?.name || 'Subject',
            link: `/admin/subjects/${(props.subject as any)?.slug}/nodes`,
        },
    ];

    if (props.breadcrumb && Array.isArray(props.breadcrumb)) {
        let currentPath = `/admin/subjects/${(props.subject as any)?.slug}/nodes`;

        for (const crumb of props.breadcrumb as any[]) {
            currentPath += `/${crumb.slug}`;
            items.push({
                name: crumb.name,
                link: currentPath,
            });
        }
    }

    return items;
});
</script>

<template>
    <Head :title="parent?.name || subject?.name || 'Manage Nodes'" />

    <div class="flex w-full flex-1 flex-col">
        <!-- Page Header & Action Toolbar -->
        <NodeHeader
            :subject="subject"
            :parent="parent"
            :breadcrumbs="adminBreadcrumbs"
            :back-url="backUrl"
            :is-frozen="isFrozen"
            :is-directly-frozen="isDirectlyFrozen"
            :is-inherited-frozen="isInheritedFrozen"
            :is-toggling-freeze="isTogglingFreeze"
            @toggle-freeze="toggleFreeze"
            @create-single-folder="openCreateNodeModal"
            @create-bulk-folder="isBulkModalOpen = true"
        />

        <!-- Modals -->
        <CreateNodeModal
            :is-open="isSingleModalOpen"
            :subject="subject"
            :parent="parent"
            :node="editingNode"
            @close="handleNodeModalClose"
        />

        <BulkNodeModal
            :is-open="isBulkModalOpen"
            :subject="subject"
            :parent="parent"
            @close="isBulkModalOpen = false"
        />

        <!-- Items Container -->
        <div class="flex flex-1 flex-col">
            <template v-if="totalItemsCount > 0">
                <div class="flex flex-col gap-2.5 sm:gap-3">
                    <NodeRow
                        v-for="node in nodes"
                        :key="`node-${(node as any).id}`"
                        :node="node"
                        :is-frozen="isFrozen"
                        @edit="openEditNodeModal"
                    />

                    <ResourceRow
                        v-for="resource in resources"
                        :key="`resource-${(resource as any).id}`"
                        :resource="resource"
                        :is-frozen="isFrozen"
                    />
                </div>
            </template>

            <EmptyState
                v-else
                title="No items in this folder"
                description="Create a new folder or resource above to get started."
            />
        </div>
    </div>
</template>
