<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ExternalLink,
    FileImage,
    FileText,
    FileVideo,
    Pencil,
    PlusCircle,
    Trash2,
} from 'lucide-vue-next';
import { ref } from 'vue';
import BaseModal from '@/components/BaseModal.vue';
import ImageViewerModal from '@/components/ImageViewerModal.vue';
import { formatDateTime } from '@/lib/useDate';
import { usePermissions } from '@/lib/usePermissions';

export interface User {
    id: number;
    name: string;
    username: string;
    image_path?: string;
}

export interface Subject {
    id: number;
    name: string;
    slug: string;
}

export interface NodeBreadcrumb {
    name: string;
    slug: string;
}

export interface Node {
    id: number;
    name: string;
    slug?: string;
    subject?: Subject;
    breadcrumb?: NodeBreadcrumb[];
}

export interface LiveResource {
    id: number;
    title: string;
    resource_type: string;
    content?: string;
    external_url?: string;
    file_path?: string;
    file_url?: string;
}

export interface ChangeRequest {
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

export interface ChangedField {
    key: string;
    label: string;
    oldVal: any;
    newVal: any;
    isMedia?: boolean;
}

export interface BreadcrumbItem {
    name: string;
    url: string;
}

defineProps<{
    request: ChangeRequest | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const { can } = usePermissions();

const isViewerOpen = ref(false);
const viewerImageSrc = ref('');
const viewerImageAlt = ref('');

const openImageViewer = (src: string, alt = 'Resource Image') => {
    viewerImageSrc.value = src;
    viewerImageAlt.value = alt;
    isViewerOpen.value = true;
};

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
    } else {
        items.push({
            name: node.name,
            url: node.subject ? `/${node.subject.slug}` : '#',
        });
    }

    return items;
};

const getChangedFields = (req: ChangeRequest): ChangedField[] => {
    const live = req.resource || req.targetResource;

    if (req.action_type !== 'update' || !live || !req.payload) {
        return [];
    }

    const changes: ChangedField[] = [];
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
            oldVal: live.file_url || live.file_path || null,
            newVal: req.staged_file_url || null,
            isMedia: true,
        });
    }

    return changes;
};
</script>

<template>
    <BaseModal
        :is-open="request !== null"
        :title="
            request?.action_type === 'create'
                ? 'Upload Request'
                : request?.action_type === 'update'
                  ? 'Change Request'
                  : 'Deletion Request'
        "
        max-width="2xl"
        @close="emit('close')"
    >
        <template #icon>
            <div
                v-if="request?.action_type === 'create'"
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400"
            >
                <PlusCircle class="h-5 w-5" />
            </div>
            <div
                v-else-if="request?.action_type === 'update'"
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

        <div v-if="request" class="space-y-4 p-4 sm:p-6">
            <!-- Modal Header Meta (Location, Submitter, Live Link) -->
            <div
                class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200/60 pb-3 text-xs text-slate-500 dark:border-slate-800/60 dark:text-gray-400"
            >
                <div
                    v-if="getNodeBreadcrumbs(request.node).length > 0"
                    class="flex flex-wrap items-center gap-1"
                >
                    <span>In:</span>
                    <template
                        v-for="(crumb, cIdx) in getNodeBreadcrumbs(
                            request.node,
                        )"
                        :key="cIdx"
                    >
                        <span
                            v-if="cIdx > 0"
                            class="text-slate-400 dark:text-gray-600"
                            >/</span
                        >
                        <Link
                            :href="crumb.url"
                            class="font-semibold text-slate-700 hover:text-indigo-600 hover:underline dark:text-gray-300 dark:hover:text-indigo-400"
                            :title="crumb.name"
                        >
                            {{ crumb.name }}
                        </Link>
                    </template>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex flex-wrap items-center gap-1">
                        <template v-if="request.user?.name">
                            <Link
                                v-if="can('edit users') && request.user?.id"
                                :href="`/admin/users/edit/${request.user.id}`"
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
                                {{ request.user.name }}
                            </span>
                            <Link
                                v-if="request.user?.username"
                                :href="`/u/${request.user.username}`"
                                target="_blank"
                                class="text-indigo-600 hover:underline dark:text-indigo-400"
                            >
                                (@{{ request.user.username }})
                            </Link>
                            <span>•</span>
                        </template>
                        <span>{{ formatDateTime(request.created_at) }}</span>
                    </div>
                </div>
            </div>

            <!-- MODAL CONTENT: CREATE -->
            <div v-if="request.action_type === 'create'" class="space-y-4">
                <!-- Header: Title & Resource Type -->
                <div class="flex items-start justify-between gap-3">
                    <h3
                        class="text-base font-bold text-slate-900 sm:text-lg dark:text-white"
                    >
                        {{ request.payload?.title || '(untitled)' }}
                    </h3>
                    <span
                        v-if="request.payload?.resource_type"
                        class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-slate-200/80 bg-slate-100/80 px-2.5 py-1 text-xs font-semibold tracking-wider text-slate-700 uppercase dark:border-gray-700/80 dark:bg-gray-800 dark:text-gray-300"
                    >
                        <FileImage
                            v-if="request.payload?.resource_type === 'image'"
                            class="h-3.5 w-3.5 text-amber-500"
                        />
                        <FileVideo
                            v-else-if="
                                request.payload?.resource_type === 'video'
                            "
                            class="h-3.5 w-3.5 text-rose-500"
                        />
                        <FileText v-else class="h-3.5 w-3.5 text-indigo-500" />
                        <span>{{ request.payload?.resource_type }}</span>
                    </span>
                </div>

                <!-- Description / Body -->
                <p
                    v-if="request.payload?.content"
                    class="text-xs leading-relaxed whitespace-pre-line text-slate-600 sm:text-sm dark:text-gray-300"
                >
                    {{ request.payload.content }}
                </p>

                <!-- External URL -->
                <div
                    v-if="request.payload?.external_url"
                    class="flex items-center gap-2 rounded-xl border border-slate-200/80 bg-slate-50/50 px-3.5 py-2.5 text-xs text-slate-600 dark:border-slate-800/60 dark:bg-gray-800/40 dark:text-gray-300"
                >
                    <ExternalLink
                        class="h-3.5 w-3.5 shrink-0 text-slate-400 dark:text-gray-500"
                    />
                    <span class="font-medium text-slate-400 dark:text-gray-500"
                        >URL:</span
                    >
                    <a
                        :href="request.payload.external_url"
                        target="_blank"
                        class="truncate font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                    >
                        {{ request.payload.external_url }}
                    </a>
                </div>

                <!-- Staged Uploaded File -->
                <div
                    v-if="request.staged_file_url"
                    class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-2.5 dark:border-slate-800/60 dark:bg-gray-800/30"
                >
                    <img
                        v-if="request.payload?.resource_type === 'image'"
                        :src="request.staged_file_url"
                        alt="Staged"
                        class="max-h-72 w-auto max-w-full cursor-zoom-in rounded-lg object-contain transition hover:opacity-95"
                        @click="
                            openImageViewer(
                                request.staged_file_url,
                                request.payload?.title || 'Staged Image',
                            )
                        "
                    />
                    <a
                        v-else
                        :href="request.staged_file_url"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-1 py-0.5 text-xs font-semibold text-emerald-600 hover:underline dark:text-emerald-400"
                    >
                        <ExternalLink class="h-3.5 w-3.5" />
                        <span>Open Staged File</span>
                    </a>
                </div>
            </div>

            <!-- MODAL CONTENT: UPDATE (DIFF ONLY CHANGED FIELDS) -->
            <div v-else-if="request.action_type === 'update'" class="space-y-3">
                <div class="flex flex-wrap items-center gap-1.5">
                    <span
                        class="text-xs font-bold text-slate-500 dark:text-gray-400"
                    >
                        Changed fields:
                    </span>
                    <template v-if="getChangedFields(request).length > 0">
                        <span
                            v-for="change in getChangedFields(request)"
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
                        v-for="change in getChangedFields(request)"
                        :key="`modal-diff-${change.key}`"
                        class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xs dark:border-slate-800/60 dark:bg-gray-900"
                    >
                        <div
                            class="border-b border-slate-200/60 bg-slate-50 px-3.5 py-1.5 text-[11px] font-bold text-slate-700 dark:border-slate-800/60 dark:bg-gray-800/60 dark:text-gray-300"
                        >
                            {{ change.label }}
                        </div>

                        <div
                            class="grid grid-cols-1 divide-y divide-slate-200/60 sm:grid-cols-2 sm:divide-x sm:divide-y-0 dark:divide-gray-800"
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
                                                (request.resource
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
                                                class="max-h-48 max-w-full cursor-zoom-in rounded-lg border border-slate-200 object-contain transition hover:opacity-90 dark:border-gray-700"
                                                @click="
                                                    openImageViewer(
                                                        change.oldVal,
                                                        'Current Image',
                                                    )
                                                "
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
                                    <template v-else>
                                        <span
                                            class="line-through decoration-rose-400/80"
                                            >{{ change.oldVal }}</span
                                        >
                                    </template>
                                </div>
                            </div>

                            <!-- After -->
                            <div
                                class="bg-amber-50/20 p-3 dark:bg-amber-950/10"
                            >
                                <div
                                    class="mb-1 text-[10px] font-bold tracking-wider text-amber-700 uppercase dark:text-amber-400"
                                >
                                    After
                                </div>
                                <div
                                    class="text-xs font-semibold text-slate-900 dark:text-white"
                                >
                                    <template v-if="change.key === 'file'">
                                        <div
                                            v-if="
                                                change.newVal &&
                                                (request.payload
                                                    ?.resource_type ===
                                                    'image' ||
                                                    request.resource
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
                                                class="max-h-48 max-w-full cursor-zoom-in rounded-lg border border-slate-200 object-contain transition hover:opacity-90 dark:border-gray-700"
                                                @click="
                                                    openImageViewer(
                                                        change.newVal,
                                                        'Proposed New Image',
                                                    )
                                                "
                                            />
                                            <div
                                                class="text-[11px] font-normal text-amber-700 dark:text-amber-300"
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
                v-else-if="request.action_type === 'delete'"
                class="rounded-xl border border-rose-200 bg-rose-50/60 p-4 dark:border-rose-900/50 dark:bg-rose-950/20"
            >
                <div class="flex items-start gap-3">
                    <Trash2 class="mt-0.5 h-6 w-6 shrink-0 text-rose-500" />
                    <div>
                        <h4
                            class="text-base font-bold text-rose-950 dark:text-rose-200"
                        >
                            Target: "{{
                                request.resource?.title ||
                                request.targetResource?.title ||
                                'Resource'
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
                v-if="request.status !== 'pending'"
                class="rounded-xl bg-slate-50 p-3 text-xs dark:bg-gray-800/60"
            >
                <div
                    v-if="request.status === 'approved'"
                    class="font-semibold text-emerald-600 dark:text-emerald-400"
                >
                    ✓ Approved<span v-if="request.reviewer?.name">
                        by {{ request.reviewer.name }}</span
                    >
                    <span v-if="request.reviewed_at">
                        on {{ formatDateTime(request.reviewed_at) }}
                    </span>
                </div>
                <div v-else class="space-y-1 text-rose-600 dark:text-rose-400">
                    <div class="font-semibold">
                        ✕ Rejected<span v-if="request.reviewer?.name">
                            by {{ request.reviewer.name }}</span
                        >
                        <span v-if="request.reviewed_at">
                            on {{ formatDateTime(request.reviewed_at) }}
                        </span>
                    </div>
                    <div
                        v-if="request.rejection_reason"
                        class="text-xs text-slate-600 dark:text-gray-300"
                    >
                        Reason: {{ request.rejection_reason }}
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex items-center justify-between">
                <div>
                    <a
                        v-if="
                            request?.resource?.id ||
                            request?.targetResource?.id ||
                            request?.resource_id
                        "
                        :href="`/resources/${request.resource?.id || request.targetResource?.id || request.resource_id}`"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-50 hover:text-indigo-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-indigo-400"
                    >
                        <span>View Live Resource</span>
                        <ExternalLink class="h-3.5 w-3.5" />
                    </a>
                </div>

                <button
                    type="button"
                    @click="emit('close')"
                    class="cursor-pointer rounded-xl px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800"
                >
                    Close
                </button>
            </div>
        </template>
    </BaseModal>

    <!-- Fullscreen Image Viewer Modal -->
    <ImageViewerModal
        v-model="isViewerOpen"
        :src="viewerImageSrc"
        :alt="viewerImageAlt"
        @close="isViewerOpen = false"
    />
</template>
