<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
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
                        Total: {{ totalItemsCount }}
                    </span>
                </div>

                <Link
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
                        class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3.5 py-1.5 text-xs font-medium text-white shadow-sm transition-colors duration-150 hover:bg-blue-700"
                    >
                        <Plus class="h-3.5 w-3.5" :stroke-width="2.5" />
                        Add Resource
                        <ChevronDown class="h-3.5 w-3.5" />
                    </button>

                    <div
                        v-if="isDropdownOpen"
                        class="absolute right-0 z-10 mt-2 w-44 rounded-lg border border-gray-100 bg-white p-1 shadow-lg"
                    >
                        <Link
                            :href="`/admin/resources/create?node_id=${parent.id}`"
                            @click="isDropdownOpen = false"
                            class="block rounded-md px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 hover:text-gray-900"
                        >
                            Single File
                        </Link>
                        <Link
                            :href="`/admin/resources/create/bulk/images?node_id=${parent.id}&redirect=${currentUrl}`"
                            @click="isDropdownOpen = false"
                            class="block rounded-md px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 hover:text-gray-900"
                        >
                            Upload Bulk Images
                        </Link>
                        <Link
                            :href="`/admin/resources/create/bulk/videos?node_id=${parent.id}&redirect=${currentUrl}`"
                            @click="isDropdownOpen = false"
                            class="block rounded-md px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 hover:text-gray-900"
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
                    class="flex shrink-0 items-center justify-between rounded-t-lg border-b border-gray-100 bg-gray-50/70 px-4 py-2.5 text-xs font-semibold tracking-wider text-gray-400 uppercase"
                >
                    <span>Resources</span>
                </div>

                <div
                    class="grid grid-cols-2 gap-4 rounded-b-lg border border-gray-100 p-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6"
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
