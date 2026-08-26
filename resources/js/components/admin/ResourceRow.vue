<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import {
    Book,
    File,
    FileArchive,
    FileImage,
    FileVideo,
    Pencil,
    Trash2,
} from 'lucide-vue-next';

const { resource } = defineProps({
    resource: Object,
});

const handleDelete = () => {
    if (confirm('Are you sure you want to delete this Resource?')) {
        router.delete(`/admin/resources/${resource.id}`);
    }
};
</script>

<template>
    <div
        @click="router.visit(`/resources/${resource.id}`)"
        class="group relative flex cursor-pointer flex-col gap-3 rounded-xl border border-slate-200/90 bg-white p-3.5 transition-all duration-150 hover:border-amber-300 hover:bg-amber-50/40 hover:shadow-xs sm:flex-row sm:items-center sm:justify-between sm:p-4 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-amber-500/40 dark:hover:bg-gray-800/50"
    >
        <!-- Left: Resource Icon + Full Title -->
        <div class="flex items-center gap-3.5 min-w-0">
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-amber-200/40 bg-amber-50 text-amber-600 shadow-2xs transition-transform duration-200 group-hover:scale-105 sm:h-11 sm:w-11 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-400"
            >
                <FileImage
                    v-if="resource.resource_type === 'image'"
                    class="h-5 w-5 stroke-[2.2]"
                />
                <FileVideo
                    v-else-if="resource.resource_type === 'video'"
                    class="h-5 w-5 stroke-[2.2]"
                />
                <FileArchive
                    v-else-if="resource.resource_type === 'pdf'"
                    class="h-5 w-5 stroke-[2.2]"
                />
                <Book
                    v-else-if="resource.resource_type === 'note'"
                    class="h-5 w-5 stroke-[2.2]"
                />
                <File v-else class="h-5 w-5 stroke-[2.2]" />
            </div>

            <div class="flex flex-col min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h3
                        class="text-sm font-bold text-slate-800 break-words transition-colors group-hover:text-amber-700 dark:text-gray-200 dark:group-hover:text-amber-400"
                    >
                        {{ resource.title }}
                    </h3>

                    <!-- Resource Type Badge -->
                    <span
                        class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-[10px] font-extrabold tracking-wider text-amber-700 uppercase ring-1 ring-amber-600/20 ring-inset dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/30"
                    >
                        {{ resource.resource_type }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Right: Actions -->
        <div
            class="flex items-center gap-2 self-end sm:self-center"
            @click.stop
        >
            <Link
                :href="`/admin/resources/edit/${resource.id}`"
                target="_self"
                class="inline-flex h-7 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-600 shadow-2xs transition-colors hover:border-amber-200 hover:bg-amber-50 hover:text-amber-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-amber-500/30 dark:hover:bg-amber-500/10 dark:hover:text-amber-400"
                title="Edit Resource"
            >
                <Pencil class="h-3 w-3" :stroke-width="2" />
                <span>Edit</span>
            </Link>

            <button
                type="button"
                @click="handleDelete"
                class="inline-flex h-7 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-rose-600 shadow-2xs transition-colors hover:border-red-200 hover:bg-red-50 hover:text-red-700 dark:border-gray-700 dark:bg-gray-800 dark:text-rose-400 dark:hover:border-red-500/30 dark:hover:bg-red-500/10"
                title="Delete Resource"
            >
                <Trash2 class="h-3 w-3" :stroke-width="2" />
                <span>Delete</span>
            </button>
        </div>
    </div>
</template>
