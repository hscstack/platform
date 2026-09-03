<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import AppLogo from '@/components/AppLogo.vue';
import MaterialIcon from '@/components/ui/MaterialIcon.vue';
import { usePermissions } from '@/lib/usePermissions';

const { can } = usePermissions();
const page = usePage();
const user = computed(
    () => (page.props.auth as any)?.user as App.Data.UserData | undefined,
);

defineProps<{
    navigation: Array<{
        name: string;
        to: string;
        icon: string;
        permission?: string;
    }>;
    isOpen: boolean;
}>();

const emit = defineEmits(['close']);

const handleClearCache = () => {
    if (confirm('Are you sure you want to clear all cache?')) {
        emit('close');
        router.post('/admin/clear-cache');
    }
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex md:hidden">
        <div
            class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm dark:bg-black/60"
            @click="$emit('close')"
        ></div>

        <div
            class="relative flex w-full max-w-xs flex-1 flex-col justify-between border-r border-slate-200 bg-white/95 p-5 shadow-2xl backdrop-blur-xl dark:border-gray-700 dark:bg-gray-900/95"
        >
            <div class="flex-1 overflow-y-auto">
                <div class="mb-6 flex items-center justify-between">
                    <AppLogo />
                    <button
                        @click="$emit('close')"
                        class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800"
                        aria-label="Close staff menu"
                    >
                        <MaterialIcon name="close" :size="20" />
                    </button>
                </div>

                <p
                    class="mb-2 px-2 text-[11px] font-bold tracking-widest text-slate-400 uppercase dark:text-gray-500"
                >
                    Management
                </p>
                <nav class="space-y-1">
                    <div v-for="(item, index) in navigation" :key="item.name">
                        <Link
                            :href="item.to"
                            @click="$emit('close')"
                            class="group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 transition-colors hover:bg-indigo-50/60 hover:text-indigo-600 dark:text-gray-400 dark:hover:bg-indigo-500/10 dark:hover:text-indigo-400"
                        >
                            <MaterialIcon :name="item.icon" :size="20" />
                            <span>{{ item.name }}</span>
                        </Link>

                        <hr
                            v-if="index < navigation.length - 1"
                            class="mx-3 my-1 border-slate-200/60 dark:border-gray-700/60"
                        />
                    </div>
                </nav>
            </div>

            <div
                class="space-y-1.5 border-t border-slate-100 pt-4 dark:border-gray-800"
            >
                <button
                    v-if="can('clear cache')"
                    type="button"
                    @click="handleClearCache"
                    class="group flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-rose-600 transition-colors hover:bg-rose-50/60 dark:text-rose-400 dark:hover:bg-rose-500/10"
                >
                    <MaterialIcon name="cached" :size="20" />
                    <span>Clear Cache</span>
                </button>
                <Link
                    href="/"
                    @click="$emit('close')"
                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold text-slate-700 transition-colors hover:bg-slate-100 dark:text-gray-300 dark:hover:bg-gray-800"
                >
                    <MaterialIcon name="home" :size="20" />
                    <span>Back to site</span>
                </Link>
                <Link
                    v-if="user"
                    href="/logout"
                    method="post"
                    as="button"
                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold text-rose-600 transition-colors hover:bg-rose-50 dark:hover:bg-rose-950/40"
                >
                    <MaterialIcon name="logout" :size="20" />
                    <span>Log out</span>
                </Link>
            </div>
        </div>
    </div>
</template>
