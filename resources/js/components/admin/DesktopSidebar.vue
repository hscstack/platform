<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { kList, kListItem, kBlock, kDialog, kButton } from 'konsta/vue';
import { Database, LogOut } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps({
    navigation: Array,
});

const logoutDialogOpened = ref(false);
const clearCacheDialogOpened = ref(false);

const confirmLogout = () => {
    logoutDialogOpened.value = false;
    router.post('/logout');
};

const confirmClearCache = () => {
    clearCacheDialogOpened.value = false;
    router.post('/admin/clear-cache');
};
</script>

<template>
    <nav class="space-y-1">
        <p
            class="mb-3 px-3 text-xs font-semibold tracking-wider text-slate-400 uppercase dark:text-gray-500"
        >
            Management
        </p>

        <kList>
            <div v-for="(item, index) in navigation" :key="item.name">
                <kListItem :link="true" :href="item.to">
                    <template #media>
                        <component :is="item.icon" :stroke-width="2" />
                    </template>

                    <template #title>
                        {{ item.name }}
                    </template>
                </kListItem>

                <hr v-if="index < navigation.length - 1" />
            </div>
        </kList>
    </nav>

    <kBlock strong outline>
        <kListItem link @click="clearCacheDialogOpened = true">
            <template #media>
                <Database :stroke-width="2" />
            </template>

            <template #title> Clear cache </template>
        </kListItem>

        <kListItem link @click="logoutDialogOpened = true">
            <template #media>
                <LogOut :stroke-width="2" />
            </template>

            <template #title> Logout </template>
        </kListItem>

        <div class="px-2 pt-3 text-xs text-slate-400 dark:text-gray-500">
            Internal Dashboard
        </div>
    </kBlock>

    <kDialog
        :opened="logoutDialogOpened"
        @opened:change="logoutDialogOpened = $event"
    >
        <div class="p-4">
            <p class="text-base font-semibold text-gray-900 dark:text-white">
                Confirm Logout
            </p>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                Are you sure you want to log out?
            </p>
            <div class="mt-4 flex justify-end gap-2">
                <kButton @click="logoutDialogOpened = false">Cancel</kButton>
                <kButton @click="confirmLogout">Confirm</kButton>
            </div>
        </div>
    </kDialog>

    <kDialog
        :opened="clearCacheDialogOpened"
        @opened:change="clearCacheDialogOpened = $event"
    >
        <div class="p-4">
            <p class="text-base font-semibold text-gray-900 dark:text-white">
                Clear Cache
            </p>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                Are you sure you want to clear all cache?
            </p>
            <div class="mt-4 flex justify-end gap-2">
                <kButton @click="clearCacheDialogOpened = false"
                    >Cancel</kButton
                >
                <kButton @click="confirmClearCache">Confirm</kButton>
            </div>
        </div>
    </kDialog>
</template>
