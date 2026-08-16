<script setup lang="ts">
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { kDialog, kButton } from 'konsta/vue';
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

const deleteDialogOpened = ref(false);
const deleteCallback = ref<() => void>(() => {});

const showDeleteDialog = () => {
    deleteCallback.value = () =>
        router.delete(`/admin/resources/${resource.id}`);
    deleteDialogOpened.value = true;
};

const confirmDelete = () => {
    deleteDialogOpened.value = false;
    deleteCallback.value();
};
</script>

<template>
    <div
        @click="router.visit(`/resources/${resource.id}`)"
        class="group relative flex cursor-pointer flex-col items-center justify-center rounded-xl border border-transparent p-2 text-center transition-all duration-200 hover:border-amber-100/60 hover:bg-amber-50/30 hover:shadow-sm active:scale-95 dark:hover:border-amber-500/30 dark:hover:bg-amber-500/10"
    >
        <div
            class="absolute top-1 right-1 z-10 flex gap-1 opacity-100 transition-opacity duration-150 md:opacity-0 md:group-hover:opacity-100"
            @click.stop
        >
            <Link
                :href="`/admin/resources/edit/${resource.id}`"
                target="_self"
                class="inline-flex h-5 items-center gap-1 rounded-md border border-slate-200 bg-white px-1.5 py-0.5 text-[10px] font-medium text-slate-500 shadow-sm transition-colors hover:border-amber-200 hover:bg-amber-50 hover:text-amber-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:hover:border-amber-500/30 dark:hover:bg-amber-500/10 dark:hover:text-amber-400"
                title="Edit Resource"
            >
                <Pencil class="h-2.5 w-2.5" :stroke-width="2.2" />
                <span>Edit</span>
            </Link>

            <button
                type="button"
                @click="showDeleteDialog"
                class="inline-flex h-5 items-center gap-1 rounded-md border border-slate-200 bg-white px-1.5 py-0.5 text-[10px] font-medium text-slate-500 shadow-sm transition-colors hover:border-red-200 hover:bg-red-50 hover:text-red-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:hover:border-red-500/30 dark:hover:bg-red-500/10 dark:hover:text-red-400"
                title="Delete Resource"
            >
                <Trash2 class="h-2.5 w-2.5" :stroke-width="2.2" />
                <span>Delete</span>
            </button>
        </div>

        <div
            class="mb-2 flex h-14 w-14 shrink-0 items-center justify-center rounded-xl border border-amber-200/40 bg-amber-50 text-amber-600 transition-colors duration-200 group-hover:border-amber-200 group-hover:bg-amber-100/70 group-hover:text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-400 dark:group-hover:border-amber-500/30 dark:group-hover:bg-amber-500/20 dark:group-hover:text-amber-400"
        >
            <FileImage
                v-if="resource.resource_type === 'image'"
                class="h-6 w-6 stroke-[2.2]"
            />
            <FileVideo
                v-else-if="resource.resource_type === 'video'"
                class="h-6 w-6 stroke-[2.2]"
            />
            <FileArchive
                v-else-if="resource.resource_type === 'pdf'"
                class="h-6 w-6 stroke-[2.2]"
            />
            <Book
                v-else-if="resource.resource_type === 'note'"
                class="h-6 w-6 stroke-[2.2]"
            />
            <File v-else class="h-6 w-6 stroke-[2.2]" />
        </div>

        <div class="w-full max-w-[100px] px-0.5">
            <span
                class="block truncate text-xs font-bold text-slate-900 transition-colors group-hover:text-amber-700 dark:text-gray-100 dark:group-hover:text-amber-400"
                :title="resource.title"
            >
                {{ resource.title }}
            </span>
        </div>
    </div>

    <kDialog
        :opened="deleteDialogOpened"
        @opened:change="deleteDialogOpened = $event"
    >
        <div class="p-4">
            <p class="text-base font-semibold text-gray-900 dark:text-white">
                Delete Resource
            </p>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                Are you sure you want to delete this Resource?
            </p>
            <div class="mt-4 flex justify-end gap-2">
                <kButton @click="deleteDialogOpened = false">Cancel</kButton>
                <kButton @click="confirmDelete">Delete</kButton>
            </div>
        </div>
    </kDialog>
</template>
