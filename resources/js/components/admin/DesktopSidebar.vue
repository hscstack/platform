<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Database } from 'lucide-vue-next';
import { usePermissions } from '@/lib/usePermissions';

const { can } = usePermissions();

defineProps({
    navigation: Array,
});

const handleClearCache = () => {
    if (confirm('Are you sure you want to clear all cache?')) {
        router.post('/admin/clear-cache');
    }
};
</script>

<template>
    <nav class="space-y-1">
        <p
            class="mb-3 px-3 text-xs font-semibold tracking-wider text-slate-400 uppercase dark:text-gray-500"
        >
            Management
        </p>

        <div
            v-for="(item, index) in navigation"
            :key="item.name"
            class="group/wrapper relative"
        >
            <Link
                :href="item.to"
                class="group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 transition-all duration-300 hover:bg-indigo-50/60 hover:text-indigo-600 dark:text-gray-400 dark:hover:bg-indigo-500/10 dark:hover:text-indigo-400"
            >
                <span
                    class="absolute top-1/4 bottom-1/4 left-0 w-[3px] origin-center scale-y-0 rounded-full bg-indigo-600 transition-transform duration-200 group-hover:scale-y-100 dark:bg-indigo-400"
                ></span>
                <component
                    :is="item.icon"
                    class="h-4 w-4 text-slate-400 transition-all duration-300 ease-out group-hover:scale-110 group-hover:rotate-[2deg] group-hover:text-indigo-500 dark:text-gray-500 dark:group-hover:text-indigo-400"
                    :stroke-width="2"
                />
                <span
                    class="transform transition-transform duration-300 ease-out group-hover:translate-x-0.5"
                >
                    {{ item.name }}
                </span>
            </Link>
            <hr
                v-if="index < navigation.length - 1"
                class="mx-3 my-1 border-slate-200/60 dark:border-gray-700/60"
            />
        </div>
    </nav>

    <div class="space-y-3">
        <button
            v-if="can('clear cache')"
            @click="handleClearCache"
            class="group relative flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-rose-600 transition-all duration-300 hover:bg-rose-50/60 hover:text-rose-700 dark:text-rose-400 dark:hover:bg-rose-500/10 dark:hover:text-rose-300"
        >
            <span
                class="absolute top-1/4 bottom-1/4 left-0 w-[3px] origin-center scale-y-0 rounded-full bg-rose-600 transition-transform duration-200 group-hover:scale-y-100 dark:bg-rose-400"
            ></span>
            <Database
                class="h-4 w-4 text-rose-400 transition-all duration-300 ease-out group-hover:scale-110 group-hover:-rotate-[4deg] group-hover:text-rose-500 dark:text-rose-500 dark:group-hover:text-rose-400"
                :stroke-width="2"
            />
            <span
                class="transform transition-transform duration-300 ease-out group-hover:translate-x-0.5"
            >
                Clear cache
            </span>
        </button>

        <div
            class="border-t border-slate-200/60 px-2 pt-3 text-xs text-slate-400 dark:border-gray-700/60 dark:text-gray-500"
        >
            Internal Dashboard
        </div>
    </div>
</template>
