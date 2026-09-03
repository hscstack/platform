<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import AppLogo from '@/components/AppLogo.vue';
import NotificationDropdown from '@/components/NotificationDropdown.vue';
import MaterialIcon from '@/components/ui/MaterialIcon.vue';
import { useDarkMode } from '@/lib/useDarkMode';
import { usePermissions } from '@/lib/usePermissions';

export type AdminNavItem = {
    name: string;
    to: string;
    icon: string;
    permission?: string;
};

type Props = {
    navigation: AdminNavItem[];
    collapsed: boolean;
};

defineProps<Props>();
const emit = defineEmits<{ (e: 'toggle'): void }>();

const page = usePage();
const user = computed(
    () => page.props.auth?.user as App.Data.UserData | undefined,
);
const currentUrl = computed(() => String(page.url));
const { can } = usePermissions();
const { theme, toggle } = useDarkMode();

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
        router.post('/admin/clear-cache');
    }
};
</script>

<template>
    <aside
        :class="[
            'flex shrink-0 flex-col bg-white/70 backdrop-blur-xl transition-all duration-300 dark:bg-slate-900/60 dark:backdrop-blur-xl',
            'sticky top-0 h-screen border-r border-slate-200/60 shadow-[1px_0_3px_rgba(0,0,0,0.02),4px_0_16px_rgba(0,0,0,0.03)] dark:border-slate-800/60 dark:shadow-none',
            collapsed ? 'w-[72px]' : 'w-[280px]',
        ]"
        aria-label="Staff navigation"
    >
        <!-- Header: Logo + Collapse toggle -->
        <div
            :class="[
                'flex shrink-0 items-center border-b border-slate-200/60 bg-white/70 backdrop-blur-xl dark:border-slate-800/60 dark:bg-slate-900/60 dark:backdrop-blur-xl',
                collapsed
                    ? 'h-auto flex-col justify-center gap-3 px-2 py-3'
                    : 'h-16 flex-row justify-between px-3',
            ]"
        >
            <AppLogo v-if="!collapsed" />
            <Link
                v-else
                href="/admin"
                class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-lg bg-slate-900 shadow-sm ring-1 ring-slate-900/10 dark:bg-gray-100"
                aria-label="Staff Panel"
            >
                <img
                    src="/favicon.svg"
                    alt="HSCStack"
                    class="h-6 w-6 scale-120 object-cover"
                />
            </Link>
            <button
                type="button"
                @click="emit('toggle')"
                class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm transition-all duration-150 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 hover:shadow dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:bg-slate-700 dark:hover:text-slate-100"
                :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
            >
                <MaterialIcon
                    :name="collapsed ? 'chevron_right' : 'chevron_left'"
                    :size="18"
                />
            </button>
        </div>

        <!-- Scrollable admin nav -->
        <div class="flex flex-1 flex-col overflow-y-auto py-3.5">
            <div v-if="!collapsed" class="px-4 pb-2.5">
                <p
                    class="text-[10px] font-bold tracking-[0.12em] text-slate-400 uppercase dark:text-slate-500"
                >
                    Management
                </p>
            </div>
            <nav class="space-y-0.5 px-2.5">
                <Link
                    v-for="item in navigation"
                    :key="item.to"
                    :href="item.to"
                    :class="[
                        'group flex items-center gap-2.5 rounded-[10px] px-2.5 py-2 text-[13px] font-medium tracking-tight transition-all duration-150 ease-out',
                        isActive(item.to)
                            ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200/60 dark:bg-indigo-500/10 dark:text-indigo-200 dark:ring-indigo-500/20'
                            : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/70 dark:hover:text-slate-100',
                        collapsed ? 'justify-center px-2' : '',
                    ]"
                    :title="collapsed ? item.name : undefined"
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
                    <span v-if="!collapsed" class="truncate">{{
                        item.name
                    }}</span>
                    <span
                        v-if="!collapsed && isActive(item.to)"
                        class="ml-auto h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-600 dark:bg-indigo-400"
                    />
                </Link>
            </nav>

            <!-- Clear cache -->
            <div v-if="can('clear cache')" class="mt-4 px-2.5">
                <button
                    type="button"
                    @click="handleClearCache"
                    :class="[
                        'flex w-full items-center gap-2.5 rounded-[10px] px-2.5 py-2 text-[13px] font-medium tracking-tight text-rose-600 transition-all duration-150 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10',
                        collapsed ? 'justify-center px-2' : '',
                    ]"
                    :title="collapsed ? 'Clear cache' : undefined"
                >
                    <MaterialIcon name="cached" :size="22" class="shrink-0" />
                    <span v-if="!collapsed" class="truncate">Clear cache</span>
                </button>
            </div>
        </div>

        <!-- Footer controls — same h-11 geometry as SideRail -->
        <div
            class="border-t border-slate-200/60 bg-white/40 p-2 backdrop-blur-sm dark:border-slate-800/60 dark:bg-slate-900/40 dark:backdrop-blur-sm"
        >
            <div class="space-y-1.5">
                <!-- Theme -->
                <button
                    type="button"
                    @click="toggle"
                    :aria-label="collapsed ? `Theme: ${theme}` : undefined"
                    :title="collapsed ? `Theme: ${theme}` : undefined"
                    :class="[
                        'flex h-11 w-full items-center gap-2.5 rounded-xl border px-3 text-[13px] font-semibold transition-all duration-150',
                        'border-transparent text-slate-600 hover:border-slate-200 hover:bg-white hover:text-slate-900 hover:shadow-sm dark:text-slate-400 dark:hover:border-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-100',
                        collapsed ? 'justify-center' : '',
                    ]"
                >
                    <MaterialIcon
                        :name="
                            theme === 'system'
                                ? 'computer'
                                : theme === 'light'
                                  ? 'light_mode'
                                  : 'dark_mode'
                        "
                        :size="20"
                    />
                    <span v-if="!collapsed" class="capitalize">{{
                        theme
                    }}</span>
                </button>

                <!-- Notifications -->
                <div
                    v-if="user"
                    :class="[
                        'flex h-11 w-full items-center gap-2.5 px-3',
                        collapsed ? 'justify-center px-0' : 'justify-start',
                    ]"
                >
                    <NotificationDropdown />
                    <span
                        v-if="!collapsed"
                        class="text-[13px] font-semibold text-slate-600 dark:text-slate-400"
                    >
                        Notifications
                    </span>
                </div>

                <!-- Back to site — same size as other bottom actions -->
                <Link
                    href="/"
                    :class="[
                        'flex h-11 w-full items-center gap-2.5 rounded-xl border px-3 text-[13px] font-bold shadow-sm transition-all duration-150',
                        'justify-center border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:hover:border-slate-600 dark:hover:bg-slate-700',
                        collapsed ? '' : '',
                    ]"
                    :title="collapsed ? 'Back to site' : undefined"
                    aria-label="Back to site"
                >
                    <MaterialIcon name="home" :size="20" />
                    <span v-if="!collapsed">Back to site</span>
                </Link>

                <!-- User + logout -->
                <div v-if="user" :class="collapsed ? 'px-0' : 'px-2 pt-1'">
                    <div
                        :class="[
                            'flex items-center gap-3 rounded-xl border bg-white px-3 py-2.5 shadow-sm dark:border-slate-700 dark:bg-slate-800',
                            collapsed
                                ? 'flex-col justify-center gap-2 py-3'
                                : 'justify-between',
                        ]"
                    >
                        <Link
                            :href="
                                user.username
                                    ? `/u/${user.username}`
                                    : '/profile'
                            "
                            :class="[
                                'flex items-center gap-2.5',
                                collapsed ? 'justify-center' : 'min-w-0',
                            ]"
                            :title="collapsed ? 'Profile' : undefined"
                            :aria-label="collapsed ? 'Profile' : undefined"
                        >
                            <img
                                v-if="user.image_url"
                                :src="user.image_url"
                                :alt="user.name"
                                class="h-8 w-8 rounded-full object-cover ring-1 ring-slate-200 dark:ring-slate-700"
                            />
                            <span
                                v-else
                                class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-indigo-600 to-violet-600 text-xs font-bold text-white ring-1 ring-indigo-600/20"
                                >{{ user.name.charAt(0).toUpperCase() }}</span
                            >
                            <div v-if="!collapsed" class="min-w-0 text-left">
                                <p
                                    class="truncate text-xs font-semibold text-slate-900 dark:text-slate-100"
                                >
                                    {{ user.name }}
                                </p>
                                <p
                                    class="truncate text-[11px] text-slate-500 dark:text-slate-400"
                                >
                                    {{ user.email }}
                                </p>
                            </div>
                        </Link>
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            title="Log out"
                            aria-label="Log out"
                            class="shrink-0 rounded-lg p-1.5 text-rose-500 transition-colors hover:bg-rose-50 hover:text-rose-600 hover:shadow-sm dark:text-rose-400 dark:hover:bg-rose-950/40 dark:hover:text-rose-300"
                        >
                            <MaterialIcon name="logout" :size="20" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </aside>
</template>
