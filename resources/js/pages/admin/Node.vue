<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import BulkImageModal from '@/components/admin/BulkImageModal.vue';
import BulkNodeModal from '@/components/admin/BulkNodeModal.vue';
import BulkRenameModal from '@/components/admin/BulkRenameModal.vue';
import BulkVideoModal from '@/components/admin/BulkVideoModal.vue';
import CreateNodeModal from '@/components/admin/CreateNodeModal.vue';
import CreateResourceModal from '@/components/admin/CreateResourceModal.vue';
import NodeHeader from '@/components/admin/NodeHeader.vue';
import NodeRow from '@/components/admin/NodeRow.vue';
import NodeTabsHeader from '@/components/admin/NodeTabsHeader.vue';
import PendingSubmissionRow from '@/components/admin/PendingSubmissionRow.vue';
import ResourceRow from '@/components/admin/ResourceRow.vue';
import ResourceSubmissionModal from '@/components/admin/ResourceSubmissionModal.vue';
import EmptyState from '@/components/EmptyState.vue';
import { usePermissions } from '@/lib/usePermissions';

const { can } = usePermissions();
const page = usePage();

const props = defineProps({
    subject: Object,
    nodes: Array,
    resources: Array,
    pending_creates: Array,
    rejected_creates: Array,
    parent: Object,
    breadcrumb: Array,
});

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
const isBulkImageModalOpen = ref(false);
const isBulkVideoModalOpen = ref(false);
const isBulkRenameModalOpen = ref(false);
const isSingleModalOpen = ref(false);
const editingNode = ref<any | null>(null);
const isSingleResourceModalOpen = ref(false);
const editingResource = ref<any | null>(null);
const viewingPendingModal = ref<any | null>(null);

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

const openCreateResourceModal = () => {
    editingResource.value = null;
    isSingleResourceModalOpen.value = true;
};

const openEditResourceModal = (resource: any) => {
    editingResource.value = resource;
    isSingleResourceModalOpen.value = true;
};

const handleResourceModalClose = () => {
    isSingleResourceModalOpen.value = false;
    editingResource.value = null;
};

const totalItemsCount = computed(
    () =>
        (props.nodes?.length ?? 0) +
        (props.resources?.length ?? 0) +
        (props.pending_creates?.length ?? 0) +
        (props.rejected_creates?.length ?? 0),
);

const activeResourceTab = ref<'live' | 'pending' | 'rejected'>('live');

const syncTabFromHash = () => {
    if (typeof window === 'undefined') {
        return;
    }

    const hash = window.location.hash.toLowerCase();

    if (hash === '#rejected') {
        activeResourceTab.value = 'rejected';
    } else if (hash === '#pending') {
        activeResourceTab.value = 'pending';
    } else if (hash === '#live' || hash === '#approved') {
        activeResourceTab.value = 'live';
    }
};

const handleTabChange = (tab: 'live' | 'pending' | 'rejected') => {
    activeResourceTab.value = tab;

    if (typeof window !== 'undefined') {
        const hash = `#${tab}`;

        if (window.location.hash !== hash) {
            history.replaceState(null, '', hash);
        }
    }
};

onMounted(() => {
    syncTabFromHash();
    window.addEventListener('hashchange', syncTabFromHash);
});

onUnmounted(() => {
    window.removeEventListener('hashchange', syncTabFromHash);
});

const liveResourcesWithPending = computed(() => {
    return (
        (props.resources as any[])?.filter((r) =>
            Boolean(r.pending_change_request),
        ) ?? []
    );
});

const totalPendingCount = computed(() => {
    return (
        (props.pending_creates?.length ?? 0) +
        liveResourcesWithPending.value.length
    );
});

const liveResourcesWithRejected = computed(() => {
    return (
        (props.resources as any[])?.filter(
            (r) =>
                !r.pending_change_request &&
                Boolean(r.latest_rejected_change_request),
        ) ?? []
    );
});

const totalRejectedCount = computed(() => {
    return (
        (props.rejected_creates?.length ?? 0) +
        liveResourcesWithRejected.value.length
    );
});

const liveCount = computed(() => {
    return (props.nodes?.length ?? 0) + (props.resources?.length ?? 0);
});

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
            @create-single-resource="openCreateResourceModal"
            @create-bulk-image="isBulkImageModalOpen = true"
            @create-bulk-video="isBulkVideoModalOpen = true"
        />

        <!-- Modals -->
        <CreateNodeModal
            :is-open="isSingleModalOpen"
            :subject="subject"
            :parent="parent"
            :node="editingNode"
            @close="handleNodeModalClose"
        />

        <CreateResourceModal
            v-if="parent"
            :is-open="isSingleResourceModalOpen"
            :node="parent"
            :resource="editingResource"
            @close="handleResourceModalClose"
        />

        <BulkNodeModal
            :is-open="isBulkModalOpen"
            :subject="subject"
            :parent="parent"
            @close="isBulkModalOpen = false"
        />

        <BulkImageModal
            v-if="parent"
            :is-open="isBulkImageModalOpen"
            :node="parent"
            @close="isBulkImageModalOpen = false"
        />

        <BulkVideoModal
            v-if="parent"
            :is-open="isBulkVideoModalOpen"
            :node="parent"
            @close="isBulkVideoModalOpen = false"
        />

        <BulkRenameModal
            v-if="parent"
            :is-open="isBulkRenameModalOpen"
            :node="parent"
            :resources="resources ?? []"
            @close="isBulkRenameModalOpen = false"
        />

        <!-- Pending / Rejected Resource Preview Modal -->
        <ResourceSubmissionModal
            :change-request="viewingPendingModal"
            @close="viewingPendingModal = null"
        />

        <!-- Items Container -->
        <div class="flex flex-1 flex-col">
            <template v-if="totalItemsCount > 0">
                <div class="space-y-4">
                    <!-- Tabs Header on Top -->
                    <NodeTabsHeader
                        :active-tab="activeResourceTab"
                        :live-count="liveCount"
                        :total-pending-count="totalPendingCount"
                        :total-rejected-count="totalRejectedCount"
                        :can-bulk-rename="
                            !isFrozen &&
                            can('edit resources') &&
                            Boolean(resources?.length)
                        "
                        @change-tab="handleTabChange"
                        @bulk-rename="isBulkRenameModalOpen = true"
                    />

                    <!-- Live Tab Content -->
                    <div
                        v-if="activeResourceTab === 'live'"
                        class="flex flex-col gap-2.5 sm:gap-3"
                    >
                        <NodeRow
                            v-for="node in nodes"
                            :key="`node-${node.id}`"
                            :node="node"
                            :is-frozen="isFrozen"
                            @edit="openEditNodeModal"
                        />

                        <ResourceRow
                            v-for="resource in resources"
                            :key="`resource-${resource.id}`"
                            :resource="resource"
                            :is-frozen="isFrozen"
                            @edit="openEditResourceModal"
                            @view-pending="
                                (pending, res) =>
                                    (viewingPendingModal = pending
                                        ? { ...pending, targetResource: res }
                                        : null)
                            "
                        />

                        <div
                            v-if="liveCount === 0"
                            class="rounded-xl border border-dashed border-slate-200 bg-slate-50/50 p-6 text-center text-xs text-slate-500 dark:border-gray-800 dark:bg-gray-900/30 dark:text-gray-400"
                        >
                            No live items in this folder.
                        </div>
                    </div>

                    <!-- Pending Review Tab Content -->
                    <div
                        v-else-if="activeResourceTab === 'pending'"
                        class="flex flex-col gap-2.5 sm:gap-3"
                    >
                        <PendingSubmissionRow
                            v-for="pending in pending_creates"
                            :key="`pending-create-${pending.id}`"
                            :submission="pending"
                            status="pending"
                            @preview="viewingPendingModal = $event"
                        />

                        <ResourceRow
                            v-for="resource in liveResourcesWithPending"
                            :key="`pending-res-${resource.id}`"
                            :resource="resource"
                            :is-frozen="isFrozen"
                            @edit="openEditResourceModal"
                            @view-pending="
                                (pending, res) =>
                                    (viewingPendingModal = pending
                                        ? { ...pending, targetResource: res }
                                        : null)
                            "
                        />

                        <div
                            v-if="totalPendingCount === 0"
                            class="rounded-xl border border-dashed border-slate-200 bg-slate-50/50 p-6 text-center text-xs text-slate-500 dark:border-gray-800 dark:bg-gray-900/30 dark:text-gray-400"
                        >
                            No pending submissions awaiting review.
                        </div>
                    </div>

                    <!-- Rejected Submissions Tab Content -->
                    <div
                        v-else-if="activeResourceTab === 'rejected'"
                        class="flex flex-col gap-2.5 sm:gap-3"
                    >
                        <PendingSubmissionRow
                            v-for="rejected in rejected_creates"
                            :key="`rejected-create-${rejected.id}`"
                            :submission="rejected"
                            status="rejected"
                            @preview="viewingPendingModal = $event"
                        />

                        <ResourceRow
                            v-for="resource in liveResourcesWithRejected"
                            :key="`rejected-res-${resource.id}`"
                            :resource="resource"
                            :is-frozen="isFrozen"
                            @edit="openEditResourceModal"
                            @view-pending="
                                (pending, res) =>
                                    (viewingPendingModal = pending
                                        ? { ...pending, targetResource: res }
                                        : null)
                            "
                        />

                        <div
                            v-if="totalRejectedCount === 0"
                            class="rounded-xl border border-dashed border-slate-200 bg-slate-50/50 p-6 text-center text-xs text-slate-500 dark:border-gray-800 dark:bg-gray-900/30 dark:text-gray-400"
                        >
                            No rejected submissions in this folder.
                        </div>
                    </div>
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
