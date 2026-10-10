<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Check,
    Clock,
    ExternalLink,
    Eye,
    FileImage,
    FileText,
    FileVideo,
    Loader2,
    Pencil,
    PlusCircle,
    Trash2,
    X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import BaseModal from '@/components/BaseModal.vue';
import EmptyState from '@/components/EmptyState.vue';
import { formatDateTime, formatTimeAgo } from '@/lib/useDate';
import { usePermissions } from '@/lib/usePermissions';

interface User {
    id: number;
    name: string;
    username: string;
    image_path?: string;
}

interface Subject {
    id: number;
    name: string;
    slug: string;
}

interface NodeBreadcrumb {
    name: string;
    slug: string;
}

interface Node {
    id: number;
    name: string;
    slug?: string;
    subject?: Subject;
    breadcrumb?: NodeBreadcrumb[];
}

interface LiveResource {
    id: number;
    title: string;
    resource_type: string;
    content?: string;
    external_url?: string;
    file_path?: string;
    file_url?: string;
}

interface ChangeRequest {
    id: number;
    user_id: number;
    resource_id?: number;
    node_id: number;
    action_type: 'create' | 'update' | 'delete';
    status: 'pending' | 'approved' | 'rejected';
    payload?: {
        title?: string;
        resource_type?: string;
        content?: string;
        external_url?: string;
        file_path?: string;
    };
    staged_file_url?: string;
    rejection_reason?: string;
    reviewed_at?: string;
    created_at: string;
    user?: User;
    reviewer?: User;
    node?: Node;
    resource?: LiveResource;
}

const props = defineProps<{
    requests: {
        data: ChangeRequest[];
        next_page_url?: string | null;
        prev_page_url?: string | null;
        current_page: number;
        per_page?: number;
    };
    counts: {
        pending: number;
        approved: number;
        rejected: number;
    };
    filters: {
        status: string;
    };
}>();

const page = usePage();
const { can } = usePermissions();
const loadedRequests = ref<ChangeRequest[]>([...(props.requests?.data || [])]);
const nextPageUrl = ref<string | null>(props.requests?.next_page_url || null);
const isLoadingMore = ref(false);

const viewingRequest = ref<ChangeRequest | null>(null);
const rejectingRequest = ref<ChangeRequest | null>(null);
const rejectionReason = ref('');
const isProcessing = ref(false);
const processingId = ref<number | null>(null);
const selectedIds = ref<number[]>([]);

watch(
    () => props.filters.status,
    () => {
        loadedRequests.value = [...(props.requests?.data || [])];
        nextPageUrl.value = props.requests?.next_page_url || null;
        selectedIds.value = [];
    },
);

watch(
    () => props.requests,
    (newReqs) => {
        if (!newReqs) {
            return;
        }

        if (loadedRequests.value.length === 0) {
            loadedRequests.value = [...(newReqs.data || [])];
            nextPageUrl.value = newReqs.next_page_url || null;
        }
    },
    { deep: true },
);

const pendingRequests = computed(() =>
    loadedRequests.value.filter((r) => r.status === 'pending'),
);

const allPendingSelected = computed(
    () =>
        pendingRequests.value.length > 0 &&
        pendingRequests.value.every((r) => selectedIds.value.includes(r.id)),
);

const isSomePendingSelected = computed(
    () => selectedIds.value.length > 0 && !allPendingSelected.value,
);

const toggleSelectAllPending = () => {
    if (allPendingSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = pendingRequests.value.map((r) => r.id);
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

const clearSelection = () => {
    selectedIds.value = [];
};

const setStatusFilter = (status: string) => {
    selectedIds.value = [];
    router.get(
        '/admin/moderation/resources',
        { status },
        { preserveState: false, preserveScroll: true },
    );
};

interface ChangedField {
    key: string;
    label: string;
    oldVal: any;
    newVal: any;
    isMedia?: boolean;
}

const getChangedFields = (req: ChangeRequest): ChangedField[] => {
    if (req.action_type !== 'update' || !req.resource || !req.payload) {
        return [];
    }

    const changes: ChangedField[] = [];
    const live = req.resource;
    const proposed = req.payload;

    if (proposed.title !== undefined && proposed.title !== live.title) {
        changes.push({
            key: 'title',
            label: 'Title',
            oldVal: live.title,
            newVal: proposed.title,
        });
    }

    if (
        proposed.resource_type !== undefined &&
        proposed.resource_type !== live.resource_type
    ) {
        changes.push({
            key: 'resource_type',
            label: 'Resource Type',
            oldVal: live.resource_type,
            newVal: proposed.resource_type,
        });
    }

    // Description / Content
    const liveContent = (live.content || '').trim();
    const proposedContent = (proposed.content || '').trim();

    if (proposed.content !== undefined && liveContent !== proposedContent) {
        changes.push({
            key: 'content',
            label: 'Description / Content',
            oldVal: liveContent || '(empty)',
            newVal: proposedContent || '(empty)',
        });
    }

    // External URL
    const liveUrl = (live.external_url || '').trim();
    const proposedUrl = (proposed.external_url || '').trim();

    if (proposed.external_url !== undefined && liveUrl !== proposedUrl) {
        changes.push({
            key: 'external_url',
            label: 'External URL',
            oldVal: liveUrl || '(none)',
            newVal: proposedUrl || '(none)',
        });
    }

    if (proposed.file_path && proposed.file_path !== (live.file_path || '')) {
        changes.push({
            key: 'file',
            label: 'Uploaded File',
            oldVal:
                live.file_url ||
                (live.file_path ? 'Existing file on storage' : null),
            newVal: req.staged_file_url || proposed.file_path,
            isMedia: true,
        });
    }

    return changes;
};

interface BreadcrumbItem {
    name: string;
    url: string;
}

const getNodeBreadcrumbs = (node?: Node): BreadcrumbItem[] => {
    if (!node) {
        return [];
    }

    const items: BreadcrumbItem[] = [];

    if (node.subject) {
        items.push({
            name: node.subject.name,
            url: `/${node.subject.slug}`,
        });
    }

    if (node.breadcrumb && node.breadcrumb.length > 0) {
        let currentPath = node.subject ? `/${node.subject.slug}` : '';

        for (const crumb of node.breadcrumb) {
            currentPath += `/${crumb.slug}`;
            items.push({
                name: crumb.name,
                url: currentPath,
            });
        }
    } else if (node.name) {
        const url = node.subject
            ? `/${node.subject.slug}/${node.slug || ''}`
            : '#';
        items.push({
            name: node.name,
            url,
        });
    }

    return items;
};

const handleApprove = (req: ChangeRequest) => {
    const actionLabel =
        req.action_type === 'create'
            ? 'new upload'
            : req.action_type === 'update'
              ? 'edited resource'
              : 'resource deletion';

    if (confirm(`Approve this ${actionLabel}?`)) {
        isProcessing.value = true;
        processingId.value = req.id;
        router.post(
            '/admin/moderation/resources/approve',
            { ids: [req.id] },
            {
                preserveScroll: true,
                onSuccess: () => {
                    if (props.filters.status === 'pending') {
                        loadedRequests.value = loadedRequests.value.filter(
                            (r) => r.id !== req.id,
                        );
                    }

                    selectedIds.value = selectedIds.value.filter(
                        (id) => id !== req.id,
                    );

                    if (viewingRequest.value?.id === req.id) {
                        viewingRequest.value = null;
                    }
                },
                onFinish: () => {
                    isProcessing.value = false;
                    processingId.value = null;
                },
            },
        );
    }
};

const handleBulkApprove = () => {
    if (selectedIds.value.length === 0) {
        return;
    }

    const count = selectedIds.value.length;

    if (confirm(`Approve ${count} selected request${count > 1 ? 's' : ''}?`)) {
        isProcessing.value = true;
        const toApprove = [...selectedIds.value];
        router.post(
            '/admin/moderation/resources/approve',
            { ids: toApprove },
            {
                preserveScroll: true,
                onSuccess: () => {
                    const approvedSet = new Set(toApprove);

                    if (props.filters.status === 'pending') {
                        loadedRequests.value = loadedRequests.value.filter(
                            (r) => !approvedSet.has(r.id),
                        );
                    }

                    if (
                        viewingRequest.value &&
                        approvedSet.has(viewingRequest.value.id)
                    ) {
                        viewingRequest.value = null;
                    }

                    selectedIds.value = [];
                },
                onFinish: () => {
                    isProcessing.value = false;
                },
            },
        );
    }
};

const loadMore = async () => {
    if (!nextPageUrl.value || isLoadingMore.value) {
        return;
    }

    isLoadingMore.value = true;

    try {
        const headers: Record<string, string> = {
            'X-Inertia': 'true',
            'X-Inertia-Partial-Component': 'admin/moderation/Resources',
            'X-Inertia-Partial-Data': 'requests',
            'X-Requested-With': 'XMLHttpRequest',
        };

        if (page.version) {
            headers['X-Inertia-Version'] = String(page.version);
        }

        const res = await fetch(nextPageUrl.value, { headers });

        if (res.status === 409) {
            const location =
                res.headers.get('X-Inertia-Location') || nextPageUrl.value;
            window.location.href = location;

            return;
        }

        if (res.ok) {
            const data = await res.json();
            const newRequests = data?.props?.requests?.data || [];
            const existingIds = new Set(loadedRequests.value.map((r) => r.id));
            const uniqueNew = newRequests.filter(
                (r: ChangeRequest) => !existingIds.has(r.id),
            );
            loadedRequests.value = [...loadedRequests.value, ...uniqueNew];
            nextPageUrl.value = data?.props?.requests?.next_page_url || null;
        }
    } catch (e) {
        console.error('Failed to load more requests:', e);
    } finally {
        isLoadingMore.value = false;
    }
};

const isBulkReject = ref(false);

const openRejectModal = (req: ChangeRequest) => {
    isBulkReject.value = false;
    rejectingRequest.value = req;
    rejectionReason.value = '';
};

const openBulkRejectModal = () => {
    if (selectedIds.value.length === 0) {
        return;
    }

    isBulkReject.value = true;
    rejectingRequest.value = null;
    rejectionReason.value = '';
};

const closeRejectModal = () => {
    isBulkReject.value = false;
    rejectingRequest.value = null;
    rejectionReason.value = '';
};

const handleReject = () => {
    if (isBulkReject.value) {
        if (selectedIds.value.length === 0) {
            return;
        }

        const toReject = [...selectedIds.value];
        isProcessing.value = true;
        processingId.value = null;

        router.post(
            '/admin/moderation/resources/reject',
            {
                ids: toReject,
                rejection_reason: rejectionReason.value || null,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    const rejectedSet = new Set(toReject);

                    if (props.filters.status === 'pending') {
                        loadedRequests.value = loadedRequests.value.filter(
                            (r) => !rejectedSet.has(r.id),
                        );
                    }

                    if (
                        viewingRequest.value &&
                        rejectedSet.has(viewingRequest.value.id)
                    ) {
                        viewingRequest.value = null;
                    }

                    selectedIds.value = [];
                    closeRejectModal();
                },
                onFinish: () => {
                    isProcessing.value = false;
                },
            },
        );

        return;
    }

    if (!rejectingRequest.value) {
        return;
    }

    const reqId = rejectingRequest.value.id;
    isProcessing.value = true;
    processingId.value = reqId;

    router.post(
        '/admin/moderation/resources/reject',
        {
            ids: [reqId],
            rejection_reason: rejectionReason.value || null,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                if (props.filters.status === 'pending') {
                    loadedRequests.value = loadedRequests.value.filter(
                        (r) => r.id !== reqId,
                    );
                }

                selectedIds.value = selectedIds.value.filter(
                    (id) => id !== reqId,
                );
                closeRejectModal();

                if (viewingRequest.value?.id === reqId) {
                    viewingRequest.value = null;
                }
            },
            onFinish: () => {
                isProcessing.value = false;
                processingId.value = null;
            },
        },
    );
};
</script>

<template>
    <Head title="Resource Moderation - Admin" />

    <div class="space-y-5">
        <!-- Top Header -->
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1
                    class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl dark:text-gray-100"
                >
                    Resource Moderation Queue
                </h1>
                <p class="text-xs text-slate-500 dark:text-gray-400">
                    Review and approve community resource uploads, edits, and
                    author deletion requests.
                </p>
            </div>
        </div>

        <!-- Integrated Status Tabs -->
        <div
            class="flex flex-wrap items-center gap-1.5 rounded-2xl border border-slate-200/90 bg-white p-3 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
        >
            <button
                type="button"
                @click="setStatusFilter('pending')"
                class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold transition"
                :class="[
                    filters.status === 'pending'
                        ? 'bg-amber-500 text-white shadow-xs'
                        : 'bg-amber-50 text-amber-800 hover:bg-amber-100 dark:bg-amber-950/40 dark:text-amber-300',
                ]"
            >
                <Clock class="h-3.5 w-3.5" />
                <span>Pending</span>
                <span
                    class="py-0.2 rounded-full px-1.5 text-[10px] font-bold"
                    :class="[
                        filters.status === 'pending'
                            ? 'bg-amber-600 text-white'
                            : 'bg-amber-200/80 text-amber-900 dark:bg-amber-900/60 dark:text-amber-200',
                    ]"
                >
                    {{ counts.pending }}
                </span>
            </button>

            <button
                type="button"
                @click="setStatusFilter('approved')"
                class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold transition"
                :class="[
                    filters.status === 'approved'
                        ? 'bg-emerald-600 text-white shadow-xs'
                        : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300',
                ]"
            >
                <Check class="h-3.5 w-3.5" />
                <span>Approved</span>
                <span
                    class="py-0.2 rounded-full px-1.5 text-[10px] font-bold"
                    :class="[
                        filters.status === 'approved'
                            ? 'bg-emerald-700 text-white'
                            : 'bg-emerald-200/80 text-emerald-900 dark:bg-emerald-900/60 dark:text-emerald-200',
                    ]"
                >
                    {{ counts.approved }}
                </span>
            </button>

            <button
                type="button"
                @click="setStatusFilter('rejected')"
                class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold transition"
                :class="[
                    filters.status === 'rejected'
                        ? 'bg-rose-600 text-white shadow-xs'
                        : 'bg-rose-50 text-rose-800 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300',
                ]"
            >
                <X class="h-3.5 w-3.5" />
                <span>Rejected</span>
                <span
                    class="py-0.2 rounded-full px-1.5 text-[10px] font-bold"
                    :class="[
                        filters.status === 'rejected'
                            ? 'bg-rose-700 text-white'
                            : 'bg-rose-200/80 text-rose-900 dark:bg-rose-900/60 dark:text-rose-200',
                    ]"
                >
                    {{ counts.rejected }}
                </span>
            </button>
        </div>

        <!-- Requests Feed (Clean, Scannable Rows) -->
        <div class="space-y-4">
            <EmptyState
                v-if="loadedRequests.length === 0"
                :icon="Clock"
                variant="dashed"
                :title="`No ${filters.status} change requests`"
                :description="`There are no ${filters.status} resource requests matching the selected filter.`"
            />

            <template v-else>
                <!-- Retention info banner for Approved / Rejected history -->
                <div
                    v-if="
                        filters.status !== 'pending' &&
                        loadedRequests.length > 0
                    "
                    class="flex items-center gap-2 rounded-xl border border-slate-200/70 bg-slate-50/70 px-3.5 py-2 text-xs text-slate-500 dark:border-gray-800 dark:bg-gray-800/40 dark:text-gray-400"
                >
                    <Clock class="h-3.5 w-3.5 shrink-0 text-slate-400" />
                    <span>
                        {{
                            filters.status === 'approved'
                                ? 'Approved'
                                : 'Rejected'
                        }}
                        records are kept for 30 days before being automatically
                        deleted.
                    </span>
                </div>

                <!-- Bulk Action Toolbar (Pending items) -->
                <div
                    v-if="
                        filters.status === 'pending' &&
                        pendingRequests.length > 0
                    "
                    class="flex h-11 min-h-[44px] items-center justify-between gap-3 rounded-xl border border-slate-200/80 bg-white px-3.5 text-xs text-slate-600 shadow-2xs dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                >
                    <div class="flex items-center gap-2.5">
                        <input
                            type="checkbox"
                            id="select-all-pending"
                            :checked="allPendingSelected"
                            :indeterminate="isSomePendingSelected"
                            @change="toggleSelectAllPending"
                            class="h-4 w-4 cursor-pointer rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 dark:border-gray-700 dark:bg-gray-800"
                        />
                        <label
                            for="select-all-pending"
                            class="cursor-pointer font-medium text-slate-700 select-none dark:text-gray-300"
                        >
                            <span v-if="selectedIds.length > 0">
                                {{ selectedIds.length }} of
                                {{ pendingRequests.length }} selected
                            </span>
                            <span v-else>
                                Select all ({{ pendingRequests.length }} loaded)
                            </span>
                        </label>
                    </div>

                    <div
                        v-if="selectedIds.length > 0"
                        class="flex items-center gap-2"
                    >
                        <button
                            type="button"
                            @click="clearSelection"
                            class="cursor-pointer rounded-lg px-2 py-1 text-xs text-slate-500 hover:bg-slate-100 hover:text-slate-800 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
                        >
                            Clear selection
                        </button>

                        <button
                            type="button"
                            @click="openBulkRejectModal"
                            :disabled="isProcessing"
                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 shadow-xs transition hover:bg-rose-100 disabled:opacity-50 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300 dark:hover:bg-rose-950/70"
                        >
                            <X class="h-3.5 w-3.5" />
                            <span>Reject Selected</span>
                        </button>

                        <button
                            type="button"
                            @click="handleBulkApprove"
                            :disabled="isProcessing"
                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs transition hover:bg-emerald-500 disabled:opacity-50"
                        >
                            <Loader2
                                v-if="isProcessing && processingId === null"
                                class="h-3.5 w-3.5 animate-spin"
                            />
                            <Check v-else class="h-3.5 w-3.5" />
                            <span>Approve Selected</span>
                        </button>
                    </div>
                </div>

                <div class="space-y-2.5">
                    <div
                        v-for="req in loadedRequests"
                        :key="req.id"
                        class="flex flex-col gap-3 rounded-xl border bg-white p-3.5 transition-colors sm:flex-row sm:items-center sm:justify-between dark:bg-gray-900"
                        :class="[
                            req.status === 'pending'
                                ? selectedIds.includes(req.id)
                                    ? 'border-emerald-400 bg-emerald-50/30 shadow-2xs dark:border-emerald-700 dark:bg-emerald-950/20'
                                    : 'border-slate-200/90 shadow-2xs hover:border-slate-300 dark:border-gray-800 dark:hover:border-gray-700'
                                : 'border-slate-200/60 bg-slate-50/40 opacity-90 dark:border-gray-800 dark:bg-gray-900/40',
                        ]"
                    >
                        <!-- Left: Checkbox + Badges, Title, Node, Submitter -->
                        <div
                            class="flex min-w-0 flex-1 items-start gap-3 sm:items-center"
                        >
                            <div
                                v-if="req.status === 'pending'"
                                class="shrink-0 pt-0.5 sm:pt-0"
                            >
                                <input
                                    type="checkbox"
                                    :checked="selectedIds.includes(req.id)"
                                    @change="toggleSelect(req.id)"
                                    class="h-4 w-4 cursor-pointer rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 dark:border-gray-700 dark:bg-gray-800"
                                    :aria-label="`Select request ${req.id}`"
                                />
                            </div>

                            <div class="min-w-0 flex-1 space-y-1.5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <!-- Action Badge -->
                                    <span
                                        v-if="req.action_type === 'create'"
                                        class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300"
                                    >
                                        <PlusCircle class="h-3 w-3" />
                                        New Upload
                                    </span>
                                    <span
                                        v-else-if="req.action_type === 'update'"
                                        class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-700 dark:bg-amber-950/70 dark:text-amber-300"
                                    >
                                        <Pencil class="h-3 w-3" />
                                        Proposed Edit
                                    </span>
                                    <span
                                        v-else-if="req.action_type === 'delete'"
                                        class="inline-flex items-center gap-1 rounded-md bg-rose-50 px-2 py-0.5 text-[11px] font-bold text-rose-700 dark:bg-rose-950/70 dark:text-rose-300"
                                    >
                                        <Trash2 class="h-3 w-3" />
                                        Deletion Request
                                    </span>

                                    <!-- Resource Type Badge -->
                                    <span
                                        class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-700 uppercase dark:bg-gray-800 dark:text-gray-300"
                                    >
                                        <FileImage
                                            v-if="
                                                (req.payload?.resource_type ||
                                                    req.resource
                                                        ?.resource_type) ===
                                                'image'
                                            "
                                            class="h-3 w-3 text-amber-500"
                                        />
                                        <FileVideo
                                            v-else-if="
                                                (req.payload?.resource_type ||
                                                    req.resource
                                                        ?.resource_type) ===
                                                'video'
                                            "
                                            class="h-3 w-3 text-rose-500"
                                        />
                                        <FileText
                                            v-else
                                            class="h-3 w-3 text-indigo-500"
                                        />
                                        <span>{{
                                            req.payload?.resource_type ||
                                            req.resource?.resource_type ||
                                            'note'
                                        }}</span>
                                    </span>

                                    <!-- Changed Fields count (for edits) -->
                                    <span
                                        v-if="
                                            req.action_type === 'update' &&
                                            getChangedFields(req).length > 0
                                        "
                                        class="py-0.2 rounded bg-amber-100/80 px-1.5 text-[10px] font-bold text-amber-800 dark:bg-amber-900/40 dark:text-amber-300"
                                    >
                                        {{ getChangedFields(req).length }}
                                        changed
                                    </span>

                                    <!-- Folder Location -->
                                    <div
                                        v-if="
                                            getNodeBreadcrumbs(req.node)
                                                .length > 0
                                        "
                                        class="flex flex-wrap items-center gap-1 text-xs text-slate-500 dark:text-gray-400"
                                    >
                                        <span class="shrink-0 text-slate-400"
                                            >in</span
                                        >
                                        <template
                                            v-for="(
                                                crumb, cIdx
                                            ) in getNodeBreadcrumbs(req.node)"
                                            :key="cIdx"
                                        >
                                            <span
                                                v-if="cIdx > 0"
                                                class="shrink-0 text-slate-400 dark:text-gray-600"
                                                >/</span
                                            >
                                            <a
                                                :href="crumb.url"
                                                target="_blank"
                                                class="font-medium text-slate-600 hover:text-indigo-600 hover:underline dark:text-gray-300 dark:hover:text-indigo-400"
                                                :title="crumb.name"
                                                @click.stop
                                            >
                                                {{ crumb.name }}
                                            </a>
                                        </template>
                                    </div>
                                </div>

                                <!-- Resource Title -->
                                <div>
                                    <h3
                                        class="truncate text-sm font-bold text-slate-900 sm:text-base dark:text-white"
                                    >
                                        {{
                                            req.payload?.title ||
                                            req.resource?.title ||
                                            '(Untitled)'
                                        }}
                                    </h3>
                                </div>

                                <!-- Submitter Info -->
                                <div
                                    class="flex flex-wrap items-center gap-1 text-xs text-slate-400 dark:text-gray-500"
                                >
                                    <Link
                                        v-if="can('edit users') && req.user?.id"
                                        :href="`/admin/users/edit/${req.user.id}`"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 rounded text-slate-400 transition-colors hover:text-indigo-600 dark:text-gray-500 dark:hover:text-indigo-400"
                                        title="Edit user in admin"
                                        @click.stop
                                    >
                                        <span>By</span>
                                        <Pencil class="h-2.5 w-2.5" />
                                    </Link>
                                    <span v-else>By</span>
                                    <span
                                        class="font-semibold text-slate-700 dark:text-gray-300"
                                    >
                                        {{ req.user?.name || 'User' }}
                                    </span>
                                    <Link
                                        v-if="req.user?.username"
                                        :href="`/u/${req.user.username}`"
                                        target="_blank"
                                        class="text-indigo-600 hover:underline dark:text-indigo-400"
                                    >
                                        @{{ req.user.username }}
                                    </Link>
                                    <span>&bull;</span>
                                    <span
                                        :title="formatDateTime(req.created_at)"
                                    >
                                        {{ formatTimeAgo(req.created_at) }}
                                    </span>

                                    <!-- Reviewed info if done -->
                                    <span
                                        v-if="req.status === 'approved'"
                                        class="ml-1 font-semibold text-emerald-600 dark:text-emerald-400"
                                    >
                                        &bull; Approved
                                    </span>
                                    <span
                                        v-else-if="req.status === 'rejected'"
                                        class="ml-1 font-semibold text-rose-600 dark:text-rose-400"
                                    >
                                        &bull; Rejected
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Right Actions: View Details / Diff, Quick Approve / Reject -->
                        <div
                            class="flex shrink-0 items-center gap-1.5 sm:self-center"
                        >
                            <!-- View Changes / Details button -->
                            <button
                                type="button"
                                @click="viewingRequest = req"
                                class="inline-flex cursor-pointer items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                <Eye class="h-3.5 w-3.5 text-slate-500" />
                                <span>{{
                                    req.action_type === 'update'
                                        ? 'View Diff'
                                        : 'Details'
                                }}</span>
                            </button>

                            <!-- Reject for Pending -->
                            <button
                                v-if="req.status === 'pending'"
                                type="button"
                                @click="openRejectModal(req)"
                                :disabled="isProcessing"
                                class="cursor-pointer rounded-lg border border-slate-200 p-1.5 text-slate-500 transition hover:bg-rose-50 hover:text-rose-600 disabled:opacity-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-rose-950/40 dark:hover:text-rose-400"
                                title="Reject request"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Load More Button -->
                <div v-if="nextPageUrl" class="flex justify-center pt-2 pb-4">
                    <button
                        type="button"
                        @click="loadMore"
                        :disabled="isLoadingMore"
                        class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-xs font-semibold text-slate-700 shadow-2xs transition hover:border-slate-300 hover:bg-slate-50 disabled:opacity-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
                    >
                        <Loader2
                            v-if="isLoadingMore"
                            class="h-3.5 w-3.5 animate-spin text-slate-500"
                        />
                        <span>{{
                            isLoadingMore
                                ? 'Loading more...'
                                : 'Load More Requests'
                        }}</span>
                    </button>
                </div>
            </template>
        </div>
    </div>

    <!-- 1. Dedicated Review / Diff Modal -->
    <BaseModal
        :is-open="viewingRequest !== null"
        :title="
            viewingRequest?.action_type === 'create'
                ? 'Review New Upload'
                : viewingRequest?.action_type === 'update'
                  ? 'Compare Proposed Changes'
                  : 'Review Deletion Request'
        "
        max-width="2xl"
        @close="viewingRequest = null"
    >
        <template #icon>
            <div
                v-if="viewingRequest?.action_type === 'create'"
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400"
            >
                <PlusCircle class="h-5 w-5" />
            </div>
            <div
                v-else-if="viewingRequest?.action_type === 'update'"
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400"
            >
                <Pencil class="h-5 w-5" />
            </div>
            <div
                v-else
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400"
            >
                <Trash2 class="h-5 w-5" />
            </div>
        </template>

        <div v-if="viewingRequest" class="space-y-4 p-4 sm:p-6">
            <!-- Modal Header Meta (Location, Submitter, Live Link) -->
            <div
                class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3 text-xs text-slate-500 dark:border-gray-800 dark:text-gray-400"
            >
                <div
                    v-if="getNodeBreadcrumbs(viewingRequest.node).length > 0"
                    class="flex flex-wrap items-center gap-1"
                >
                    <span>In:</span>
                    <template
                        v-for="(crumb, cIdx) in getNodeBreadcrumbs(
                            viewingRequest.node,
                        )"
                        :key="cIdx"
                    >
                        <span
                            v-if="cIdx > 0"
                            class="text-slate-400 dark:text-gray-600"
                            >/</span
                        >
                        <a
                            :href="crumb.url"
                            target="_blank"
                            class="font-semibold text-slate-700 hover:text-indigo-600 hover:underline dark:text-gray-300 dark:hover:text-indigo-400"
                            :title="crumb.name"
                        >
                            {{ crumb.name }}
                        </a>
                    </template>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex flex-wrap items-center gap-1">
                        <Link
                            v-if="can('edit users') && viewingRequest.user?.id"
                            :href="`/admin/users/edit/${viewingRequest.user.id}`"
                            target="_blank"
                            class="inline-flex items-center gap-1 rounded text-slate-400 transition-colors hover:text-indigo-600 dark:text-gray-500 dark:hover:text-indigo-400"
                            title="Edit user in admin"
                        >
                            <span>By</span>
                            <Pencil class="h-2.5 w-2.5" />
                        </Link>
                        <span v-else>By</span>
                        <span
                            class="font-semibold text-slate-700 dark:text-gray-300"
                        >
                            {{ viewingRequest.user?.name }}
                        </span>
                        <Link
                            v-if="viewingRequest.user?.username"
                            :href="`/u/${viewingRequest.user.username}`"
                            target="_blank"
                            class="text-indigo-600 hover:underline dark:text-indigo-400"
                        >
                            (@{{ viewingRequest.user.username }})
                        </Link>
                        • {{ formatDateTime(viewingRequest.created_at) }}
                    </div>

                    <!-- Direct link to view live resource page (for edit or delete) -->
                    <a
                        v-if="viewingRequest.resource_id"
                        :href="`/resources/${viewingRequest.resource_id}`"
                        target="_blank"
                        class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs font-semibold text-indigo-600 shadow-2xs hover:bg-slate-50 hover:underline dark:border-gray-700 dark:bg-gray-800 dark:text-indigo-400 dark:hover:bg-gray-700"
                        title="Open live resource page in new tab"
                    >
                        <ExternalLink class="h-3 w-3" />
                        <span>View Live Page</span>
                    </a>
                </div>
            </div>

            <!-- MODAL CONTENT: CREATE -->
            <div
                v-if="viewingRequest.action_type === 'create'"
                class="space-y-3"
            >
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div
                        class="rounded-xl border border-slate-200 bg-slate-50/60 p-3 dark:border-gray-800 dark:bg-gray-800/50"
                    >
                        <span
                            class="text-[10px] font-bold tracking-wider text-slate-400 uppercase"
                        >
                            Title
                        </span>
                        <div
                            class="mt-1 text-sm font-semibold text-slate-900 dark:text-white"
                        >
                            {{ viewingRequest.payload?.title || '(untitled)' }}
                        </div>
                    </div>

                    <div
                        class="rounded-xl border border-slate-200 bg-slate-50/60 p-3 dark:border-gray-800 dark:bg-gray-800/50"
                    >
                        <span
                            class="text-[10px] font-bold tracking-wider text-slate-400 uppercase"
                        >
                            Resource Type
                        </span>
                        <div
                            class="mt-1 flex items-center gap-1.5 font-semibold text-slate-800 uppercase dark:text-gray-200"
                        >
                            <FileImage
                                v-if="
                                    viewingRequest.payload?.resource_type ===
                                    'image'
                                "
                                class="h-4 w-4 text-amber-500"
                            />
                            <FileVideo
                                v-else-if="
                                    viewingRequest.payload?.resource_type ===
                                    'video'
                                "
                                class="h-4 w-4 text-rose-500"
                            />
                            <FileText v-else class="h-4 w-4 text-indigo-500" />
                            <span>{{
                                viewingRequest.payload?.resource_type
                            }}</span>
                        </div>
                    </div>
                </div>

                <div
                    v-if="viewingRequest.payload?.content"
                    class="rounded-xl border border-slate-200 bg-slate-50/60 p-3 dark:border-gray-800 dark:bg-gray-800/50"
                >
                    <span
                        class="text-[10px] font-bold tracking-wider text-slate-400 uppercase"
                    >
                        Body / Description
                    </span>
                    <div
                        class="mt-1 text-xs leading-relaxed text-slate-700 dark:text-gray-200"
                    >
                        {{ viewingRequest.payload.content }}
                    </div>
                </div>

                <div
                    v-if="viewingRequest.payload?.external_url"
                    class="rounded-xl border border-slate-200 bg-slate-50/60 p-3 dark:border-gray-800 dark:bg-gray-800/50"
                >
                    <span
                        class="text-[10px] font-bold tracking-wider text-slate-400 uppercase"
                    >
                        External URL
                    </span>
                    <div class="mt-1">
                        <a
                            :href="viewingRequest.payload.external_url"
                            target="_blank"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                        >
                            <span>{{
                                viewingRequest.payload.external_url
                            }}</span>
                            <ExternalLink class="h-3 w-3" />
                        </a>
                    </div>
                </div>

                <div
                    v-if="viewingRequest.staged_file_url"
                    class="rounded-xl border border-slate-200 bg-slate-50/60 p-3 dark:border-gray-800 dark:bg-gray-800/50"
                >
                    <span
                        class="text-[10px] font-bold tracking-wider text-slate-400 uppercase"
                    >
                        Uploaded Local File
                    </span>
                    <div class="mt-2">
                        <img
                            v-if="
                                viewingRequest.payload?.resource_type ===
                                'image'
                            "
                            :src="viewingRequest.staged_file_url"
                            alt="Staged"
                            class="max-h-64 max-w-full rounded-xl border border-slate-200 object-contain dark:border-gray-700"
                        />
                        <a
                            v-else
                            :href="viewingRequest.staged_file_url"
                            target="_blank"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-emerald-600 hover:underline dark:border-gray-700 dark:bg-gray-800 dark:text-emerald-400"
                        >
                            <ExternalLink class="h-3.5 w-3.5" />
                            <span>Open Staged File</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- MODAL CONTENT: UPDATE (DIFF ONLY CHANGED FIELDS) -->
            <div
                v-else-if="viewingRequest.action_type === 'update'"
                class="space-y-3"
            >
                <div class="flex flex-wrap items-center gap-1.5">
                    <span
                        class="text-xs font-bold text-slate-500 dark:text-gray-400"
                    >
                        Changed fields:
                    </span>
                    <template
                        v-if="getChangedFields(viewingRequest).length > 0"
                    >
                        <span
                            v-for="change in getChangedFields(viewingRequest)"
                            :key="change.key"
                            class="rounded-md bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-800 dark:bg-amber-950/60 dark:text-amber-300"
                        >
                            {{ change.label }}
                        </span>
                    </template>
                    <span v-else class="text-xs text-slate-400 italic">
                        No textual changes detected
                    </span>
                </div>

                <div class="space-y-2.5">
                    <div
                        v-for="change in getChangedFields(viewingRequest)"
                        :key="`modal-diff-${change.key}`"
                        class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xs dark:border-gray-800 dark:bg-gray-900"
                    >
                        <div
                            class="border-b border-slate-100 bg-slate-50 px-3.5 py-1.5 text-[11px] font-bold text-slate-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-300"
                        >
                            {{ change.label }}
                        </div>

                        <div
                            class="grid grid-cols-1 divide-y divide-slate-100 sm:grid-cols-2 sm:divide-x sm:divide-y-0 dark:divide-gray-800"
                        >
                            <!-- Before -->
                            <div class="bg-slate-50/40 p-3 dark:bg-gray-900/30">
                                <div
                                    class="mb-1 text-[10px] font-bold tracking-wider text-slate-400 uppercase"
                                >
                                    Now
                                </div>
                                <div
                                    class="text-xs text-slate-600 dark:text-gray-400"
                                >
                                    <template v-if="change.key === 'file'">
                                        <div
                                            v-if="
                                                change.oldVal &&
                                                (viewingRequest.resource
                                                    ?.resource_type ===
                                                    'image' ||
                                                    String(change.oldVal).match(
                                                        /\.(jpeg|jpg|gif|png|webp|svg)$/i,
                                                    ))
                                            "
                                            class="space-y-1"
                                        >
                                            <img
                                                :src="change.oldVal"
                                                alt="Current file"
                                                class="max-h-48 max-w-full rounded-lg border border-slate-200 object-contain dark:border-gray-700"
                                            />
                                            <div
                                                class="text-[11px] text-slate-400 line-through decoration-rose-400/80"
                                            >
                                                Current image
                                            </div>
                                        </div>
                                        <a
                                            v-else-if="change.oldVal"
                                            :href="change.oldVal"
                                            target="_blank"
                                            class="inline-flex items-center gap-1 line-through decoration-rose-400/80 hover:underline"
                                        >
                                            <span class="truncate"
                                                >Existing file</span
                                            >
                                            <ExternalLink
                                                class="h-3 w-3 shrink-0"
                                            />
                                        </a>
                                        <span
                                            v-else
                                            class="text-slate-400 italic"
                                            >(no file)</span
                                        >
                                    </template>
                                    <template
                                        v-else-if="
                                            change.key === 'external_url' &&
                                            change.oldVal !== '(none)'
                                        "
                                    >
                                        <a
                                            :href="change.oldVal"
                                            target="_blank"
                                            class="inline-flex max-w-full items-center gap-1 line-through decoration-rose-400/80 hover:underline"
                                        >
                                            <span class="truncate">{{
                                                change.oldVal
                                            }}</span>
                                            <ExternalLink
                                                class="h-3 w-3 shrink-0"
                                            />
                                        </a>
                                    </template>
                                    <span
                                        v-else
                                        class="line-through decoration-rose-400/80"
                                    >
                                        {{ change.oldVal }}
                                    </span>
                                </div>
                            </div>

                            <!-- After -->
                            <div
                                class="bg-amber-50/20 p-3 dark:bg-amber-950/10"
                            >
                                <div
                                    class="mb-1 text-[10px] font-bold tracking-wider text-amber-600 uppercase dark:text-amber-400"
                                >
                                    After
                                </div>
                                <div
                                    class="text-xs font-semibold text-amber-900 dark:text-amber-200"
                                >
                                    <template v-if="change.key === 'file'">
                                        <div
                                            v-if="
                                                change.newVal &&
                                                (viewingRequest.payload
                                                    ?.resource_type ===
                                                    'image' ||
                                                    viewingRequest.resource
                                                        ?.resource_type ===
                                                        'image' ||
                                                    String(change.newVal).match(
                                                        /\.(jpeg|jpg|gif|png|webp|svg)$/i,
                                                    ))
                                            "
                                            class="space-y-1"
                                        >
                                            <img
                                                :src="change.newVal"
                                                alt="Proposed file"
                                                class="max-h-48 max-w-full rounded-lg border border-amber-200 object-contain dark:border-amber-800"
                                            />
                                            <div
                                                class="text-[11px] font-bold text-amber-600 dark:text-amber-400"
                                            >
                                                Proposed new image
                                            </div>
                                        </div>
                                        <a
                                            v-else-if="change.newVal"
                                            :href="change.newVal"
                                            target="_blank"
                                            class="inline-flex items-center gap-1 text-xs font-bold text-amber-700 hover:underline dark:text-amber-300"
                                        >
                                            <ExternalLink class="h-3 w-3" />
                                            <span>New uploaded file</span>
                                        </a>
                                        <span
                                            v-else
                                            class="text-slate-400 italic"
                                            >(no file)</span
                                        >
                                    </template>
                                    <template
                                        v-else-if="
                                            change.key === 'external_url' &&
                                            change.newVal !== '(none)'
                                        "
                                    >
                                        <a
                                            :href="change.newVal"
                                            target="_blank"
                                            class="inline-flex max-w-full items-center gap-1 text-indigo-600 hover:underline dark:text-indigo-400"
                                        >
                                            <span class="truncate">{{
                                                change.newVal
                                            }}</span>
                                            <ExternalLink
                                                class="h-3 w-3 shrink-0"
                                            />
                                        </a>
                                    </template>
                                    <template v-else>
                                        {{ change.newVal }}
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODAL CONTENT: DELETE -->
            <div
                v-else-if="viewingRequest.action_type === 'delete'"
                class="rounded-xl border border-rose-200 bg-rose-50/60 p-4 dark:border-rose-900/50 dark:bg-rose-950/20"
            >
                <div class="flex items-start gap-3">
                    <Trash2 class="mt-0.5 h-6 w-6 shrink-0 text-rose-500" />
                    <div>
                        <h4
                            class="text-base font-bold text-rose-950 dark:text-rose-200"
                        >
                            Target: "{{
                                viewingRequest.resource?.title || 'Resource'
                            }}"
                        </h4>
                        <p
                            class="mt-1 text-xs text-rose-700/90 dark:text-rose-300/80"
                        >
                            The author has requested complete and permanent
                            removal of this resource and all attached files.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Audit details if not pending -->
            <div
                v-if="viewingRequest.status !== 'pending'"
                class="rounded-xl bg-slate-50 p-3 text-xs dark:bg-gray-800/60"
            >
                <div
                    v-if="viewingRequest.status === 'approved'"
                    class="font-semibold text-emerald-600 dark:text-emerald-400"
                >
                    ✓ Approved by {{ viewingRequest.reviewer?.name || 'Admin' }}
                    <span v-if="viewingRequest.reviewed_at">
                        on {{ formatDateTime(viewingRequest.reviewed_at) }}
                    </span>
                </div>
                <div v-else class="space-y-1 text-rose-600 dark:text-rose-400">
                    <div class="font-semibold">
                        ✕ Rejected by
                        {{ viewingRequest.reviewer?.name || 'Admin' }}
                        <span v-if="viewingRequest.reviewed_at">
                            on {{ formatDateTime(viewingRequest.reviewed_at) }}
                        </span>
                    </div>
                    <div
                        v-if="viewingRequest.rejection_reason"
                        class="text-xs text-slate-600 dark:text-gray-300"
                    >
                        Reason: {{ viewingRequest.rejection_reason }}
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex items-center justify-between">
                <button
                    type="button"
                    @click="viewingRequest = null"
                    class="cursor-pointer rounded-xl px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800"
                >
                    Close
                </button>

                <div
                    v-if="viewingRequest?.status === 'pending'"
                    class="flex items-center gap-2"
                >
                    <button
                        type="button"
                        @click="openRejectModal(viewingRequest)"
                        :disabled="isProcessing"
                        class="cursor-pointer rounded-xl border border-slate-200 px-3.5 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 disabled:opacity-50 dark:border-gray-700 dark:hover:bg-rose-950/40"
                    >
                        Reject
                    </button>

                    <button
                        type="button"
                        @click="handleApprove(viewingRequest)"
                        :disabled="isProcessing"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-emerald-500 disabled:opacity-50"
                    >
                        <Loader2
                            v-if="
                                isProcessing &&
                                processingId === viewingRequest?.id
                            "
                            class="h-3.5 w-3.5 animate-spin"
                        />
                        <Check v-else class="h-3.5 w-3.5" />
                        <span>Approve</span>
                    </button>
                </div>
            </div>
        </template>
    </BaseModal>

    <!-- 2. Clean Rejection Feedback Modal -->
    <BaseModal
        :is-open="rejectingRequest !== null || isBulkReject"
        :title="
            isBulkReject
                ? `Reject ${selectedIds.length} Selected Requests`
                : 'Reject Change Request'
        "
        :description="
            isBulkReject
                ? `Provide optional feedback applied to all ${selectedIds.length} selected requests.`
                : 'Provide optional feedback explaining why this request was declined.'
        "
        max-width="md"
        @close="closeRejectModal"
    >
        <template #icon>
            <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400"
            >
                <X class="h-5 w-5" />
            </div>
        </template>

        <div class="space-y-3 p-4 sm:p-5">
            <div
                v-if="isBulkReject"
                class="rounded-xl border border-rose-200 bg-rose-50/60 p-3 text-xs text-rose-800 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300"
            >
                <span class="font-semibold">Batch Target:</span>
                Rejecting {{ selectedIds.length }} change request{{
                    selectedIds.length > 1 ? 's' : ''
                }}
                at once.
            </div>
            <div
                v-else-if="rejectingRequest"
                class="rounded-xl border border-slate-200 bg-slate-50/70 p-3 text-xs text-slate-700 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-300"
            >
                <span class="font-semibold text-slate-900 dark:text-white">
                    Target:
                </span>
                {{
                    rejectingRequest.payload?.title ||
                    rejectingRequest.resource?.title ||
                    'Resource'
                }}
                <span class="text-slate-400">
                    ({{ rejectingRequest.action_type }})
                </span>
            </div>

            <div>
                <label
                    class="block text-xs font-semibold text-slate-700 dark:text-gray-300"
                >
                    Rejection Feedback (Optional)
                </label>
                <textarea
                    v-model="rejectionReason"
                    rows="3"
                    placeholder="e.g. Please provide a higher-resolution document or update the video link..."
                    class="mt-1.5 w-full rounded-xl border border-slate-200 p-3 text-xs focus:border-rose-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                ></textarea>
            </div>
        </div>

        <template #footer>
            <div class="flex items-center justify-end gap-2">
                <button
                    type="button"
                    @click="closeRejectModal"
                    class="cursor-pointer rounded-xl px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    @click="handleReject"
                    :disabled="isProcessing"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-rose-500 disabled:opacity-50"
                >
                    <Loader2
                        v-if="isProcessing"
                        class="h-3.5 w-3.5 animate-spin"
                    />
                    <span>{{
                        isBulkReject
                            ? `Reject ${selectedIds.length} Requests`
                            : 'Confirm Rejection'
                    }}</span>
                </button>
            </div>
        </template>
    </BaseModal>
</template>
