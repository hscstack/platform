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
} from 'lucide-vue-next';
import { usePermissions } from '@/lib/usePermissions';

const { can } = usePermissions();

const { resource } = defineProps({
    resource: Object,
});

const emit = defineEmits<{
    (e: 'edit', resource: any): void;
}>();

const handleDelete = () => {
    if (confirm('Are you sure you want to delete this Resource?')) {
        router.delete(`/admin/resources/${resource.id}`);
    }
};
</script>

<template>
    <div
        @click="router.visit(`/resources/${resource.id}`)"
        class="group relative flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-slate-100 bg-white p-3 transition-colors duration-150 hover:border-amber-200 hover:bg-slate-50/50 sm:p-3.5 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-amber-500/30 dark:hover:bg-gray-800/40"
    >
        <!-- Left: Resource Icon + Full Title -->
        <div class="flex min-w-0 flex-1 items-center gap-3">
            <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-amber-200/40 bg-amber-50 text-amber-600 sm:h-10 sm:w-10 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-400"
            >
                <FileImage
                    v-if="resource.resource_type === 'image'"
                    class="h-4.5 w-4.5 stroke-[2]"
                />
                <FileVideo
                    v-else-if="resource.resource_type === 'video'"
                    class="h-4.5 w-4.5 stroke-[2]"
                />
                <FileArchive
                    v-else-if="resource.resource_type === 'pdf'"
                    class="h-4.5 w-4.5 stroke-[2]"
                />
                <Book
                    v-else-if="resource.resource_type === 'note'"
                    class="h-4.5 w-4.5 stroke-[2]"
                />
                <File v-else class="h-4.5 w-4.5 stroke-[2]" />
            </div>

            <div class="flex min-w-0 flex-wrap items-center gap-2">
                <h3
                    class="text-sm font-semibold break-words text-slate-900 transition-colors group-hover:text-amber-700 dark:text-gray-100 dark:group-hover:text-amber-400"
                >
                    {{ resource.title }}
                </h3>

                <!-- Resource Type Badge -->
                <span
                    class="inline-flex items-center rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-bold text-amber-700 uppercase ring-1 ring-amber-600/20 ring-inset dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/30"
                >
                    {{ resource.resource_type }}
                </span>
            </div>
        </div>

        <!-- Right: Actions -->
        <div
            v-if="can('edit resources') || can('delete resources')"
            class="flex shrink-0 items-center gap-1"
            @click.stop
        >
            <button
                v-if="can('edit resources')"
                type="button"
                @click="emit('edit', resource)"
                class="cursor-pointer rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-amber-700 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-amber-400"
                title="Edit Resource"
            >
                <Pencil class="h-4 w-4" :stroke-width="1.8" />
            </button>

            <button
                v-if="can('delete resources')"
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
