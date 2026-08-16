<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { X, LogOut, Database } from 'lucide-vue-next';
import { kPanel, kList, kListItem, kBlock, kDialog, kButton } from 'konsta/vue';

defineProps({
    navigation: Array,
    isOpen: Boolean,
});

defineEmits(['close']);

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
    <kPanel
        :opened="isOpen"
        side="left"
        effect="cover"
        class="md:hidden"
        @opened:change="
            (val: boolean) => {
                if (!val) $emit('close');
            }
        "
    >
        <div
            class="flex h-full flex-col justify-between bg-white/90 backdrop-blur-xl dark:bg-gray-900/90"
        >
            <div>
                <div class="mb-6 flex items-center justify-between px-5 pt-5">
                    <p
                        class="text-xs font-semibold tracking-wider text-slate-400 uppercase dark:text-gray-500"
                    >
                        Navigation
                    </p>
                    <button
                        @click="$emit('close')"
                        class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <nav class="space-y-1 px-3">
                    <kList class="!my-0">
                        <div
                            v-for="(item, index) in navigation"
                            :key="item.name"
                        >
                            <kListItem
                                :link="true"
                                :href="item.to"
                                @click="$emit('close')"
                                class="group relative !rounded-xl !px-3 !py-2.5 text-sm font-medium !text-slate-600 hover:!bg-indigo-50/60 hover:!text-indigo-600 dark:!text-gray-400 dark:hover:!bg-indigo-500/10 dark:hover:!text-indigo-400"
                            >
                                <template #media>
                                    <component
                                        :is="item.icon"
                                        class="h-4 w-4"
                                    />
                                </template>

                                <template #title>
                                    <span>{{ item.name }}</span>
                                </template>
                            </kListItem>

                            <hr
                                v-if="index < navigation.length - 1"
                                class="mx-3 my-1 border-slate-200/60 dark:border-gray-700/60"
                            />
                        </div>
                    </kList>
                </nav>
            </div>

            <kBlock class="!my-0 space-y-3 px-3">
                <button
                    @click="clearCacheDialogOpened = true"
                    class="group flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-rose-600 hover:bg-rose-50/60 dark:text-rose-400 dark:hover:bg-rose-500/10"
                >
                    <Database class="h-4 w-4" />
                    Clear Cache
                </button>
                <button
                    @click="logoutDialogOpened = true"
                    class="group flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-rose-600 hover:bg-rose-50/60 dark:text-rose-400 dark:hover:bg-rose-500/10"
                >
                    <LogOut class="h-4 w-4" />
                    Logout
                </button>

                <div
                    class="border-t border-slate-200/60 px-2 pt-3 text-xs text-slate-400 dark:border-gray-700/60 dark:text-gray-500"
                >
                    Internal Dashboard
                </div>
            </kBlock>
        </div>
    </kPanel>

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
