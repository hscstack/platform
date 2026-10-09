<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Plus,
    FolderPlus,
    ArrowLeft,
    ChevronDown,
    ChevronRight,
    PencilLine,
    Lock,
    Unlock,
    Clock,
    Eye,
    ExternalLink,
    User,
    FileText,
    FileArchive,
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
import BaseModal from '@/components/BaseModal.vue';
import EmptyState from '@/components/EmptyState.vue';
import { usePermissions } from '@/lib/usePermissions';

const { can } = usePermissions();
const page = usePage();

const props = defineProps({
    subject: Object,
    nodes: Array,
    resources: Array,
    pending_creates: Array,
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
        (props.pending_creates?.length ?? 0),
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
        <!-- Dedicated Breadcrumb Navigation Bar (Separate Row) -->
        <div
            v-if="adminBreadcrumbs.length > 0"
            class="mb-3.5 flex items-center rounded-xl bg-slate-50/80 px-3 py-2 text-xs font-medium text-slate-500 sm:text-sm dark:bg-gray-800/40 dark:text-gray-400"
        >
            <nav
                class="no-scrollbar flex min-w-0 flex-wrap items-center gap-1.5"
            >
                <template v-for="(crumb, idx) in adminBreadcrumbs" :key="idx">
                    <ChevronRight
                        v-if="idx > 0"
                        class="h-3.5 w-3.5 shrink-0 stroke-[2.5] text-slate-300 dark:text-gray-600"
                    />
                    <span
                        v-if="idx === adminBreadcrumbs.length - 1"
                        class="font-bold text-slate-900 dark:text-gray-100"
                        :title="crumb.name"
                    >
                        {{ crumb.name }}
                    </span>
                    <Link
                        v-else
                        :href="crumb.link"
                        class="transition-colors hover:text-indigo-600 dark:hover:text-indigo-400"
                        :title="crumb.name"
                    >
                        {{ crumb.name }}
                    </Link>
                </template>
            </nav>
        </div>

        <!-- Freeze Notice Banner -->
        <div
            v-if="isFrozen"
            class="mb-3.5 flex items-center gap-2.5 rounded-xl border px-3.5 py-2.5 text-xs font-medium"
            :class="
                isDirectlyFrozen
                    ? 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-800/60 dark:bg-sky-950/40 dark:text-sky-300'
                    : 'border-amber-200 bg-amber-50/80 text-amber-800 dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-300'
            "
        >
            <Lock
                class="h-4 w-4 shrink-0"
                :class="
                    isDirectlyFrozen
                        ? 'text-sky-600 dark:text-sky-400'
                        : 'text-amber-600 dark:text-amber-400'
                "
            />
            <span v-if="isDirectlyFrozen"
                >এই ফোল্ডারটি ফ্রিজ (লক) করা রয়েছে। এখানে নতুন কিছু আপলোড, এডিট
                বা ডিলিট করা যাবে না।</span
            >
            <span v-else
                >প্যারেন্ট ফোল্ডার লক থাকায় এই ফোল্ডারটিও স্বয়ংক্রিয়ভাবে লক
                রয়েছে। এখানে নতুন কিছু আপলোড, এডিট বা ডিলিট করা যাবে না।</span
            >
        </div>

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
                <!-- Toggle Freeze Button -->
                <button
                    v-if="parent?.id && can('freeze nodes')"
                    type="button"
                    :disabled="isTogglingFreeze"
                    @click="toggleFreeze"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-semibold shadow-2xs transition-colors disabled:opacity-50 sm:px-3"
                    :class="
                        isDirectlyFrozen
                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 dark:hover:bg-emerald-900/50'
                            : isInheritedFrozen
                              ? 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-300 dark:hover:bg-amber-900/50'
                              : 'border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100 dark:border-sky-800 dark:bg-sky-950/50 dark:text-sky-300 dark:hover:bg-sky-900/50'
                    "
                    :title="
                        isDirectlyFrozen
                            ? 'Unfreeze this folder'
                            : isInheritedFrozen
                              ? 'Freeze this folder individually'
                              : 'Freeze this folder'
                    "
                >
                    <Unlock
                        v-if="isDirectlyFrozen"
                        class="h-3.5 w-3.5"
                        :stroke-width="2"
                    />
                    <Lock v-else class="h-3.5 w-3.5" :stroke-width="2" />
                    <span>{{
                        isDirectlyFrozen
                            ? 'Unfreeze'
                            : isInheritedFrozen
                              ? 'Freeze Individually'
                              : 'Freeze'
                    }}</span>
                </button>

                <!-- Add Folder Dropdown -->
                <div
                    v-if="!isFrozen && can('create nodes')"
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
                    v-if="!isFrozen && parent?.id && can('create resources')"
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

        <!-- Pending Resource Preview Modal -->
        <BaseModal
            :is-open="viewingPendingModal !== null"
            max-width="xl"
            @close="viewingPendingModal = null"
        >
            <template #header>
                <div class="flex items-center gap-2.5">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400"
                    >
                        <Clock class="h-4.5 w-4.5 animate-pulse" />
                    </div>
                    <div>
                        <h3
                            class="text-base font-bold text-slate-900 dark:text-gray-100"
                        >
                            Resource Submission
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-gray-400">
                            Awaiting moderation review before public release
                        </p>
                    </div>
                </div>
            </template>

            <div v-if="viewingPendingModal" class="space-y-5 p-4 sm:p-6">
                <!-- Status & Submission Meta Banner -->
                <div
                    class="flex flex-wrap items-center justify-between gap-2.5 rounded-xl border border-amber-200/80 bg-amber-50/60 px-4 py-3 text-xs dark:border-amber-900/40 dark:bg-amber-950/20"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-flex items-center rounded-full bg-amber-500 px-2.5 py-0.5 text-[11px] font-bold text-white shadow-2xs"
                        >
                            Pending Review
                        </span>
                        <span
                            class="font-medium text-amber-900/80 dark:text-amber-300"
                        >
                            Action: Create Resource
                        </span>
                    </div>

                    <div
                        v-if="viewingPendingModal.user?.name"
                        class="flex items-center gap-1.5 text-slate-600 dark:text-gray-400"
                    >
                        <User class="h-3.5 w-3.5 text-slate-400" />
                        <span>
                            Submitted by
                            <Link
                                v-if="viewingPendingModal.user?.username"
                                :href="`/u/${viewingPendingModal.user.username}`"
                                class="font-semibold text-slate-900 transition-colors hover:text-indigo-600 hover:underline dark:text-gray-100 dark:hover:text-indigo-400"
                            >
                                {{ viewingPendingModal.user.name }}
                            </Link>
                            <strong
                                v-else
                                class="font-semibold text-slate-900 dark:text-gray-100"
                            >
                                {{ viewingPendingModal.user.name }}
                            </strong>
                        </span>
                    </div>
                </div>

                <!-- Main Card: Title & Type -->
                <div
                    class="rounded-xl border border-slate-200/90 bg-white p-4 shadow-2xs dark:border-gray-800 dark:bg-gray-900/50"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <span
                                class="text-[10px] font-bold tracking-wider text-slate-400 uppercase dark:text-gray-500"
                                >Resource Title</span
                            >
                            <h2
                                class="mt-0.5 text-lg font-bold break-words text-slate-900 dark:text-white"
                            >
                                {{
                                    viewingPendingModal.payload?.title ||
                                    '(Untitled)'
                                }}
                            </h2>
                        </div>
                        <span
                            class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-bold tracking-wide text-slate-700 uppercase dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                        >
                            {{
                                viewingPendingModal.payload?.resource_type ||
                                'Resource'
                            }}
                        </span>
                    </div>

                    <!-- Description / Body -->
                    <div
                        v-if="viewingPendingModal.payload?.content"
                        class="mt-4 border-t border-slate-100 pt-3 dark:border-gray-800/80"
                    >
                        <span
                            class="text-[10px] font-bold tracking-wider text-slate-400 uppercase dark:text-gray-500"
                            >Description / Content</span
                        >
                        <p
                            class="mt-1.5 text-sm leading-relaxed whitespace-pre-wrap text-slate-700 dark:text-gray-300"
                        >
                            {{ viewingPendingModal.payload.content }}
                        </p>
                    </div>

                    <!-- External URL -->
                    <div
                        v-if="viewingPendingModal.payload?.external_url"
                        class="mt-4 border-t border-slate-100 pt-3 dark:border-gray-800/80"
                    >
                        <span
                            class="text-[10px] font-bold tracking-wider text-slate-400 uppercase dark:text-gray-500"
                            >External URL</span
                        >
                        <div class="mt-1">
                            <a
                                :href="viewingPendingModal.payload.external_url"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-700 hover:underline dark:text-indigo-400"
                            >
                                <span class="break-all">{{
                                    viewingPendingModal.payload.external_url
                                }}</span>
                                <ExternalLink class="h-3.5 w-3.5 shrink-0" />
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Attached File Media Card -->
                <div
                    v-if="viewingPendingModal.staged_file_url"
                    class="overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-2xs dark:border-gray-800 dark:bg-gray-900/50"
                >
                    <div
                        class="flex items-center justify-between border-b border-slate-100 bg-slate-50/70 px-4 py-2.5 text-xs font-semibold text-slate-700 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-300"
                    >
                        <span class="flex items-center gap-1.5 font-bold">
                            <FileText
                                class="h-4 w-4 text-slate-500 dark:text-gray-400"
                            />
                            Attached Media / File
                        </span>
                        <a
                            :href="viewingPendingModal.staged_file_url"
                            target="_blank"
                            class="inline-flex items-center gap-1 font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                        >
                            <span>Open file in new tab</span>
                            <ExternalLink class="h-3 w-3" />
                        </a>
                    </div>

                    <div class="p-4">
                        <div
                            v-if="
                                viewingPendingModal.payload?.resource_type ===
                                    'image' ||
                                viewingPendingModal.staged_file_url.match(
                                    /\.(jpeg|jpg|gif|png|webp|svg)$/i,
                                )
                            "
                            class="flex flex-col items-center justify-center rounded-xl border border-slate-100 bg-slate-50/60 p-3 dark:border-gray-800 dark:bg-gray-950/40"
                        >
                            <img
                                :src="viewingPendingModal.staged_file_url"
                                alt="Staged Preview"
                                class="max-h-80 w-auto rounded-lg object-contain shadow-2xs"
                            />
                        </div>

                        <div
                            v-else
                            class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50/60 p-4 dark:border-gray-800 dark:bg-gray-800/40"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400"
                                >
                                    <FileArchive class="h-5 w-5" />
                                </div>
                                <div>
                                    <div
                                        class="text-xs font-semibold text-slate-900 dark:text-white"
                                    >
                                        Uploaded Attachment
                                    </div>
                                    <div
                                        class="text-[11px] text-slate-500 dark:text-gray-400"
                                    >
                                        Click to download or view file content
                                    </div>
                                </div>
                            </div>
                            <a
                                :href="viewingPendingModal.staged_file_url"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                            >
                                <ExternalLink class="h-3.5 w-3.5" />
                                <span>Open File</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex items-center justify-end gap-3">
                    <Link
                        v-if="can('moderate resources')"
                        href="/admin/moderation/resources"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-amber-400 bg-amber-500 px-3.5 py-2 text-xs font-semibold text-white shadow-2xs transition-colors hover:bg-amber-600 dark:border-amber-500 dark:bg-amber-600 dark:hover:bg-amber-700"
                    >
                        <span>Review in Moderation Queue</span>
                        <ChevronRight class="h-3.5 w-3.5" />
                    </Link>

                    <button
                        type="button"
                        @click="viewingPendingModal = null"
                        class="rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                    >
                        Close
                    </button>
                </div>
            </template>
        </BaseModal>

        <div class="flex flex-1 flex-col">
            <template v-if="totalItemsCount > 0">
                <div
                    class="flex shrink-0 items-center justify-between rounded-t-lg border-b border-gray-100 bg-gray-50/70 px-4 py-2.5 text-xs font-semibold tracking-wider text-gray-400 uppercase dark:border-gray-700 dark:bg-gray-800/70 dark:text-gray-500"
                >
                    <span>Resources</span>

                    <button
                        v-if="
                            !isFrozen &&
                            can('edit resources') &&
                            resources?.length
                        "
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
                        :is-frozen="isFrozen"
                        @edit="openEditNodeModal"
                    />
                    <!-- Pending New Uploads (Under Moderation) -->
                    <div
                        v-for="pending in pending_creates"
                        :key="`pending-create-${pending.id}`"
                        @click="viewingPendingModal = pending"
                        class="group relative flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-amber-200/80 bg-amber-50/50 p-3 transition hover:border-amber-300 hover:bg-amber-50 sm:p-3.5 dark:border-amber-900/40 dark:bg-amber-950/20 dark:hover:border-amber-800/80"
                    >
                        <div class="flex min-w-0 flex-1 items-center gap-3">
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-amber-300 bg-amber-100 text-amber-700 sm:h-10 sm:w-10 dark:border-amber-800 dark:bg-amber-900/50 dark:text-amber-300"
                            >
                                <Clock class="h-4.5 w-4.5 animate-pulse" />
                            </div>

                            <div
                                class="flex min-w-0 flex-wrap items-center gap-2"
                            >
                                <h3
                                    class="text-sm font-semibold break-words text-slate-900 transition-colors group-hover:text-amber-700 dark:text-gray-100 dark:group-hover:text-amber-300"
                                >
                                    {{ pending.payload?.title || '(Untitled)' }}
                                </h3>

                                <span
                                    class="inline-flex items-center gap-1 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-800 dark:bg-amber-500/20 dark:text-amber-300"
                                >
                                    Pending Approval
                                </span>

                                <span
                                    v-if="pending.user?.name"
                                    class="text-xs text-slate-400 dark:text-gray-500"
                                >
                                    by {{ pending.user.name }}
                                </span>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <button
                                type="button"
                                @click="viewingPendingModal = pending"
                                class="inline-flex cursor-pointer items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                <Eye class="h-3.5 w-3.5 text-slate-500" />
                                <span>Preview</span>
                            </button>

                            <Link
                                v-if="can('moderate resources')"
                                href="/admin/moderation/resources"
                                class="inline-flex cursor-pointer items-center rounded-lg border border-amber-300 bg-white px-2.5 py-1 text-xs font-semibold text-amber-800 shadow-2xs hover:bg-amber-50 dark:border-amber-800 dark:bg-gray-900 dark:text-amber-300 dark:hover:bg-gray-800"
                            >
                                Review in Queue
                            </Link>
                        </div>
                    </div>

                    <ResourceRow
                        v-for="resource in resources"
                        :key="`resource-${resource.id}`"
                        :resource="resource"
                        :is-frozen="isFrozen"
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
