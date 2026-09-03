<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import AppLogo from '@/components/AppLogo.vue';
import LogoutConfirmModal from '@/components/LogoutConfirmModal.vue';
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

const showLogoutModal = ref(false);

const askLogout = () => {
    emit('close');
    showLogoutModal.value = true;
};

const currentUrl = computed(() => String(page.url));

const isActive = (to: string) => {
    if (to === '/admin') {
        return (
            currentUrl.value === '/admin' ||
            currentUrl.value.startsWith('/admin?')
        );
    }

    return currentUrl.value.startsWith(to);
};

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
            class="relative flex w-full max-w-[320px] flex-1 flex-col justify-between border-r border-slate-200/60 bg-white/85 shadow-[8px_0_32px_rgba(0,0,0,0.12)] backdrop-blur-xl dark:border-slate-800/60 dark:bg-slate-900/70 dark:backdrop-blur-xl"
        >
            <div class="flex-1 overflow-y-auto py-3.5">
                <div
                    class="flex h-16 shrink-0 items-center justify-between border-b border-slate-200/60 px-4 dark:border-slate-800/60"
                >
                    <AppLogo />
                    <button
                        @click="$emit('close')"
                        class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm transition-all duration-150 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:bg-slate-700 dark:hover:text-slate-100"
                        aria-label="Close staff menu"
                    >
                        <MaterialIcon name="close" :size="18" />
                    </button>
                </div>

                <p
                    class="mt-3 mb-2 px-4 text-[10px] font-bold tracking-[0.12em] text-slate-400 uppercase dark:text-slate-500"
                >
                    Management
                </p>
                <nav class="space-y-0.5 px-2.5">
                    <div v-for="item in navigation" :key="item.name">
                        <Link
                            :href="item.to"
                            @click="$emit('close')"
                            :class="[
                                'group flex items-center gap-2.5 rounded-[10px] px-2.5 py-2 text-[13px] font-medium tracking-tight transition-all duration-150 ease-out',
                                isActive(item.to)
                                    ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200/60 dark:bg-indigo-500/10 dark:text-indigo-200 dark:ring-indigo-500/20'
                                    : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/70 dark:hover:text-slate-100',
                            ]"
                        >
                            <MaterialIcon
                                :name="item.icon"
                                :size="22"
                                :class="[
                                    'shrink-0 transition-colors duration-150',
                                    isActive(item.to)
                                        ? 'text-indigo-600 dark:text-indigo-300'
                                        : 'text-slate-500 group-hover:text-slate-700 dark:text-slate-500 dark:group-hover:text-slate-300',
                                ]"
                            />
                            <span class="truncate">{{ item.name }}</span>
                            <span
                                v-if="isActive(item.to)"
                                class="ml-auto h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-600 dark:bg-indigo-400"
                            />
                        </Link>
                    </div>
                </nav>
            </div>

            <div
                class="space-y-1.5 border-t border-slate-200/60 bg-white/40 p-2 backdrop-blur-sm dark:border-slate-800/60 dark:bg-slate-900/40 dark:backdrop-blur-sm"
            >
                <button
                    v-if="can('clear cache')"
                    type="button"
                    @click="handleClearCache"
                    class="group flex h-11 w-full items-center gap-2.5 rounded-xl px-3 text-[13px] font-semibold text-rose-600 transition-all duration-150 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40"
                >
                    <MaterialIcon name="cached" :size="20" />
                    <span>Clear Cache</span>
                </button>
                <Link
                    href="/"
                    @click="$emit('close')"
                    class="flex h-11 w-full items-center gap-2.5 rounded-xl border border-transparent px-3 text-[13px] font-semibold text-slate-600 transition-all duration-150 hover:border-slate-200 hover:bg-white hover:text-slate-900 hover:shadow-sm dark:text-slate-400 dark:hover:border-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-100"
                >
                    <MaterialIcon name="home" :size="20" />
                    <span>Back to site</span>
                </Link>
                <button
                    v-if="user"
                    type="button"
                    @click="askLogout"
                    class="flex h-11 w-full items-center gap-2.5 rounded-xl border border-transparent px-3 text-left text-[13px] font-bold text-rose-600 transition-all duration-150 hover:bg-rose-50 dark:hover:bg-rose-950/40"
                >
                    <MaterialIcon name="logout" :size="20" />
                    <span>Log out</span>
                </button>
            </div>
        </div>

        <LogoutConfirmModal v-model:open="showLogoutModal" />
    </div>
</template>
