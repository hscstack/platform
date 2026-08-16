<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Book, File, FileArchive, FileImage, FileVideo } from 'lucide-vue-next';
import { kList, kListItem } from 'konsta/vue';

const { resource } = defineProps({
    resource: Object,
});
</script>

<template>
    <kList>
        <kListItem :link="true" :href="`/resources/${resource.id}`">
            <template #media>
                <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-amber-200/40 bg-amber-50 text-amber-600 transition-colors duration-200 group-hover:border-amber-200 group-hover:bg-amber-100/70 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-400 dark:group-hover:border-amber-500/30 dark:group-hover:bg-amber-500/20"
                >
                    <FileImage
                        v-if="resource.resource_type == 'image'"
                        class="h-5 w-5 stroke-[2.2]"
                    />
                    <FileVideo
                        v-else-if="resource.resource_type == 'video'"
                        class="h-5 w-5 stroke-[2.2]"
                    />
                    <FileArchive
                        v-else-if="resource.resource_type == 'pdf'"
                        class="h-5 w-5 stroke-[2.2]"
                    />
                    <Book
                        v-else-if="resource.resource_type == 'note'"
                        class="h-5 w-5 stroke-[2.2]"
                    />
                    <File v-else class="h-5 w-5 stroke-[2.2]" />
                </div>
            </template>

            <template #title>
                <span
                    class="block text-base font-bold break-words text-slate-900 transition-colors group-hover:text-amber-700 dark:text-gray-100 dark:group-hover:text-amber-400"
                >
                    {{ resource.title }}
                </span>
            </template>

            <template #subtitle>
                <span
                    class="mt-0.5 inline-block text-xs font-semibold text-amber-600 sm:hidden dark:text-amber-400"
                >
                    {{ resource.resource_type }}
                </span>
            </template>

            <template #after>
                <div class="flex shrink-0 items-center gap-4 pl-3">
                    <span
                        class="hidden rounded-md border border-amber-200/60 bg-amber-50/60 px-2.5 py-1 text-xs font-bold text-amber-600 sm:inline-block dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-400"
                    >
                        {{ resource.resource_type }}
                    </span>

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4 text-slate-400 transition-transform duration-200 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:text-amber-600 dark:text-gray-500 dark:group-hover:text-amber-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2.5"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                        />
                    </svg>
                </div>
            </template>
        </kListItem>
    </kList>
</template>

<style scoped></style>
