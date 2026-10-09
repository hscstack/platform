<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Check,
    Clock,
    ExternalLink,
    FileImage,
    FileText,
    FileVideo,
    Pencil,
    PlusCircle,
    Trash2,
    X,
} from 'lucide-vue-next';
import { ref } from 'vue';
import Pagination from '@/components/Pagination.vue';

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

interface Node {
    id: number;
    name: string;
    subject?: Subject;
}

interface LiveResource {
    id: number;
    title: string;
    resource_type: string;
    content?: string;
    external_url?: string;
    file_path?: string;
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
        links: any[];
        from: number;
        to: number;
        total: number;
        current_page: number;
        last_page: number;
    };
    counts: {
        pending: number;
        approved: number;
        rejected: number;
    };
    filters: {
        status: string;
        action_type?: string;
    };
}>();

const rejectingId = ref<number | null>(null);
const rejectionReason = ref('');
const isProcessing = ref(false);

const setStatusFilter = (status: string) => {
    router.get(
        '/admin/moderation/resources',
        { status, action_type: props.filters.action_type },
        { preserveState: true, preserveScroll: true },
    );
};

const setActionFilter = (actionType?: string) => {
    router.get(
        '/admin/moderation/resources',
        { status: props.filters.status, action_type: actionType },
        { preserveState: true, preserveScroll: true },
    );
};

const handleApprove = (req: ChangeRequest) => {
    if (confirm(`Approve this ${req.action_type} request?`)) {
        isProcessing.value = true;
        router.post(
            `/admin/moderation/resources/${req.id}/approve`,
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    isProcessing.value = false;
                },
            },
        );
    }
};

const openRejectModal = (req: ChangeRequest) => {
    rejectingId.value = req.id;
    rejectionReason.value = '';
};

const handleReject = () => {
    if (!rejectingId.value) {
        return;
    }

    isProcessing.value = true;
    router.post(
        `/admin/moderation/resources/${rejectingId.value}/reject`,
        { rejection_reason: rejectionReason.value },
        {
            preserveScroll: true,
            onFinish: () => {
                isProcessing.value = false;
                rejectingId.value = null;
                rejectionReason.value = '';
            },
        },
    );
};
</script>

<template>
    <Head title="Resource Moderation Queue" />

    <div class="mx-auto max-w-6xl space-y-6 p-4 sm:p-6 lg:p-8">
        <!-- Header -->
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white"
                >
                    Resource Moderation Queue
                </h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                    Review, approve, or reject proposed resource uploads, edits,
                    and deletions.
                </p>
            </div>
        </div>

        <!-- Status Tabs -->
        <div
            class="flex flex-wrap items-center gap-2 border-b border-slate-200 pb-4 dark:border-gray-800"
        >
            <button
                type="button"
                @click="setStatusFilter('pending')"
                class="inline-flex cursor-pointer items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition"
                :class="[
                    filters.status === 'pending'
                        ? 'bg-amber-500/10 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300'
                        : 'text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800',
                ]"
            >
                <Clock class="h-4 w-4" />
                <span>Pending</span>
                <span
                    class="rounded-full bg-amber-500 px-2 py-0.5 text-xs text-white dark:bg-amber-600"
                >
                    {{ counts.pending }}
                </span>
            </button>

            <button
                type="button"
                @click="setStatusFilter('approved')"
                class="inline-flex cursor-pointer items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition"
                :class="[
                    filters.status === 'approved'
                        ? 'bg-emerald-500/10 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300'
                        : 'text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800',
                ]"
            >
                <Check class="h-4 w-4" />
                <span>Approved</span>
                <span
                    class="rounded-full bg-slate-200 px-2 py-0.5 text-xs text-slate-700 dark:bg-gray-700 dark:text-gray-300"
                >
                    {{ counts.approved }}
                </span>
            </button>

            <button
                type="button"
                @click="setStatusFilter('rejected')"
                class="inline-flex cursor-pointer items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition"
                :class="[
                    filters.status === 'rejected'
                        ? 'bg-rose-500/10 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300'
                        : 'text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800',
                ]"
            >
                <X class="h-4 w-4" />
                <span>Rejected</span>
                <span
                    class="rounded-full bg-slate-200 px-2 py-0.5 text-xs text-slate-700 dark:bg-gray-700 dark:text-gray-300"
                >
                    {{ counts.rejected }}
                </span>
            </button>

            <!-- Action Type Filter -->
            <div class="ml-auto flex items-center gap-1">
                <span class="text-xs font-medium text-slate-400"
                    >Filter action:</span
                >
                <button
                    type="button"
                    @click="setActionFilter(undefined)"
                    class="cursor-pointer rounded-lg px-2.5 py-1 text-xs font-semibold"
                    :class="
                        !filters.action_type
                            ? 'bg-slate-900 text-white dark:bg-white dark:text-gray-900'
                            : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-gray-800'
                    "
                >
                    All
                </button>
                <button
                    type="button"
                    @click="setActionFilter('create')"
                    class="cursor-pointer rounded-lg px-2.5 py-1 text-xs font-semibold"
                    :class="
                        filters.action_type === 'create'
                            ? 'bg-emerald-600 text-white'
                            : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-gray-800'
                    "
                >
                    Creates
                </button>
                <button
                    type="button"
                    @click="setActionFilter('update')"
                    class="cursor-pointer rounded-lg px-2.5 py-1 text-xs font-semibold"
                    :class="
                        filters.action_type === 'update'
                            ? 'bg-amber-600 text-white'
                            : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-gray-800'
                    "
                >
                    Updates
                </button>
                <button
                    type="button"
                    @click="setActionFilter('delete')"
                    class="cursor-pointer rounded-lg px-2.5 py-1 text-xs font-semibold"
                    :class="
                        filters.action_type === 'delete'
                            ? 'bg-rose-600 text-white'
                            : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-gray-800'
                    "
                >
                    Deletes
                </button>
            </div>
        </div>

        <!-- Queue List -->
        <div
            v-if="requests.data.length === 0"
            class="rounded-2xl border border-dashed border-slate-200 p-12 text-center dark:border-gray-800"
        >
            <Clock
                class="mx-auto h-12 w-12 text-slate-300 dark:text-gray-600"
            />
            <h3
                class="mt-4 text-base font-semibold text-slate-900 dark:text-white"
            >
                No requests found
            </h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                There are no {{ filters.status }} resource change requests
                matching the current filters.
            </p>
        </div>

        <div v-else class="space-y-4">
            <div
                v-for="req in requests.data"
                :key="req.id"
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs transition dark:border-gray-800 dark:bg-gray-900"
            >
                <!-- Top Bar: Action badge, Node breadcrumb, Author -->
                <div
                    class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3 dark:border-gray-800/80"
                >
                    <div class="flex items-center gap-2">
                        <!-- Action Type Badge -->
                        <span
                            v-if="req.action_type === 'create'"
                            class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400"
                        >
                            <PlusCircle class="h-3.5 w-3.5" />
                            New Upload
                        </span>
                        <span
                            v-else-if="req.action_type === 'update'"
                            class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700 dark:bg-amber-500/10 dark:text-amber-400"
                        >
                            <Pencil class="h-3.5 w-3.5" />
                            Proposed Edit
                        </span>
                        <span
                            v-else-if="req.action_type === 'delete'"
                            class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700 dark:bg-rose-500/10 dark:text-rose-400"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                            Deletion Request
                        </span>

                        <!-- Folder/Node context -->
                        <span class="text-xs text-slate-500 dark:text-gray-400">
                            in
                            <span
                                class="font-medium text-slate-700 dark:text-gray-300"
                                >{{ req.node?.subject?.name }} /
                                {{ req.node?.name }}</span
                            >
                        </span>
                    </div>

                    <!-- Requester info -->
                    <div
                        class="flex items-center gap-2 text-xs text-slate-500 dark:text-gray-400"
                    >
                        <span>Submitted by</span>
                        <span
                            class="font-semibold text-slate-800 dark:text-gray-200"
                        >
                            {{ req.user?.name }} (@{{ req.user?.username }})
                        </span>
                        <span>&bull;</span>
                        <span>{{
                            new Date(req.created_at).toLocaleString()
                        }}</span>
                    </div>
                </div>

                <!-- Content Details / Diffs -->
                <div class="py-4">
                    <!-- CREATE ACTION DETAILS -->
                    <div v-if="req.action_type === 'create'" class="space-y-3">
                        <div class="flex items-center gap-2">
                            <FileImage
                                v-if="req.payload?.resource_type === 'image'"
                                class="h-5 w-5 text-amber-500"
                            />
                            <FileVideo
                                v-else-if="
                                    req.payload?.resource_type === 'video'
                                "
                                class="h-5 w-5 text-rose-500"
                            />
                            <FileText v-else class="h-5 w-5 text-indigo-500" />
                            <h4
                                class="text-base font-bold text-slate-900 dark:text-white"
                            >
                                {{ req.payload?.title }}
                            </h4>
                            <span
                                class="rounded bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600 uppercase dark:bg-gray-800 dark:text-gray-300"
                            >
                                {{ req.payload?.resource_type }}
                            </span>
                        </div>

                        <p
                            v-if="req.payload?.content"
                            class="rounded-xl bg-slate-50 p-3 text-xs text-slate-700 dark:bg-gray-800/50 dark:text-gray-300"
                        >
                            {{ req.payload?.content }}
                        </p>

                        <div
                            v-if="req.payload?.external_url"
                            class="flex items-center gap-1.5 text-xs"
                        >
                            <span class="text-slate-400">External URL:</span>
                            <a
                                :href="req.payload.external_url"
                                target="_blank"
                                class="inline-flex items-center gap-1 text-indigo-600 hover:underline dark:text-indigo-400"
                            >
                                {{ req.payload.external_url }}
                                <ExternalLink class="h-3 w-3" />
                            </a>
                        </div>

                        <div v-if="req.staged_file_url" class="mt-2">
                            <img
                                v-if="req.payload?.resource_type === 'image'"
                                :src="req.staged_file_url"
                                class="max-h-48 rounded-xl border object-contain dark:border-gray-700"
                            />
                            <a
                                v-else
                                :href="req.staged_file_url"
                                target="_blank"
                                class="inline-flex items-center gap-1 text-xs text-indigo-600 hover:underline"
                            >
                                View Staged File
                                <ExternalLink class="h-3 w-3" />
                            </a>
                        </div>
                    </div>

                    <!-- UPDATE ACTION (SIDE-BY-SIDE DIFF) -->
                    <div
                        v-else-if="req.action_type === 'update'"
                        class="space-y-3"
                    >
                        <div
                            class="grid grid-cols-1 gap-4 rounded-xl bg-slate-50 p-4 sm:grid-cols-2 dark:bg-gray-800/40"
                        >
                            <!-- Current Live Content -->
                            <div
                                class="space-y-2 border-b pb-3 sm:border-r sm:border-b-0 sm:pr-4 dark:border-gray-800"
                            >
                                <div
                                    class="text-[11px] font-bold tracking-wider text-slate-400 uppercase"
                                >
                                    Current Live Version
                                </div>
                                <div
                                    class="text-sm font-semibold text-slate-800 dark:text-gray-200"
                                >
                                    {{ req.resource?.title }}
                                </div>
                                <div
                                    v-if="req.resource?.content"
                                    class="text-xs text-slate-600 dark:text-gray-400"
                                >
                                    {{ req.resource?.content }}
                                </div>
                                <div
                                    v-if="req.resource?.external_url"
                                    class="text-xs text-indigo-600"
                                >
                                    {{ req.resource?.external_url }}
                                </div>
                            </div>

                            <!-- Proposed New Content -->
                            <div class="space-y-2 sm:pl-2">
                                <div
                                    class="text-[11px] font-bold tracking-wider text-amber-600 uppercase dark:text-amber-400"
                                >
                                    Proposed Changes
                                </div>
                                <div
                                    class="text-sm font-semibold"
                                    :class="
                                        req.payload?.title !==
                                        req.resource?.title
                                            ? 'font-bold text-amber-600 dark:text-amber-300'
                                            : 'text-slate-800 dark:text-gray-200'
                                    "
                                >
                                    {{ req.payload?.title }}
                                </div>
                                <div
                                    v-if="req.payload?.content"
                                    class="text-xs"
                                    :class="
                                        req.payload?.content !==
                                        req.resource?.content
                                            ? 'text-amber-700 dark:text-amber-300'
                                            : 'text-slate-600 dark:text-gray-400'
                                    "
                                >
                                    {{ req.payload?.content }}
                                </div>
                                <div
                                    v-if="req.payload?.external_url"
                                    class="text-xs text-indigo-600"
                                >
                                    {{ req.payload?.external_url }}
                                </div>
                                <div v-if="req.staged_file_url" class="mt-1">
                                    <a
                                        :href="req.staged_file_url"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 text-xs text-amber-600 hover:underline"
                                    >
                                        New File Uploaded
                                        <ExternalLink class="h-3 w-3" />
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DELETE ACTION -->
                    <div
                        v-else-if="req.action_type === 'delete'"
                        class="flex items-center gap-3 rounded-xl bg-rose-50/50 p-4 dark:bg-rose-500/10"
                    >
                        <Trash2 class="h-6 w-6 text-rose-500" />
                        <div>
                            <h4
                                class="text-sm font-bold text-rose-900 dark:text-rose-200"
                            >
                                Target: "{{ req.resource?.title }}"
                            </h4>
                            <p
                                class="text-xs text-rose-700/80 dark:text-rose-300/80"
                            >
                                Author requested complete deletion of this
                                resource and its associated files.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Bottom Bar: Status information OR Approve/Reject Actions -->
                <div
                    class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3 dark:border-gray-800/80"
                >
                    <!-- Review Audit Info -->
                    <div
                        v-if="req.status !== 'pending'"
                        class="text-xs text-slate-500 dark:text-gray-400"
                    >
                        <span
                            v-if="req.status === 'approved'"
                            class="font-medium text-emerald-600 dark:text-emerald-400"
                        >
                            Approved by {{ req.reviewer?.name }} on
                            {{
                                req.reviewed_at
                                    ? new Date(
                                          req.reviewed_at,
                                      ).toLocaleDateString()
                                    : ''
                            }}
                        </span>
                        <span
                            v-else
                            class="font-medium text-rose-600 dark:text-rose-400"
                        >
                            Rejected by {{ req.reviewer?.name }}:
                            {{ req.rejection_reason || 'No reason specified' }}
                        </span>
                    </div>
                    <div v-else></div>

                    <!-- Approve & Reject Buttons for Pending items -->
                    <div
                        v-if="req.status === 'pending'"
                        class="flex items-center gap-2"
                    >
                        <button
                            type="button"
                            @click="openRejectModal(req)"
                            :disabled="isProcessing"
                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200 px-3.5 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-rose-50 hover:text-rose-600 disabled:opacity-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                        >
                            <X class="h-3.5 w-3.5" />
                            Reject
                        </button>

                        <button
                            type="button"
                            @click="handleApprove(req)"
                            :disabled="isProcessing"
                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-1.5 text-xs font-semibold text-white shadow-xs transition hover:bg-emerald-500 disabled:opacity-50"
                        >
                            <Check class="h-3.5 w-3.5" />
                            Approve
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <Pagination
            :links="requests.links"
            :from="requests.from"
            :to="requests.to"
            :total="requests.total"
            :current-page="requests.current_page"
            :last-page="requests.last_page"
        />
    </div>

    <!-- Rejection Modal -->
    <div
        v-if="rejectingId !== null"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs"
    >
        <div
            class="w-full max-w-md rounded-2xl border border-slate-100 bg-white p-6 shadow-2xl dark:border-gray-800 dark:bg-gray-900"
        >
            <h3 class="text-base font-bold text-slate-900 dark:text-white">
                Reject Change Request
            </h3>
            <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">
                Provide optional feedback to the contributor explaining why this
                request was declined.
            </p>

            <div class="mt-4">
                <textarea
                    v-model="rejectionReason"
                    rows="3"
                    placeholder="e.g. Please upload higher quality PDF or fix the video link..."
                    class="w-full rounded-xl border border-slate-200 p-3 text-xs focus:border-rose-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                ></textarea>
            </div>

            <div class="mt-5 flex items-center justify-end gap-2">
                <button
                    type="button"
                    @click="rejectingId = null"
                    class="cursor-pointer rounded-xl px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    @click="handleReject"
                    :disabled="isProcessing"
                    class="cursor-pointer rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-rose-500 disabled:opacity-50"
                >
                    Confirm Rejection
                </button>
            </div>
        </div>
    </div>
</template>
