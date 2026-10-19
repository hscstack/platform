/**
 * AdminNode — TSX port of `Node.vue`.
 *
 * Folder/resources browser with live/pending/rejected moderation tabs,
 * freeze controls and breadcrumbs, rewritten as a `.tsx`
 * `defineComponent` render function. Resolved via the explicit
 * dual-extension (`*.vue` + `*.tsx`) page resolver in `resources/js/app.ts`.
 *
 * Ported 1:1 from the SFC; the chrome lives in `NodeHeader.vue` /
 * `NodeTabsHeader.vue`, rows in `NodeRow.vue` / `ResourceRow.vue` /
 * `PendingSubmissionRow.vue`.
 */
import { Head, router } from '@inertiajs/vue3';
import { computed, defineComponent, onMounted, onUnmounted, ref } from 'vue';
import type { PropType } from 'vue';

import BulkNodeModal from '@/components/admin/BulkNodeModal.vue';
import BulkRenameModal from '@/components/admin/BulkRenameModal.vue';
import CreateNodeModal from '@/components/admin/CreateNodeModal.vue';
import CreateResourceModal from '@/components/admin/CreateResourceModal.vue';
import NodeHeader from '@/components/admin/NodeHeader.vue';
import NodeRow from '@/components/admin/NodeRow.vue';
import NodeTabsHeader from '@/components/admin/NodeTabsHeader.vue';
import PendingSubmissionRow from '@/components/admin/PendingSubmissionRow.vue';
import ResourceRow from '@/components/admin/ResourceRow.vue';
import ReviewResourceModal from '@/components/admin/ReviewResourceModal.vue';
import { adminPageClass } from '@/components/admin/ui';
import EmptyState from '@/components/EmptyState.vue';
import { usePermissions } from '@/lib/usePermissions';

interface BreadcrumbItem {
    name: string;
    link: string;
}

type ResourceTab = 'live' | 'pending' | 'rejected';

export default defineComponent({
    name: 'AdminNode',
    props: {
        subject: { type: Object as PropType<any>, default: null },
        nodes: { type: Array as PropType<any[]>, default: null },
        resources: { type: Array as PropType<any[]>, default: null },
        pending_creates: { type: Array as PropType<any[]>, default: null },
        rejected_creates: { type: Array as PropType<any[]>, default: null },
        parent: { type: Object as PropType<any>, default: null },
        breadcrumb: { type: Array as PropType<any[]>, default: null },
    },
    setup(props) {
        const { can } = usePermissions();

        /* Frozen state (direct on this folder vs inherited from a parent). */
        const isDirectlyFrozen = computed(() =>
            Boolean(props.parent?.is_frozen),
        );
        const isInheritedFrozen = computed(
            () =>
                !isDirectlyFrozen.value &&
                Boolean(props.parent?.is_effectively_frozen),
        );
        const isFrozen = computed(
            () => isDirectlyFrozen.value || isInheritedFrozen.value,
        );

        const isTogglingFreeze = ref(false);

        /**
         * Confirms and requests a toggle of this folder's direct freeze flag.
         * Inherited freezes can be made direct; without a parent ID, does nothing.
         */
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

        /* Moderation tabs — the active tab is mirrored into the URL hash
            so it survives refreshes and can be linked directly. */
        const activeResourceTab = ref<ResourceTab>('live');

        /**
         * Reads the moderation tab from the URL hash without regard to case.
         * An empty hash or #approved selects live; unknown hashes leave the
         * selection unchanged. Does nothing outside the browser.
         */
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
            } else if (hash === '') {
                // No hash — navigating to another folder resets to Live so
                // the visible tab can't silently disagree with the URL.
                activeResourceTab.value = 'live';
            }
        };

        /** Selects a tab and replaces the browser URL hash without adding history. */
        const handleTabChange = (tab: ResourceTab) => {
            activeResourceTab.value = tab;

            if (typeof window !== 'undefined') {
                const hash = `#${tab}`;

                if (window.location.hash !== hash) {
                    // Keep Inertia's state on the entry — a null state breaks
                    // Back-button restoration to this page.
                    history.replaceState(window.history.state, '', hash);
                }
            }
        };

        onMounted(() => {
            syncTabFromHash();

            window.addEventListener('hashchange', syncTabFromHash);

            // Inertia client-side navigations (folder → folder) don't fire
            // hashchange; re-sync so the tab follows the (cleared) URL.
            const offNavigate = router.on('navigate', () => syncTabFromHash());

            onUnmounted(() => offNavigate());
        });

        onUnmounted(() => {
            window.removeEventListener('hashchange', syncTabFromHash);
        });

        /* Live resources that carry a pending or rejected change request. */
        const liveResourcesWithPending = computed(() => {
            return (
                props.resources?.filter((r) =>
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
                props.resources?.filter(
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

        const adminBreadcrumbs = computed<BreadcrumbItem[]>(() => {
            const items: BreadcrumbItem[] = [
                {
                    name: 'Subjects',
                    link: '/admin/subjects',
                },
                {
                    name: props.subject?.name || 'Subject',
                    link: `/admin/subjects/${props.subject?.slug}/nodes`,
                },
            ];

            if (props.breadcrumb && Array.isArray(props.breadcrumb)) {
                let currentPath = `/admin/subjects/${props.subject?.slug}/nodes`;

                for (const crumb of props.breadcrumb) {
                    currentPath += `/${crumb.slug}`;
                    items.push({
                        name: crumb.name,
                        link: currentPath,
                    });
                }
            }

            return items;
        });

        /**
         * Opens review with the request and its target resource, or clears
         * the modal when the request is falsy.
         */
        const openPendingModal = (pending: any, resource: any) => {
            viewingPendingModal.value = pending
                ? { ...pending, targetResource: resource }
                : null;
        };

        /** Dashed callout for an empty tab bucket. */
        const TabEmptyCallout = defineComponent({
            name: 'AdminNodeTabEmptyCallout',
            props: { message: { type: String, required: true } },
            setup(innerProps) {
                return () => (
                    <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50/50 p-6 text-center text-xs text-slate-500 dark:border-slate-800/60 dark:bg-gray-900/30 dark:text-gray-400">
                        {innerProps.message}
                    </div>
                );
            },
        });

        /** Per-render props for ResourceRow spreads. Built as a factory so
         *  `isFrozen` is read during render, not once in setup — otherwise
         *  rows keep stale frozen state across partial page updates. */
        const resourceRowProps = () => ({
            isFrozen: isFrozen.value,
            onEdit: openEditResourceModal,
            onViewPending: openPendingModal,
        });

        return () => (
            <>
                <Head
                    title={
                        props.parent?.name ||
                        props.subject?.name ||
                        'Manage Nodes'
                    }
                />

                <div class={[adminPageClass, 'flex-1']}>
                    {/* Page Header & Action Toolbar */}
                    <NodeHeader
                        subject={props.subject}
                        parent={props.parent}
                        breadcrumbs={adminBreadcrumbs.value}
                        isFrozen={isFrozen.value}
                        isDirectlyFrozen={isDirectlyFrozen.value}
                        isInheritedFrozen={isInheritedFrozen.value}
                        isTogglingFreeze={isTogglingFreeze.value}
                        onToggleFreeze={toggleFreeze}
                        onCreateSingleFolder={openCreateNodeModal}
                        onCreateBulkFolder={() =>
                            (isBulkModalOpen.value = true)
                        }
                    />

                    {/* Items Container */}
                    <div class="flex flex-1 flex-col">
                        {totalItemsCount.value > 0 ? (
                            <div class="space-y-4">
                                {/* Tabs Header on Top */}
                                <NodeTabsHeader
                                    activeTab={activeResourceTab.value}
                                    liveCount={liveCount.value}
                                    totalPendingCount={totalPendingCount.value}
                                    totalRejectedCount={
                                        totalRejectedCount.value
                                    }
                                    canBulkRename={
                                        !isFrozen.value &&
                                        can('edit resources') &&
                                        Boolean(props.resources?.length)
                                    }
                                    onChangeTab={handleTabChange}
                                    onBulkRename={() =>
                                        (isBulkRenameModalOpen.value = true)
                                    }
                                />

                                {/* Live Tab Content */}
                                {activeResourceTab.value === 'live' ? (
                                    <div class="flex flex-col gap-2.5 sm:gap-3">
                                        {props.nodes?.map((node) => (
                                            <NodeRow
                                                key={`node-${node.id}`}
                                                node={node}
                                                isFrozen={isFrozen.value}
                                                onEdit={openEditNodeModal}
                                            />
                                        ))}

                                        {props.resources?.map((resource) => (
                                            <ResourceRow
                                                key={`resource-${resource.id}`}
                                                resource={resource}
                                                {...resourceRowProps()}
                                            />
                                        ))}

                                        {liveCount.value === 0 && (
                                            <TabEmptyCallout message="No live items in this folder." />
                                        )}
                                    </div>
                                ) : activeResourceTab.value === 'pending' ? (
                                    <div class="flex flex-col gap-2.5 sm:gap-3">
                                        {props.pending_creates?.map(
                                            (pending) => (
                                                <PendingSubmissionRow
                                                    key={`pending-create-${pending.id}`}
                                                    submission={pending}
                                                    status="pending"
                                                    onPreview={(p: any) =>
                                                        (viewingPendingModal.value =
                                                            p)
                                                    }
                                                />
                                            ),
                                        )}

                                        {liveResourcesWithPending.value.map(
                                            (resource) => (
                                                <ResourceRow
                                                    key={`pending-res-${resource.id}`}
                                                    resource={resource}
                                                    {...resourceRowProps()}
                                                />
                                            ),
                                        )}

                                        {totalPendingCount.value === 0 && (
                                            <TabEmptyCallout message="No pending submissions awaiting review." />
                                        )}
                                    </div>
                                ) : (
                                    <div class="flex flex-col gap-2.5 sm:gap-3">
                                        {props.rejected_creates?.map(
                                            (rejected) => (
                                                <PendingSubmissionRow
                                                    key={`rejected-create-${rejected.id}`}
                                                    submission={rejected}
                                                    status="rejected"
                                                    onPreview={(r: any) =>
                                                        (viewingPendingModal.value =
                                                            r)
                                                    }
                                                />
                                            ),
                                        )}

                                        {liveResourcesWithRejected.value.map(
                                            (resource) => (
                                                <ResourceRow
                                                    key={`rejected-res-${resource.id}`}
                                                    resource={resource}
                                                    {...resourceRowProps()}
                                                />
                                            ),
                                        )}

                                        {totalRejectedCount.value === 0 && (
                                            <TabEmptyCallout message="No rejected submissions in this folder." />
                                        )}
                                    </div>
                                )}
                            </div>
                        ) : (
                            <EmptyState
                                title="No items in this folder"
                                description="Create a new folder or resource above to get started."
                            />
                        )}
                    </div>
                </div>

                {/* Modals are outside the spaced page stack: fixed overlays
                    must not receive space-y margins (they'd shift down). */}
                <CreateNodeModal
                    isOpen={isSingleModalOpen.value}
                    subject={props.subject}
                    parent={props.parent}
                    node={editingNode.value}
                    onClose={handleNodeModalClose}
                />

                {props.parent && (
                    <CreateResourceModal
                        isOpen={isSingleResourceModalOpen.value}
                        node={props.parent}
                        resource={editingResource.value}
                        onClose={handleResourceModalClose}
                    />
                )}

                <BulkNodeModal
                    isOpen={isBulkModalOpen.value}
                    subject={props.subject}
                    parent={props.parent}
                    onClose={() => (isBulkModalOpen.value = false)}
                />

                {props.parent && (
                    <BulkRenameModal
                        isOpen={isBulkRenameModalOpen.value}
                        node={props.parent}
                        resources={props.resources ?? []}
                        onClose={() => (isBulkRenameModalOpen.value = false)}
                    />
                )}

                {/* Pending / Rejected Resource Preview Modal */}
                <ReviewResourceModal
                    request={viewingPendingModal.value}
                    onClose={() => (viewingPendingModal.value = null)}
                />
            </>
        );
    },
});
