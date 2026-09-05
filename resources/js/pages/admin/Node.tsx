/**
 * AdminNode — TSX port of the former `Node.vue` (flat, decardified).
 *
 * Same UI/behavior as the SFC: folder/resource dropdowns, single + bulk
 * modals, NodeRow/ResourceRow lists. Resolved via the explicit
 * dual-extension (`*.vue` + `*.tsx`) page resolver in
 * `resources/js/app.ts`.
 */
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ChevronDown,
    FolderPlus,
    PencilLine,
    Plus,
} from 'lucide-vue-next';
import { computed, defineComponent, onMounted, onUnmounted, ref } from 'vue';
import type { PropType } from 'vue';

import BulkImageModal from '@/components/admin/BulkImageModal.vue';
import BulkNodeModal from '@/components/admin/BulkNodeModal.vue';
import BulkRenameModal from '@/components/admin/BulkRenameModal.vue';
import BulkVideoModal from '@/components/admin/BulkVideoModal.vue';
import CreateNodeModal from '@/components/admin/CreateNodeModal.vue';
import CreateResourceModal from '@/components/admin/CreateResourceModal.vue';
import NodeRow from '@/components/admin/NodeRow.vue';
import ResourceRow from '@/components/admin/ResourceRow.vue';
import EmptyState from '@/components/EmptyState.vue';
import { usePermissions } from '@/lib/usePermissions';

interface Subject {
    id: number;
    name: string;
    slug: string;
}

interface Node {
    id: number;
    subject_id?: number | null;
    parent_id?: number | null;
    name: string;
    slug: string;
    sort_order?: number | null;
}

interface Resource {
    id: number;
    node_id?: number | null;
    title: string;
    resource_type?: string | null;
    content?: string | null;
    external_url?: string | null;
    file_path?: string | null;
}

export default defineComponent({
    name: 'AdminNode',
    props: {
        subject: { type: Object as PropType<Subject>, required: true },
        nodes: { type: Array as PropType<Node[]>, required: true },
        resources: { type: Array as PropType<Resource[]>, required: true },
        parent: { type: Object as PropType<Node>, default: undefined },
    },
    setup(props) {
        const { can } = usePermissions();
        const page = usePage();

        const isResourceDropdownOpen = ref(false);
        const isFolderDropdownOpen = ref(false);
        const resourceDropdownRef = ref<HTMLElement | null>(null);
        const folderDropdownRef = ref<HTMLElement | null>(null);
        const isBulkModalOpen = ref(false);
        const isBulkImageModalOpen = ref(false);
        const isBulkVideoModalOpen = ref(false);
        const isBulkRenameModalOpen = ref(false);
        const isSingleModalOpen = ref(false);
        const editingNode = ref<Node | null>(null);
        const isSingleResourceModalOpen = ref(false);
        const editingResource = ref<Resource | null>(null);

        const openCreateNodeModal = () => {
            editingNode.value = null;
            isSingleModalOpen.value = true;
        };

        const openEditNodeModal = (node: Node) => {
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

        const openEditResourceModal = (resource: Resource) => {
            editingResource.value = resource;
            isSingleResourceModalOpen.value = true;
        };

        const handleResourceModalClose = () => {
            isSingleResourceModalOpen.value = false;
            editingResource.value = null;
        };

        const totalItemsCount = computed(
            () => (props.nodes?.length ?? 0) + (props.resources?.length ?? 0),
        );

        const backUrl = computed(() => {
            const rawPath = (page.url || '').split('?')[0];
            const segments = rawPath.split('/').filter(Boolean);

            // If at top-level /admin/subjects/{subject}/nodes -> go to /admin/subjects
            const nodesIndex = segments.indexOf('nodes');

            if (nodesIndex === -1 || nodesIndex === segments.length - 1) {
                return '/admin/subjects';
            }

            // Step up one folder level
            segments.pop();

            return '/' + segments.join('/');
        });

        const closeDropdowns = (e: MouseEvent) => {
            const target = e.target as globalThis.Node | null;

            if (
                resourceDropdownRef.value &&
                target &&
                !resourceDropdownRef.value.contains(target)
            ) {
                isResourceDropdownOpen.value = false;
            }

            if (
                folderDropdownRef.value &&
                target &&
                !folderDropdownRef.value.contains(target)
            ) {
                isFolderDropdownOpen.value = false;
            }
        };

        onMounted(() => document.addEventListener('click', closeDropdowns));
        onUnmounted(() =>
            document.removeEventListener('click', closeDropdowns),
        );

        return () => (
            <>
                <Head
                    title={
                        props.parent?.name ||
                        props.subject?.name ||
                        'Manage Nodes'
                    }
                />

                <div class="space-y-6">
                    {/* Page header */}
                    <div class="flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <Link
                                    href={backUrl.value}
                                    class="flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                                    title="Go back"
                                    aria-label="Go back"
                                >
                                    <ArrowLeft
                                        class="h-4 w-4"
                                        strokeWidth={2.2}
                                    />
                                </Link>
                                <h1 class="truncate text-lg font-bold tracking-tight text-slate-900 dark:text-gray-100">
                                    {props.parent?.name
                                        ? props.parent.name
                                        : props.subject.name}
                                </h1>
                                <span class="inline-flex items-center rounded-md bg-indigo-50 px-1.5 py-0.5 text-[11px] font-bold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400">
                                    {totalItemsCount.value}
                                </span>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                                {props.subject.name} ·{' '}
                                {props.nodes?.length ?? 0}{' '}
                                {(props.nodes?.length ?? 0) === 1
                                    ? 'folder'
                                    : 'folders'}{' '}
                                · {props.resources?.length ?? 0}{' '}
                                {(props.resources?.length ?? 0) === 1
                                    ? 'resource'
                                    : 'resources'}
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            {/* Add Folder Dropdown */}
                            {can('create nodes') && (
                                <div
                                    ref={folderDropdownRef}
                                    class="relative inline-block"
                                >
                                    <button
                                        type="button"
                                        onClick={() =>
                                            (isFolderDropdownOpen.value =
                                                !isFolderDropdownOpen.value)
                                        }
                                        class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
                                    >
                                        <FolderPlus
                                            class="h-3.5 w-3.5 text-slate-500 dark:text-gray-400"
                                            strokeWidth={2}
                                        />
                                        <span>
                                            <span class="hidden sm:inline">
                                                Add{' '}
                                            </span>
                                            Folder
                                        </span>
                                        <ChevronDown class="h-3.5 w-3.5 text-slate-400" />
                                    </button>

                                    {isFolderDropdownOpen.value && (
                                        <div class="absolute right-0 z-10 mt-1.5 w-44 rounded-xl border border-slate-100 bg-white p-1 shadow-lg dark:border-gray-800 dark:bg-gray-900">
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    isFolderDropdownOpen.value = false;
                                                    openCreateNodeModal();
                                                }}
                                                class="block w-full cursor-pointer rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-700 transition hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                                            >
                                                Upload Single Folder
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    isFolderDropdownOpen.value = false;
                                                    isBulkModalOpen.value = true;
                                                }}
                                                class="block w-full cursor-pointer rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-700 transition hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                                            >
                                                Add Multiple Folders
                                            </button>
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* Add Resource Dropdown */}
                            {props.parent?.id ? (
                                <div
                                    ref={resourceDropdownRef}
                                    class="relative inline-block"
                                >
                                    <button
                                        type="button"
                                        onClick={() =>
                                            (isResourceDropdownOpen.value =
                                                !isResourceDropdownOpen.value)
                                        }
                                        class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 text-xs font-semibold text-white transition hover:bg-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                                    >
                                        <Plus
                                            class="h-3.5 w-3.5"
                                            strokeWidth={2.2}
                                        />
                                        <span>
                                            <span class="hidden sm:inline">
                                                Add{' '}
                                            </span>
                                            Resource
                                        </span>
                                        <ChevronDown class="h-3.5 w-3.5" />
                                    </button>

                                    {isResourceDropdownOpen.value && (
                                        <div class="absolute right-0 z-10 mt-1.5 w-48 rounded-xl border border-slate-100 bg-white p-1 shadow-lg dark:border-gray-800 dark:bg-gray-900">
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    isResourceDropdownOpen.value = false;
                                                    openCreateResourceModal();
                                                }}
                                                class="block w-full cursor-pointer rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-700 transition hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                                            >
                                                Upload Single Resource
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    isResourceDropdownOpen.value = false;
                                                    isBulkImageModalOpen.value = true;
                                                }}
                                                class="block w-full cursor-pointer rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-700 transition hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                                            >
                                                Upload Multiple Images
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    isResourceDropdownOpen.value = false;
                                                    isBulkVideoModalOpen.value = true;
                                                }}
                                                class="block w-full cursor-pointer rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-700 transition hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                                            >
                                                Upload Multiple Videos
                                            </button>
                                        </div>
                                    )}
                                </div>
                            ) : null}
                        </div>
                    </div>

                    {/* Single Node Modal (Create / Edit) */}
                    <CreateNodeModal
                        isOpen={isSingleModalOpen.value}
                        subject={props.subject}
                        parent={props.parent}
                        node={editingNode.value}
                        onClose={handleNodeModalClose}
                    />

                    {/* Single Resource Modal (Create / Edit) */}
                    {props.parent ? (
                        <CreateResourceModal
                            isOpen={isSingleResourceModalOpen.value}
                            node={props.parent}
                            resource={editingResource.value}
                            onClose={handleResourceModalClose}
                        />
                    ) : null}

                    {/* Bulk Node Modal */}
                    <BulkNodeModal
                        isOpen={isBulkModalOpen.value}
                        subject={props.subject}
                        parent={props.parent}
                        onClose={() => (isBulkModalOpen.value = false)}
                    />

                    {/* Bulk Images Modal */}
                    {props.parent ? (
                        <BulkImageModal
                            isOpen={isBulkImageModalOpen.value}
                            node={props.parent}
                            onClose={() => (isBulkImageModalOpen.value = false)}
                        />
                    ) : null}

                    {/* Bulk Videos Modal */}
                    {props.parent ? (
                        <BulkVideoModal
                            isOpen={isBulkVideoModalOpen.value}
                            node={props.parent}
                            onClose={() => (isBulkVideoModalOpen.value = false)}
                        />
                    ) : null}

                    {/* Bulk Rename Modal */}
                    {props.parent ? (
                        <BulkRenameModal
                            isOpen={isBulkRenameModalOpen.value}
                            node={props.parent}
                            resources={props.resources ?? []}
                            onClose={() =>
                                (isBulkRenameModalOpen.value = false)
                            }
                        />
                    ) : null}

                    <div class="flex flex-1 flex-col">
                        {totalItemsCount.value > 0 ? (
                            <>
                                {can('edit resources') &&
                                props.resources?.length ? (
                                    <div class="flex justify-end">
                                        <button
                                            type="button"
                                            onClick={() =>
                                                (isBulkRenameModalOpen.value = true)
                                            }
                                            class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
                                        >
                                            <PencilLine class="h-3.5 w-3.5" />
                                            <span>Bulk Rename</span>
                                        </button>
                                    </div>
                                ) : null}

                                <div class="flex flex-col gap-2">
                                    {props.nodes.map((node) => (
                                        <NodeRow
                                            key={`node-${node.id}`}
                                            node={node}
                                            onEdit={openEditNodeModal}
                                        />
                                    ))}
                                    {props.resources.map((resource) => (
                                        <ResourceRow
                                            key={`resource-${resource.id}`}
                                            resource={resource}
                                            onEdit={openEditResourceModal}
                                        />
                                    ))}
                                </div>
                            </>
                        ) : (
                            <EmptyState
                                title="No items in this folder"
                                description="Create a new folder or resource above to get started."
                            />
                        )}
                    </div>
                </div>
            </>
        );
    },
});
