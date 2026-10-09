<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    AlertCircle,
    Book,
    Check,
    CheckCheck,
    ExternalLink,
    File,
    FileArchive,
    FileImage,
    FileVideo,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import Pagination from '@/components/Pagination.vue';
import VerifiedBadge from '@/components/VerifiedBadge.vue';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface ResourceItem {
    id: number;
    title: string;
    resource_type: string;
    status: string;
    created_at: string;
    updated_at: string;
    user?: {
        id: number;
        name: string;
        username: string;
        image_path: string | null;
        is_verified?: boolean;
    };
    node?: {
        id: number;
        name: string;
        slug: string;
        subject?: {
            id: number;
            name: string;
            slug: string;
        };
    };
}

interface PaginatedResources {
    data: ResourceItem[];
    total: number;
    current_page: number;
    last_page: number;
    per_page: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
}

const props = defineProps<{
    resources: PaginatedResources;
}>();

const selectedIds = ref<number[]>([]);
const isProcessing = ref(false);

const rejectModalOpen = ref(false);
const rejectingResource = ref<ResourceItem | null>(null);
const rejectionReason = ref('');

const allSelected = computed(() => {
    return (
        props.resources.data.length > 0 &&
        props.resources.data.every((r) => selectedIds.value.includes(r.id))
    );
});

const toggleSelectAll = () => {
    if (allSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = props.resources.data.map((r) => r.id);
    }
};

const toggleSelect = (id: number) => {
    const idx = selectedIds.value.indexOf(id);

    if (idx > -1) {
        selectedIds.value.splice(idx, 1);
    } else {
        selectedIds.value.push(id);
    }
};

const isSelected = (id: number) => selectedIds.value.includes(id);

const approveSelected = () => {
    if (selectedIds.value.length === 0) {
return;
}

    if (
        !confirm(
            `Are you sure you want to approve ${selectedIds.value.length} selected resource(s)?`,
        )
    ) {
return;
}

    isProcessing.value = true;
    router.post(
        '/admin/resources/approve',
        { ids: selectedIds.value },
        {
            preserveScroll: true,
            onFinish: () => {
                isProcessing.value = false;
                selectedIds.value = [];
            },
        },
    );
};

const approveSingle = (resource: ResourceItem) => {
    isProcessing.value = true;
    router.post(
        '/admin/resources/approve',
        { ids: [resource.id] },
        {
            preserveScroll: true,
            onFinish: () => {
                isProcessing.value = false;
                const idx = selectedIds.value.indexOf(resource.id);

                if (idx > -1) {
selectedIds.value.splice(idx, 1);
}
            },
        },
    );
};

const openRejectModal = (resource: ResourceItem) => {
    rejectingResource.value = resource;
    rejectionReason.value = '';
    rejectModalOpen.value = true;
};

const closeRejectModal = () => {
    rejectModalOpen.value = false;
    rejectingResource.value = null;
    rejectionReason.value = '';
};

const confirmReject = () => {
    if (!rejectingResource.value) {
return;
}

    isProcessing.value = true;
    router.post(
        `/admin/resources/${rejectingResource.value.id}/reject`,
        { rejection_reason: rejectionReason.value },
        {
            preserveScroll: true,
            onFinish: () => {
                isProcessing.value = false;
                closeRejectModal();
            },
        },
    );
};

const formatDate = (dateStr: string) => {
    try {
        const d = new Date(dateStr);

        return d.toLocaleDateString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return dateStr;
    }
};
</script>

<template>
    <Head title="Pending Resources Review" />

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div
            class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
        >
            <div>
                <h1
                    class="text-2xl font-bold text-slate-900 dark:text-gray-100"
                >
                    Pending Resources
                </h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                    Review, approve, or reject submissions and edits before they
                    go live.
                </p>
            </div>

            <!-- Bulk Action Toolbar -->
            <div
                v-if="selectedIds.length > 0"
                class="flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 p-2 dark:border-amber-500/30 dark:bg-amber-950/40"
            >
                <span
                    class="px-2 text-xs font-semibold text-amber-900 dark:text-amber-200"
                >
                    {{ selectedIds.length }} selected
                </span>
                <button
                    type="button"
                    :disabled="isProcessing"
                    @click="approveSelected"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-500 disabled:opacity-50"
                >
                    <CheckCheck class="h-3.5 w-3.5" />
                    Approve Selected
                </button>
                <button
                    type="button"
                    @click="selectedIds = []"
                    class="cursor-pointer rounded-lg px-2 py-1.5 text-xs text-slate-600 hover:bg-slate-200/50 dark:text-gray-400 dark:hover:bg-gray-800"
                >
                    Clear
                </button>
            </div>
        </div>

        <!-- Table Container -->
        <div
            v-if="resources.data.length > 0"
            class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900"
        >
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead
                        class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-500 uppercase dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-400"
                    >
                        <tr>
                            <th class="w-10 px-4 py-3.5 text-center">
                                <input
                                    type="checkbox"
                                    :checked="allSelected"
                                    @change="toggleSelectAll"
                                    class="h-4 w-4 cursor-pointer rounded border-slate-300 text-amber-600 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800"
                                />
                            </th>
                            <th class="px-4 py-3.5">Resource</th>
                            <th class="px-4 py-3.5">Author</th>
                            <th class="px-4 py-3.5">Folder / Topic</th>
                            <th class="px-4 py-3.5">Type</th>
                            <th class="px-4 py-3.5">Date</th>
                            <th class="px-4 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-slate-100 dark:divide-gray-800"
                    >
                        <tr
                            v-for="item in resources.data"
                            :key="item.id"
                            :class="[
                                isSelected(item.id)
                                    ? 'bg-amber-50/40 dark:bg-amber-950/20'
                                    : 'hover:bg-slate-50/60 dark:hover:bg-gray-800/40',
                                'transition-colors',
                            ]"
                        >
                            <!-- Checkbox -->
                            <td class="px-4 py-3.5 text-center">
                                <input
                                    type="checkbox"
                                    :checked="isSelected(item.id)"
                                    @change="toggleSelect(item.id)"
                                    class="h-4 w-4 cursor-pointer rounded border-slate-300 text-amber-600 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800"
                                />
                            </td>

                            <!-- Title & Preview Link -->
                            <td class="max-w-xs px-4 py-3.5 sm:max-w-sm">
                                <div class="flex items-start gap-2.5">
                                    <div
                                        class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-amber-200/50 bg-amber-50 text-amber-600 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-400"
                                    >
                                        <FileImage
                                            v-if="
                                                item.resource_type === 'image'
                                            "
                                            class="h-4 w-4"
                                        />
                                        <FileVideo
                                            v-else-if="
                                                item.resource_type === 'video'
                                            "
                                            class="h-4 w-4"
                                        />
                                        <FileArchive
                                            v-else-if="
                                                item.resource_type === 'pdf'
                                            "
                                            class="h-4 w-4"
                                        />
                                        <Book
                                            v-else-if="
                                                item.resource_type === 'note'
                                            "
                                            class="h-4 w-4"
                                        />
                                        <File v-else class="h-4 w-4" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5">
                                            <a
                                                :href="`/resources/${item.id}`"
                                                target="_blank"
                                                class="font-semibold text-slate-900 hover:text-amber-600 dark:text-gray-100 dark:hover:text-amber-400"
                                            >
                                                {{ item.title }}
                                            </a>
                                            <ExternalLink
                                                class="h-3 w-3 text-slate-400"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Author -->
                            <td class="px-4 py-3.5">
                                <div
                                    v-if="item.user"
                                    class="flex items-center gap-2"
                                >
                                    <img
                                        v-if="item.user.image_path"
                                        :src="`/storage/${item.user.image_path}`"
                                        :alt="item.user.name"
                                        class="h-6 w-6 rounded-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-200 text-[10px] font-bold text-slate-600 dark:bg-gray-800 dark:text-gray-300"
                                    >
                                        {{ item.user.name.charAt(0) }}
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <span
                                            class="text-xs font-medium text-slate-800 dark:text-gray-200"
                                        >
                                            {{ item.user.name }}
                                        </span>
                                        <VerifiedBadge
                                            v-if="item.user.is_verified"
                                        />
                                    </div>
                                </div>
                                <span v-else class="text-xs text-slate-400"
                                    >Anonymous</span
                                >
                            </td>

                            <!-- Topic / Node -->
                            <td class="px-4 py-3.5">
                                <div
                                    class="text-xs text-slate-700 dark:text-gray-300"
                                >
                                    <div
                                        class="font-medium text-slate-900 dark:text-gray-100"
                                    >
                                        {{
                                            item.node?.subject?.name ||
                                            'Subject'
                                        }}
                                    </div>
                                    <div
                                        class="text-slate-500 dark:text-gray-400"
                                    >
                                        {{ item.node?.name || 'Folder' }}
                                    </div>
                                </div>
                            </td>

                            <!-- Resource Type -->
                            <td class="px-4 py-3.5">
                                <span
                                    class="inline-flex rounded bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600 uppercase dark:bg-gray-800 dark:text-gray-300"
                                >
                                    {{ item.resource_type }}
                                </span>
                            </td>

                            <!-- Date -->
                            <td
                                class="px-4 py-3.5 text-xs text-slate-500 dark:text-gray-400"
                            >
                                {{
                                    formatDate(
                                        item.updated_at || item.created_at,
                                    )
                                }}
                            </td>

                            <!-- Actions -->
                            <td class="px-4 py-3.5 text-right">
                                <div
                                    class="flex items-center justify-end gap-1.5"
                                >
                                    <button
                                        type="button"
                                        :disabled="isProcessing"
                                        @click="approveSingle(item)"
                                        class="inline-flex cursor-pointer items-center gap-1 rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-600/20 transition hover:bg-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-400 dark:hover:bg-emerald-500/20"
                                    >
                                        <Check class="h-3.5 w-3.5" />
                                        Approve
                                    </button>

                                    <button
                                        type="button"
                                        :disabled="isProcessing"
                                        @click="openRejectModal(item)"
                                        class="inline-flex cursor-pointer items-center gap-1 rounded-lg bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700 ring-1 ring-red-600/20 transition hover:bg-red-100 dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/20"
                                    >
                                        <X class="h-3.5 w-3.5" />
                                        Reject
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="resources.links && resources.links.length > 3"
                class="border-t border-slate-100 p-4 dark:border-gray-800"
            >
                <Pagination
                    :links="resources.links"
                    :from="resources.from"
                    :to="resources.to"
                    :total="resources.total"
                    :current-page="resources.current_page"
                    :last-page="resources.last_page"
                    show-summary
                />
            </div>
        </div>

        <!-- Empty State -->
        <EmptyState
            v-else
            title="All caught up!"
            description="There are currently no pending resources awaiting review."
        />

        <!-- Reject Modal -->
        <div
            v-if="rejectModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs"
        >
            <div
                class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-gray-800 dark:bg-gray-900"
            >
                <div
                    class="flex items-center gap-3 text-red-600 dark:text-red-400"
                >
                    <AlertCircle class="h-6 w-6 shrink-0" />
                    <h3
                        class="text-lg font-bold text-slate-900 dark:text-gray-100"
                    >
                        Reject Resource
                    </h3>
                </div>

                <p class="mt-2 text-sm text-slate-600 dark:text-gray-300">
                    This resource will be marked as rejected and will remain
                    hidden from students.
                </p>

                <div class="mt-4">
                    <label
                        class="block text-xs font-semibold text-slate-700 dark:text-gray-300"
                    >
                        Reason for rejection (optional feedback for author):
                    </label>
                    <textarea
                        v-model="rejectionReason"
                        rows="3"
                        placeholder="e.g. Blurry photo, duplicate note, or guidelines violation..."
                        class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-900 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-100"
                    ></textarea>
                </div>

                <div class="mt-6 flex justify-end gap-2.5">
                    <button
                        type="button"
                        @click="closeRejectModal"
                        class="cursor-pointer rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        :disabled="isProcessing"
                        @click="confirmReject"
                        class="cursor-pointer rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 disabled:opacity-50"
                    >
                        Confirm Rejection
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
