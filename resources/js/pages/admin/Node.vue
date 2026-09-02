<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Plus,
    FolderPlus,
    ArrowLeft,
    ChevronDown,
    PencilLine,
} from 'lucide-vue-next';
import { computed, ref, onMounted, onUnmounted } from 'vue';
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

const { can } = usePermissions();
const page = usePage();

const props = defineProps({
    subject: Object,
    nodes: Array,
    resources: Array,
    parent: Object,
});

const isResourceDropdownOpen = ref(false);
const isFolderDropdownOpen = ref(false);
const resourceDropdownRef = ref<HTMLElement | null>(null);
const folderDropdownRef = ref<HTMLElement | null>(null);
const isBulkModalOpen = ref(false);
const isBulkImageModalOpen = ref(false);
const isBulkVideoModalOpen = ref(false);
const isBulkRenameModalOpen = ref(false);
const isSingleModalOpen = ref(false);
const editingNode = ref<any | null>(null);
const isSingleResourceModalOpen = ref(false);
const editingResource = ref<any | null>(null);

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
    const target = e.target as Node | null;

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
onUnmounted(() => document.removeEventListener('click', closeDropdowns));
</script>

<template>
    <Head :title="parent?.name || subject?.name || 'Manage Nodes'" />

    <div class="flex w-full flex-1 flex-col">
        <!-- Compact Page Title Bar -->
        <div
            class="mb-3.5 flex shrink-0 items-center justify-between gap-2 border-b border-slate-100 pb-3 sm:gap-3 dark:border-gray-800"
        >
            <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-2.5">
                <Link
                    :href="backUrl"
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-2xs transition-colors hover:bg-slate-50 hover:text-slate-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                    title="Go back"
                >
                    <ArrowLeft class="h-4 w-4" :stroke-width="2.2" />
                </Link>

                <h3
                    class="truncate text-sm font-bold tracking-tight text-slate-900 sm:text-base dark:text-gray-100"
                >
                    {{ parent?.name ? parent.name : subject.name }}
                </h3>
            </div>

            <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                <!-- Add Folder Dropdown -->
                <div
                    v-if="can('create nodes')"
                    ref="folderDropdownRef"
                    class="relative inline-block"
                >
                    <button
                        type="button"
                        @click="isFolderDropdownOpen = !isFolderDropdownOpen"
                        class="inline-flex cursor-pointer items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs transition-colors hover:bg-slate-50 sm:gap-1.5 sm:px-3 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <FolderPlus
                            class="h-3.5 w-3.5 text-slate-500 dark:text-gray-400"
                            :stroke-width="2"
                        />
                        <span
                            ><span class="hidden sm:inline">Add </span
                            >Folder</span
                        >
                        <ChevronDown class="h-3.5 w-3.5 text-slate-400" />
                    </button>

                    <div
                        v-if="isFolderDropdownOpen"
                        class="absolute right-0 z-10 mt-1.5 w-44 rounded-xl border border-slate-100 bg-white p-1 shadow-lg dark:border-gray-800 dark:bg-gray-900"
                    >
                        <button
                            type="button"
                            @click="
                                isFolderDropdownOpen = false;
                                openCreateNodeModal();
                            "
                            class="block w-full cursor-pointer rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                        >
                            Upload Single Folder
                        </button>
                        <button
                            type="button"
                            @click="
                                isFolderDropdownOpen = false;
                                isBulkModalOpen = true;
                            "
                            class="block w-full cursor-pointer rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                        >
                            Add Multiple Folders
                        </button>
                    </div>
                </div>

                <!-- Add Resource Dropdown -->
                <div
                    v-if="parent?.id"
                    ref="resourceDropdownRef"
                    class="relative inline-block"
                >
                    <button
                        type="button"
                        @click="
                            isResourceDropdownOpen = !isResourceDropdownOpen
                        "
                        class="inline-flex cursor-pointer items-center gap-1 rounded-lg bg-indigo-600 px-2.5 py-1.5 text-xs font-semibold text-white shadow-2xs transition-colors duration-150 hover:bg-indigo-700 sm:gap-1.5 sm:px-3"
                    >
                        <Plus class="h-3.5 w-3.5" :stroke-width="2.2" />
                        <span
                            ><span class="hidden sm:inline">Add </span
                            >Resource</span
                        >
                        <ChevronDown class="h-3.5 w-3.5" />
                    </button>

                    <div
                        v-if="isResourceDropdownOpen"
                        class="absolute right-0 z-10 mt-1.5 w-48 rounded-xl border border-slate-100 bg-white p-1 shadow-lg dark:border-gray-800 dark:bg-gray-900"
                    >
                        <button
                            type="button"
                            @click="
                                isResourceDropdownOpen = false;
                                openCreateResourceModal();
                            "
                            class="block w-full cursor-pointer rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                        >
                            Upload Single Resource
                        </button>
                        <button
                            type="button"
                            @click="
                                isResourceDropdownOpen = false;
                                isBulkImageModalOpen = true;
                            "
                            class="block w-full cursor-pointer rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                        >
                            Upload Multiple Images
                        </button>
                        <button
                            type="button"
                            @click="
                                isResourceDropdownOpen = false;
                                isBulkVideoModalOpen = true;
                            "
                            class="block w-full cursor-pointer rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                        >
                            Upload Multiple Videos
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Single Node Modal (Create / Edit) -->
        <CreateNodeModal
            :is-open="isSingleModalOpen"
            :subject="subject"
            :parent="parent"
            :node="editingNode"
            @close="handleNodeModalClose"
        />

        <!-- Single Resource Modal (Create / Edit) -->
        <CreateResourceModal
            v-if="parent"
            :is-open="isSingleResourceModalOpen"
            :node="parent"
            :resource="editingResource"
            @close="handleResourceModalClose"
        />

        <!-- Bulk Node Modal -->
        <BulkNodeModal
            :is-open="isBulkModalOpen"
            :subject="subject"
            :parent="parent"
            @close="isBulkModalOpen = false"
        />

        <!-- Bulk Images Modal -->
        <BulkImageModal
            v-if="parent"
            :is-open="isBulkImageModalOpen"
            :node="parent"
            @close="isBulkImageModalOpen = false"
        />

        <!-- Bulk Videos Modal -->
        <BulkVideoModal
            v-if="parent"
            :is-open="isBulkVideoModalOpen"
            :node="parent"
            @close="isBulkVideoModalOpen = false"
        />

        <!-- Bulk Rename Modal -->
        <BulkRenameModal
            v-if="parent"
            :is-open="isBulkRenameModalOpen"
            :node="parent"
            :resources="resources ?? []"
            @close="isBulkRenameModalOpen = false"
        />

        <div class="flex flex-1 flex-col">
            <template v-if="totalItemsCount > 0">
                <div
                    class="flex shrink-0 items-center justify-between rounded-t-lg border-b border-gray-100 bg-gray-50/70 px-4 py-2.5 text-xs font-semibold tracking-wider text-gray-400 uppercase dark:border-gray-700 dark:bg-gray-800/70 dark:text-gray-500"
                >
                    <span>Resources</span>

                    <button
                        v-if="can('edit resources') && resources?.length"
                        type="button"
                        @click="isBulkRenameModalOpen = true"
                        class="inline-flex cursor-pointer items-center gap-1 rounded-md px-2 py-0.5 text-xs font-medium text-indigo-600 transition-colors hover:bg-indigo-50 hover:text-indigo-700 dark:text-indigo-400 dark:hover:bg-indigo-950/50 dark:hover:text-indigo-300"
                    >
                        <PencilLine class="h-3.5 w-3.5" />
                        <span>Bulk Rename</span>
                    </button>
                </div>

                <div class="flex flex-col gap-2.5 sm:gap-3">
                    <NodeRow
                        v-for="node in nodes"
                        :key="`node-${node.id}`"
                        :node="node"
                        @edit="openEditNodeModal"
                    />
                    <ResourceRow
                        v-for="resource in resources"
                        :key="`resource-${resource.id}`"
                        :resource="resource"
                        @edit="openEditResourceModal"
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
