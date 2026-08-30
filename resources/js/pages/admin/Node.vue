<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Plus,
    FolderPlus,
    ArrowLeft,
    ChevronDown,
    Layers,
} from 'lucide-vue-next';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import BulkNodeModal from '@/components/admin/BulkNodeModal.vue';
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
const isSingleModalOpen = ref(false);
const editingNode = ref<any | null>(null);
const isSingleResourceModalOpen = ref(false);

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
            class="mb-3.5 flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 pb-3 dark:border-gray-800"
        >
            <div class="flex min-w-0 items-center gap-2.5">
                <Link
                    :href="backUrl"
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-2xs transition-colors hover:bg-slate-50 hover:text-slate-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                    title="Go back"
                >
                    <ArrowLeft class="h-4 w-4" :stroke-width="2.2" />
                </Link>

                <div class="flex min-w-0 flex-wrap items-center gap-2">
                    <h3
                        class="truncate text-base font-bold tracking-tight text-slate-900 dark:text-gray-100"
                    >
                        {{ parent?.name ? parent.name : subject.name }}
                    </h3>

                    <span
                        class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-[11px] font-bold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400"
                    >
                        {{ totalItemsCount }}
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <!-- Add Folder Dropdown -->
                <div
                    v-if="can('create nodes')"
                    ref="folderDropdownRef"
                    class="relative inline-block"
                >
                    <button
                        type="button"
                        @click="isFolderDropdownOpen = !isFolderDropdownOpen"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs transition-colors hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <FolderPlus
                            class="h-3.5 w-3.5 text-slate-500 dark:text-gray-400"
                            :stroke-width="2"
                        />
                        <span>Add Folder</span>
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
                            Single Folder
                        </button>
                        <button
                            type="button"
                            @click="
                                isFolderDropdownOpen = false;
                                isBulkModalOpen = true;
                            "
                            class="flex w-full cursor-pointer items-center justify-between rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                        >
                            <span>Bulk Add Folders</span>
                            <Layers
                                class="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400"
                            />
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
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-2xs transition-colors duration-150 hover:bg-indigo-700"
                    >
                        <Plus class="h-3.5 w-3.5" :stroke-width="2.2" />
                        <span>Add Resource</span>
                        <ChevronDown class="h-3.5 w-3.5" />
                    </button>

                    <div
                        v-if="isResourceDropdownOpen"
                        class="absolute right-0 z-10 mt-1.5 w-44 rounded-xl border border-slate-100 bg-white p-1 shadow-lg dark:border-gray-800 dark:bg-gray-900"
                    >
                        <button
                            type="button"
                            @click="
                                isResourceDropdownOpen = false;
                                isSingleResourceModalOpen = true;
                            "
                            class="block w-full cursor-pointer rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                        >
                            Single Resource
                        </button>
                        <Link
                            :href="`/admin/resources/create/bulk/images?node_id=${parent.id}&redirect=${page.url}`"
                            @click="isResourceDropdownOpen = false"
                            class="block rounded-lg px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                        >
                            Upload Bulk Images
                        </Link>
                        <Link
                            :href="`/admin/resources/create/bulk/videos?node_id=${parent.id}&redirect=${page.url}`"
                            @click="isResourceDropdownOpen = false"
                            class="block rounded-lg px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                        >
                            Upload Bulk Videos
                        </Link>
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

        <!-- Single Resource Modal -->
        <CreateResourceModal
            v-if="parent"
            :is-open="isSingleResourceModalOpen"
            :node="parent"
            @close="isSingleResourceModalOpen = false"
        />

        <!-- Bulk Node Modal -->
        <BulkNodeModal
            :is-open="isBulkModalOpen"
            :subject="subject"
            :parent="parent"
            @close="isBulkModalOpen = false"
        />

        <div class="flex flex-1 flex-col">
            <template v-if="totalItemsCount > 0">
                <div
                    class="flex shrink-0 items-center justify-between rounded-t-lg border-b border-gray-100 bg-gray-50/70 px-4 py-2.5 text-xs font-semibold tracking-wider text-gray-400 uppercase dark:border-gray-700 dark:bg-gray-800/70 dark:text-gray-500"
                >
                    <span>Resources</span>
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
                    />
                </div>
            </template>

            <div v-else class="flex flex-1 items-center justify-center py-12">
                <EmptyState />
            </div>
        </div>
    </div>
</template>
