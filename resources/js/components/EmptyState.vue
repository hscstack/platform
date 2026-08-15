<script setup lang="ts">
import { Folder } from 'lucide-vue-next';
</script>

<template>
    <div
        class="flex flex-col items-center justify-center px-4 py-14 text-center"
    >
        <div
            class="flex h-12 w-12 items-center justify-center rounded-2xl border border-slate-100 bg-slate-50 text-slate-400 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-500"
        >
            <Folder class="h-6 w-6 stroke-[1.8]" />
        </div>
        <h3 class="mt-4 text-sm font-bold text-slate-900 dark:text-gray-100">
            No items available
        </h3>
        <p
            class="mt-1 max-w-xs text-xs leading-relaxed font-medium text-slate-400 dark:text-gray-500"
        >
            There are currently no files or sub-folders located inside this
            section.
        </p>
    </div>
</template>
