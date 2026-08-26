<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus, FolderPlus, ArrowLeft, ChevronDown } from 'lucide-vue-next';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import NodeRow from '@/components/admin/NodeRow.vue';
import ResourceRow from '@/components/admin/ResourceRow.vue';
import EmptyState from '@/components/EmptyState.vue';

const props = defineProps({
    subject: Object,
    nodes: Array,
    resources: Array,
    parent: Object,
});

const isDropdownOpen = ref(false);
const dropdownRef = ref(null);

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

const closeDropdown = (e) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
        isDropdownOpen.value = false;
    }
};

onMounted(() => document.addEventListener('click', closeDropdown));
onUnmounted(() => document.removeEventListener('click', closeDropdown));
</script>

<template>
    <Head :title="parent?.name || subject?.name || 'Manage Nodes'" />

    <div class="flex w-full flex-1 flex-col">
        <!-- Compact Page Title Bar -->
        <div
            class="mb-3.5 flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 pb-3 dark:border-gray-800"
        >
            <div class="flex items-center gap-2.5 min-w-0">
                <button
                    @click="handleBack"
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-2xs transition-colors hover:bg-slate-50 hover:text-slate-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                    title="Go back"
                >
                    <ArrowLeft class="h-4 w-4" :stroke-width="2.2" />
                </button>

                <div class="flex flex-wrap items-center gap-2 min-w-0">
                    <h3
                        class="text-base font-bold tracking-tight text-slate-900 truncate dark:text-gray-100"
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
                <Link
                    :href="
                        parent
                            ? `/admin/subjects/${subject.slug}/nodes/create?parent_id=${parent.id}`
                            : `/admin/subjects/${subject.slug}/nodes/create`
                    "
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs transition-colors hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    <FolderPlus
                        class="h-3.5 w-3.5 text-slate-500 dark:text-gray-400"
                        :stroke-width="2"
                    />
                    <span>Add Folder</span>
                </Link>

                <!-- Add Resource Dropdown -->
                <div
                    v-if="parent?.id"
                    ref="dropdownRef"
                    class="relative inline-block"
                >
                    <button
                        type="button"
                        @click="isDropdownOpen = !isDropdownOpen"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-2xs transition-colors duration-150 hover:bg-indigo-700"
                    >
                        <Plus class="h-3.5 w-3.5" :stroke-width="2.2" />
                        <span>Add Resource</span>
                        <ChevronDown class="h-3.5 w-3.5" />
                    </button>

                    <div
                        v-if="isDropdownOpen"
                        class="absolute right-0 z-10 mt-1.5 w-44 rounded-xl border border-slate-100 bg-white p-1 shadow-lg dark:border-gray-800 dark:bg-gray-900"
                    >
                        <Link
                            :href="`/admin/resources/create?node_id=${parent.id}`"
                            @click="isDropdownOpen = false"
                            class="block rounded-lg px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                        >
                            Single File
                        </Link>
                        <Link
                            :href="`/admin/resources/create/bulk/images?node_id=${parent.id}&redirect=${currentUrl}`"
                            @click="isDropdownOpen = false"
                            class="block rounded-lg px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                        >
                            Upload Bulk Images
                        </Link>
                        <Link
                            :href="`/admin/resources/create/bulk/videos?node_id=${parent.id}&redirect=${currentUrl}`"
                            @click="isDropdownOpen = false"
                            class="block rounded-lg px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                        >
                            Upload Bulk Videos
                        </Link>
                    </div>
                </div>
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
                    class="flex flex-col gap-2.5 sm:gap-3"
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
    </div>
</template>
