<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Book,
    File,
    FileArchive,
    FileImage,
    FileVideo,
    Pencil,
    Trash2,
    Eye,
    AlertCircle,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { useAuth } from '@/lib/useAuth';

const { userId, can } = useAuth();

const props = defineProps({
    resource: Object,
    isFrozen: Boolean,
});

const emit = defineEmits<{
    (e: 'edit', resource: any): void;
    (e: 'view-pending', pending: any, resource: any): void;
}>();

const hasPendingChange = computed(() => {
    return Boolean(props.resource?.pending_change_request);
});

const pendingAction = computed(() => {
    return props.resource?.pending_change_request?.action_type;
});

const hasRejectedChange = computed(() => {
    return (
        !hasPendingChange.value &&
        Boolean(props.resource?.latest_rejected_change_request)
    );
});

const rejectedAction = computed(() => {
    return props.resource?.latest_rejected_change_request?.action_type;
});

const canEdit = computed(() => {
    return (
        !props.isFrozen &&
        !hasPendingChange.value &&
        (can('edit resources') ||
            (userId.value !== null && userId.value === props.resource?.user_id))
    );
});

const canDelete = computed(() => {
    return (
        !props.isFrozen &&
        !hasPendingChange.value &&
        (can('delete resources') ||
            (userId.value !== null && userId.value === props.resource?.user_id))
    );
});

const handleDelete = () => {
    if (
        confirm('Are you sure you want to request deletion of this Resource?')
    ) {
        router.delete(`/admin/resources/${props.resource?.id}`);
    }
};
</script>

<template>
    <div
        @click="router.visit(`/resources/${resource?.id}`)"
        class="group relative flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-slate-100 bg-white p-3 transition-colors duration-150 hover:border-amber-200 hover:bg-slate-50/50 sm:p-3.5 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-amber-500/30 dark:hover:bg-gray-800/40"
    >
        <!-- Left: Resource Icon + Full Title -->
        <div class="flex min-w-0 flex-1 items-center gap-3">
            <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-amber-200/40 bg-amber-50 text-amber-600 sm:h-10 sm:w-10 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-400"
            >
                <FileImage
                    v-if="resource?.resource_type === 'image'"
                    class="h-4.5 w-4.5 stroke-[2]"
                />
                <FileVideo
                    v-else-if="resource?.resource_type === 'video'"
                    class="h-4.5 w-4.5 stroke-[2]"
                />
                <FileArchive
                    v-else-if="resource?.resource_type === 'pdf'"
                    class="h-4.5 w-4.5 stroke-[2]"
                />
                <Book
                    v-else-if="resource?.resource_type === 'note'"
                    class="h-4.5 w-4.5 stroke-[2]"
                />
                <File v-else class="h-4.5 w-4.5 stroke-[2]" />
            </div>

            <div class="flex min-w-0 flex-wrap items-center gap-2">
                <h3
                    class="text-sm font-semibold break-words text-slate-900 transition-colors group-hover:text-amber-700 dark:text-gray-100 dark:group-hover:text-amber-400"
                >
                    {{ resource?.title }}
                </h3>

                <!-- Resource Type Badge -->
                <span
                    class="inline-flex items-center rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-bold text-amber-700 uppercase ring-1 ring-amber-600/20 ring-inset dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/30"
                >
                    {{ resource?.resource_type }}
                </span>

                <!-- Pending Moderation Badge -->
                <button
                    v-if="hasPendingChange && pendingAction === 'update'"
                    type="button"
                    @click.stop="
                        emit(
                            'view-pending',
                            resource.pending_change_request,
                            resource,
                        )
                    "
                    class="inline-flex cursor-pointer items-center gap-1 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-800 transition hover:bg-amber-200 dark:bg-amber-500/20 dark:text-amber-300 dark:hover:bg-amber-500/30"
                    title="View proposed changes"
                >
                    <Eye class="h-3 w-3" />
                    <span>Edit Pending</span>
                </button>
                <button
                    v-else-if="hasPendingChange && pendingAction === 'delete'"
                    type="button"
                    @click.stop="
                        emit(
                            'view-pending',
                            resource.pending_change_request,
                            resource,
                        )
                    "
                    class="inline-flex cursor-pointer items-center gap-1 rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-bold text-rose-800 transition hover:bg-rose-200 dark:bg-rose-500/20 dark:text-rose-300 dark:hover:bg-rose-500/30"
                    title="View deletion request"
                >
                    <Eye class="h-3 w-3" />
                    <span>Deletion Pending</span>
                </button>

                <!-- Rejected Moderation Badge -->
                <button
                    v-if="hasRejectedChange && rejectedAction === 'update'"
                    type="button"
                    @click.stop="
                        emit(
                            'view-pending',
                            resource.latest_rejected_change_request,
                            resource,
                        )
                    "
                    class="inline-flex cursor-pointer items-center gap-1 rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-bold text-rose-800 transition hover:bg-rose-200 dark:bg-rose-500/20 dark:text-rose-300 dark:hover:bg-rose-500/30"
                    title="View rejected edit feedback"
                >
                    <AlertCircle
                        class="h-3 w-3 text-rose-600 dark:text-rose-400"
                    />
                    <span>Edit Rejected</span>
                </button>
                <button
                    v-else-if="hasRejectedChange && rejectedAction === 'delete'"
                    type="button"
                    @click.stop="
                        emit(
                            'view-pending',
                            resource.latest_rejected_change_request,
                            resource,
                        )
                    "
                    class="inline-flex cursor-pointer items-center gap-1 rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-bold text-rose-800 transition hover:bg-rose-200 dark:bg-rose-500/20 dark:text-rose-300 dark:hover:bg-rose-500/30"
                    title="View rejected deletion feedback"
                >
                    <AlertCircle
                        class="h-3 w-3 text-rose-600 dark:text-rose-400"
                    />
                    <span>Deletion Rejected</span>
                </button>
            </div>
        </div>

        <!-- Right: Actions -->
        <div
            v-if="canEdit || canDelete || hasPendingChange || hasRejectedChange"
            class="flex shrink-0 items-center gap-1"
            @click.stop
        >
            <button
                v-if="hasPendingChange"
                type="button"
                @click="
                    emit(
                        'view-pending',
                        resource.pending_change_request,
                        resource,
                    )
                "
                class="inline-flex cursor-pointer items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
            >
                <Eye class="h-3.5 w-3.5 text-slate-500 dark:text-gray-400" />
                <span>Preview</span>
            </button>

            <button
                v-if="hasRejectedChange"
                type="button"
                @click="
                    emit(
                        'view-pending',
                        resource.latest_rejected_change_request,
                        resource,
                    )
                "
                class="inline-flex cursor-pointer items-center gap-1 rounded-lg border border-rose-200 bg-rose-50/60 px-2.5 py-1 text-xs font-semibold text-rose-700 shadow-2xs transition hover:bg-rose-100/80 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-300 dark:hover:bg-rose-900/60"
            >
                <AlertCircle
                    class="h-3.5 w-3.5 text-rose-600 dark:text-rose-400"
                />
                <span>Feedback</span>
            </button>

            <button
                v-if="canEdit"
                type="button"
                @click="emit('edit', resource)"
                class="cursor-pointer rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-amber-700 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-amber-400"
                title="Edit Resource"
            >
                <Pencil class="h-4 w-4" :stroke-width="1.8" />
            </button>

            <button
                v-if="canDelete"
                type="button"
                @click="handleDelete"
                class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:text-gray-500 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                title="Delete Resource"
            >
                <Trash2 class="h-4 w-4" :stroke-width="1.8" />
            </button>
        </div>
    </div>
</template>
