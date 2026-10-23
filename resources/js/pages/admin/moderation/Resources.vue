<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Clock,
    CheckCircle2,
    XCircle,
    FileText,
    Image as ImageIcon,
    Video,
    FlaskConical,
    BookOpen,
    File,
    ChevronRight,
    Check,
    X,
    Loader2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import Pagination from '@/components/Pagination.vue';

interface ResourceItem {
    id: number;
    resource_type: string;
    title: string;
    description?: string | null;
    status: 'pending' | 'approved' | 'rejected';
    admin_note?: string | null;
    items_count?: number;
    created_at: string;
    user?: {
        id: number;
        name: string;
        username: string;
        image_path?: string | null;
    } | null;
    node?: {
        id: number;
        name: string;
        slug: string;
        subject?: {
            id: number;
            name: string;
            slug: string;
        } | null;
        breadcrumb?: Array<{
            name: string;
            slug?: string;
        }>;
    } | null;
}

interface PaginationData<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

const props = defineProps<{
    resources: PaginationData<ResourceItem>;
    currentStatus: string;
    counts: {
        all: number;
        pending: number;
        approved: number;
        rejected: number;
    };
}>();

const selectedIds = ref<number[]>([]);
const isBatchApproving = ref(false);
const isBatchRejecting = ref(false);
const showBatchRejectModal = ref(false);
const batchRejectNote = ref('');

const isAllSelected = computed(() => {
    return (
        props.resources.data.length > 0 &&
        props.resources.data.every((r) => selectedIds.value.includes(r.id))
    );
});

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = props.resources.data.map((r) => r.id);
    }
};

const toggleSelect = (id: number) => {
    if (selectedIds.value.includes(id)) {
        selectedIds.value = selectedIds.value.filter((x) => x !== id);
    } else {
        selectedIds.value.push(id);
    }
};

const handleBatchApprove = () => {
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

    isBatchApproving.value = true;
    router.post(
        '/admin/resources/approve',
        { ids: selectedIds.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedIds.value = [];
            },
            onFinish: () => {
                isBatchApproving.value = false;
            },
        },
    );
};

const handleBatchReject = () => {
    if (selectedIds.value.length === 0) {
        return;
    }

    isBatchRejecting.value = true;
    router.post(
        '/admin/resources/reject',
        {
            ids: selectedIds.value,
            admin_note: batchRejectNote.value.trim() || null,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedIds.value = [];
                showBatchRejectModal.value = false;
                batchRejectNote.value = '';
            },
            onFinish: () => {
                isBatchRejecting.value = false;
            },
        },
    );
};

const tabs = [
    { key: 'pending', label: 'Pending Review' },
    { key: 'approved', label: 'Approved' },
    { key: 'rejected', label: 'Rejected' },
    { key: 'all', label: 'All Resources' },
] as const;

const formatDate = (dateString: string) => {
    try {
        const date = new Date(dateString);

        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return dateString;
    }
};

const getResourceIcon = (type?: string) => {
    switch (type) {
        case 'practical':
            return FlaskConical;
        case 'marked_book':
            return BookOpen;
        case 'video':
            return Video;
        case 'pdf':
            return File;
        case 'image':
            return ImageIcon;
        default:
            return FileText;
    }
};

const getNodePath = (node?: any): string[] => {
    if (!node) {
        return [];
    }

    const segments: string[] = [];

    if (node.subject?.name) {
        segments.push(node.subject.name);
    }

    if (node.breadcrumb && Array.isArray(node.breadcrumb)) {
        for (const crumb of node.breadcrumb) {
            segments.push(crumb.name);
        }
    } else if (node.name) {
        segments.push(node.name);
    }

    return segments;
};
</script>

<template>
    <Head title="Resource Moderation Queue" />

    <div class="flex w-full flex-1 flex-col font-sans">
        <!-- Header -->
        <div
            class="mb-4 flex flex-col gap-3 border-b border-slate-100 pb-3 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800"
        >
            <div class="flex items-center gap-2.5">
                <Link
                    href="/admin"
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-2xs transition hover:bg-slate-50 hover:text-slate-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                    title="Back to Dashboard"
                >
                    <ArrowLeft class="h-4 w-4" />
                </Link>
                <div>
                    <h1
                        class="text-base font-bold tracking-tight text-slate-900 sm:text-lg dark:text-gray-100"
                    >
                        Resource Moderation Queue
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-gray-400">
                        Review submissions individually on their real pages, or
                        select multiple items below to batch approve/reject.
                    </p>
                </div>
            </div>

            <!-- Pending Count Pill -->
            <div
                v-if="counts.pending > 0"
                class="inline-flex items-center gap-1.5 self-start rounded-xl border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-800 dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-300"
            >
                <Clock class="h-3.5 w-3.5 animate-pulse" />
                <span>{{ counts.pending }} Awaiting Review</span>
            </div>
        </div>

        <!-- Filter Tabs & Select All Bar -->
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <Link
                    v-for="tab in tabs"
                    :key="tab.key"
                    :href="`/admin/moderation/resources?status=${tab.key}`"
                    preserve-scroll
                    class="inline-flex items-center gap-2 rounded-xl px-3.5 py-1.5 text-xs font-semibold transition-all"
                    :class="[
                        currentStatus === tab.key
                            ? tab.key === 'pending'
                                ? 'bg-amber-500 text-white shadow-2xs'
                                : 'bg-slate-900 text-white shadow-2xs dark:bg-white dark:text-slate-900'
                            : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800',
                    ]"
                >
                    <span>{{ tab.label }}</span>
                    <span
                        class="py-0.2 rounded-full px-1.5 text-[10px] font-bold"
                        :class="[
                            currentStatus === tab.key
                                ? 'bg-white/25 text-white'
                                : 'bg-slate-100 text-slate-600 dark:bg-gray-800 dark:text-gray-400',
                        ]"
                    >
                        {{ counts[tab.key] }}
                    </span>
                </Link>
            </div>

            <!-- Select All Checkbox -->
            <div
                v-if="resources.data.length > 0"
                class="flex items-center gap-2 text-xs font-medium text-slate-600 dark:text-gray-400"
            >
                <label
                    class="flex cursor-pointer items-center gap-2 select-none"
                >
                    <input
                        type="checkbox"
                        :checked="isAllSelected"
                        @change="toggleSelectAll"
                        class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800"
                    />
                    <span>Select All on this page</span>
                </label>
            </div>
        </div>

        <!-- Sticky Floating Batch Action Bar -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-3"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-3"
        >
            <div
                v-if="selectedIds.length > 0"
                class="sticky top-4 z-30 mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-indigo-200 bg-indigo-50/95 p-3.5 shadow-lg backdrop-blur-md sm:px-5 dark:border-indigo-900/60 dark:bg-indigo-950/90"
            >
                <div
                    class="flex items-center gap-2 text-xs font-bold text-indigo-950 dark:text-indigo-200"
                >
                    <span
                        class="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-600 text-xs text-white"
                    >
                        {{ selectedIds.length }}
                    </span>
                    <span>item(s) selected</span>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <!-- Batch Approve -->
                    <button
                        v-if="currentStatus !== 'approved'"
                        type="button"
                        :disabled="isBatchApproving || isBatchRejecting"
                        @click="handleBatchApprove"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-xs transition hover:bg-emerald-700 disabled:opacity-50"
                    >
                        <Loader2
                            v-if="isBatchApproving"
                            class="h-3.5 w-3.5 animate-spin"
                        />
                        <Check v-else class="h-3.5 w-3.5 stroke-[2.5]" />
                        <span>Approve Selected</span>
                    </button>

                    <!-- Batch Reject -->
                    <button
                        v-if="currentStatus !== 'rejected'"
                        type="button"
                        :disabled="isBatchApproving || isBatchRejecting"
                        @click="showBatchRejectModal = true"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-rose-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-xs transition hover:bg-rose-700 disabled:opacity-50"
                    >
                        <X class="h-3.5 w-3.5 stroke-[2.5]" />
                        <span>Reject Selected</span>
                    </button>

                    <!-- Deselect All -->
                    <button
                        type="button"
                        @click="selectedIds = []"
                        class="cursor-pointer rounded-xl px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-indigo-100/60 dark:text-gray-300 dark:hover:bg-indigo-900/40"
                    >
                        Deselect
                    </button>
                </div>
            </div>
        </transition>

        <!-- Resources List -->
        <div v-if="resources.data.length > 0" class="flex flex-col gap-2.5">
            <div
                v-for="item in resources.data"
                :key="item.id"
                @click="router.visit(`/resources/${item.id}`)"
                class="group relative flex cursor-pointer items-center justify-between gap-3 rounded-2xl border p-3.5 shadow-2xs transition hover:border-indigo-300 hover:bg-slate-50/50 sm:p-4 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-indigo-700/60 dark:hover:bg-gray-800/50"
                :class="[
                    selectedIds.includes(item.id)
                        ? 'border-indigo-300 bg-indigo-50/40 dark:border-indigo-800/80 dark:bg-indigo-950/20'
                        : 'border-slate-200/80 bg-white',
                ]"
            >
                <!-- Left: Checkbox + Icon + Details -->
                <div class="flex min-w-0 flex-1 items-start gap-3 sm:gap-3.5">
                    <!-- Checkbox -->
                    <div class="pt-1 select-none" @click.stop>
                        <input
                            type="checkbox"
                            :checked="selectedIds.includes(item.id)"
                            @change="toggleSelect(item.id)"
                            class="h-4 w-4 cursor-pointer rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800"
                        />
                    </div>

                    <!-- Icon -->
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600 transition group-hover:bg-indigo-50 group-hover:text-indigo-600 dark:bg-gray-800 dark:text-gray-300 dark:group-hover:bg-indigo-950/60 dark:group-hover:text-indigo-400"
                    >
                        <component
                            :is="getResourceIcon(item.resource_type)"
                            class="h-5 w-5"
                        />
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3
                                class="text-sm font-bold text-slate-900 transition group-hover:text-indigo-600 dark:text-gray-100 dark:group-hover:text-indigo-400"
                            >
                                {{ item.title }}
                            </h3>

                            <span
                                class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-700 uppercase dark:bg-gray-800 dark:text-gray-300"
                            >
                                {{ item.resource_type }}
                            </span>

                            <span
                                v-if="item.items_count !== undefined"
                                class="text-xs text-slate-400 dark:text-gray-500"
                            >
                                • {{ item.items_count }} items
                            </span>
                        </div>

                        <!-- Breadcrumb & Author Info -->
                        <div
                            class="mt-1 flex flex-wrap items-center gap-x-2.5 gap-y-1 text-xs text-slate-500 dark:text-gray-400"
                        >
                            <span
                                v-if="getNodePath(item.node).length > 0"
                                class="inline-flex items-center gap-1 font-medium text-slate-600 dark:text-gray-300"
                            >
                                <span>in</span>
                                <span class="text-slate-800 dark:text-gray-200">
                                    {{ getNodePath(item.node).join(' / ') }}
                                </span>
                            </span>

                            <span
                                v-if="item.user"
                                class="inline-flex items-center gap-1"
                            >
                                <span>• by</span>
                                <span
                                    class="font-semibold text-slate-700 dark:text-gray-300"
                                >
                                    {{ item.user.name }}
                                </span>
                            </span>

                            <span>• {{ formatDate(item.created_at) }}</span>
                        </div>

                        <!-- Rejection feedback if rejected -->
                        <div
                            v-if="item.status === 'rejected' && item.admin_note"
                            class="mt-2 rounded-lg bg-rose-50 px-2.5 py-1 text-xs text-rose-700 dark:bg-rose-950/40 dark:text-rose-300"
                        >
                            <span class="font-bold">Rejection Note:</span>
                            {{ item.admin_note }}
                        </div>
                    </div>
                </div>

                <!-- Right: Status Pill & Action Prompt -->
                <div class="flex shrink-0 items-center gap-2.5 sm:gap-3">
                    <span
                        v-if="item.status === 'pending'"
                        class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700 dark:bg-amber-950/60 dark:text-amber-400"
                    >
                        <Clock class="h-3.5 w-3.5" />
                        <span>Pending</span>
                    </span>

                    <span
                        v-else-if="item.status === 'approved'"
                        class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400"
                    >
                        <CheckCircle2 class="h-3.5 w-3.5" />
                        <span>Approved</span>
                    </span>

                    <span
                        v-else-if="item.status === 'rejected'"
                        class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700 dark:bg-rose-950/60 dark:text-rose-400"
                    >
                        <XCircle class="h-3.5 w-3.5" />
                        <span>Rejected</span>
                    </span>

                    <span
                        class="hidden items-center gap-1 text-xs font-semibold text-indigo-600 group-hover:underline sm:inline-flex dark:text-indigo-400"
                    >
                        <span>Review</span>
                        <ChevronRight class="h-4 w-4" />
                    </span>
                </div>
            </div>

            <!-- Pagination -->
            <div class="pt-4">
                <Pagination
                    :links="resources.links"
                    :from="resources.from"
                    :to="resources.to"
                    :total="resources.total"
                />
            </div>
        </div>

        <!-- Empty State -->
        <EmptyState
            v-else
            title="No resources found"
            :description="
                currentStatus === 'pending'
                    ? 'All clear! There are no pending submissions awaiting review right now.'
                    : 'No resources match this status filter.'
            "
        />

        <!-- Batch Reject Reason Modal -->
        <teleport to="body">
            <div
                v-if="showBatchRejectModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs"
                @click.self="showBatchRejectModal = false"
            >
                <div
                    class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="mb-3">
                        <h3
                            class="text-sm font-bold text-slate-900 dark:text-gray-100"
                        >
                            Reject {{ selectedIds.length }} Selected Resource(s)
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-gray-400">
                            Provide an optional reason that will be sent to the
                            uploaders and saved as rejection feedback.
                        </p>
                    </div>

                    <div class="space-y-3">
                        <textarea
                            v-model="batchRejectNote"
                            rows="3"
                            maxlength="500"
                            placeholder="e.g. Incomplete notes, blurry images, or copyright issues..."
                            class="w-full rounded-xl border border-slate-200 p-3 text-xs text-slate-800 placeholder-slate-400 focus:border-rose-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                        />

                        <div class="flex items-center justify-end gap-2">
                            <button
                                type="button"
                                @click="showBatchRejectModal = false"
                                class="cursor-pointer rounded-xl px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                :disabled="isBatchRejecting"
                                @click="handleBatchReject"
                                class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-rose-700 disabled:opacity-50"
                            >
                                <Loader2
                                    v-if="isBatchRejecting"
                                    class="h-3.5 w-3.5 animate-spin"
                                />
                                <span>Confirm Rejection</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </teleport>
    </div>
</template>
