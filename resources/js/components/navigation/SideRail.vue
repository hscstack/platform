<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import AppLogo from '@/components/AppLogo.vue';
import AuthModal from '@/components/AuthModal.vue';
import NotificationDropdown from '@/components/NotificationDropdown.vue';
import MaterialIcon from '@/components/ui/MaterialIcon.vue';
import { allNavItems } from '@/lib/navigation';
import { useDarkMode } from '@/lib/useDarkMode';
import { usePwa } from '@/lib/usePwa';

type Props = {
    collapsed: boolean;
};

defineProps<Props>();
const emit = defineEmits<{ (e: 'toggle'): void }>();

const page = usePage();
const user = computed(
    () => page.props.auth?.user as App.Data.UserData | undefined,
);
const canAccessAdmin = computed(() =>
    Boolean(page.props.auth?.can_access_admin),
);
const isAdminRoute = computed(() => String(page.url).startsWith('/admin'));
const currentUrl = computed(() => String(page.url));

const { theme, toggle } = useDarkMode();
const { deferredPrompt, isInstalled, promptInstall } = usePwa();
const canInstallApp = computed(
    () => !isInstalled.value && Boolean(deferredPrompt.value),
);
const handleInstallApp = async () => {
    await promptInstall();
};

const homeHref = computed(() => {
    if (typeof window !== 'undefined') {
        try {
            const pref = localStorage.getItem('preferred_course');

            if (pref === 'ssc') {
                return '/ssc';
            }
        } catch {}
    }

    return currentUrl.value.startsWith('/ssc') ? '/ssc' : '/';
});

const isActive = (href: string, match?: (url: string) => boolean) => {
    if (match) {
        return match(currentUrl.value);
    }

    return currentUrl.value.startsWith(href);
};

// Search gate
const showAuthModal = ref(false);
const authModalMessage = ref('');

const triggerSearch = () => {
    if (!user.value) {
        authModalMessage.value =
            'অনুগ্রহ করে বিষয় ও কনটেন্ট সার্চ করতে প্রথমে লগইন করুন।';
        showAuthModal.value = true;

        return;
    }

    window.dispatchEvent(new CustomEvent('hscstack:trigger-search'));
};

// Persist collapsed is handled by parent
</script>

<template>
    <aside
        :class="[
            'flex shrink-0 flex-col bg-white transition-all duration-300 dark:bg-slate-900',
            'sticky top-0 h-screen border-r border-slate-200/60 shadow-[1px_0_3px_rgba(0,0,0,0.02),4px_0_16px_rgba(0,0,0,0.03)] dark:border-slate-800/60 dark:shadow-none',
            collapsed ? 'w-[72px]' : 'w-[280px]',
        ]"
        aria-label="Side navigation"
    >
        <!-- Header: Logo + Collapse toggle -->
        <div
            class="flex h-16 shrink-0 items-center border-b border-slate-100 bg-white px-3 dark:border-slate-800 dark:bg-slate-900"
            :class="collapsed ? 'justify-center gap-0' : 'justify-between'"
        >
            <AppLogo v-if="!collapsed" />
            <div
                v-else
                class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 text-white shadow-sm ring-1 ring-indigo-600/20"
            >
                <span class="text-xs font-black">H</span>
            </div>
            <button
                type="button"
                @click="emit('toggle')"
                class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm transition-all duration-150 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 hover:shadow dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:bg-slate-700 dark:hover:text-slate-100"
                :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
            >
                <MaterialIcon
                    :name="collapsed ? 'chevron_right' : 'chevron_left'"
                    :size="20"
                />
            </button>
        </div>

        <!-- Scrollable nav -->
        <div class="flex flex-1 flex-col overflow-y-auto py-3.5">
            <div v-if="!collapsed" class="px-4 pb-2.5">
                <p
                    class="text-[10px] font-bold tracking-[0.12em] text-slate-400 uppercase dark:text-slate-500"
                >
                    Navigation
                </p>
            </div>
            <!-- Primary + Overflow combined for desktop rail -->
            <nav class="space-y-0.5 px-2.5">
                <Link
                    v-for="item in allNavItems"
                    :key="item.href"
                    :href="item.href === '/' ? homeHref : item.href"
                    :class="[
                        'group flex items-center gap-2.5 rounded-[10px] px-2.5 py-2 text-[13px] font-medium tracking-tight transition-all duration-150 ease-out',
                        isActive(item.href, item.match)
                            ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200/60 dark:bg-indigo-500/10 dark:text-indigo-200 dark:ring-indigo-500/20'
                            : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/70 dark:hover:text-slate-100',
                        collapsed ? 'justify-center px-2' : '',
                    ]"
                    :title="collapsed ? item.label : undefined"
                >
                    <MaterialIcon
                        :name="item.icon"
                        :size="22"
                        :class="[
                            'shrink-0 transition-colors duration-150',
                            isActive(item.href, item.match)
                                ? 'text-indigo-600 dark:text-indigo-300'
                                : 'text-slate-500 group-hover:text-slate-700 dark:text-slate-500 dark:group-hover:text-slate-300',
                        ]"
                    />
                    <span v-if="!collapsed" class="truncate">{{
                        item.label
                    }}</span>
                    <span
                        v-if="!collapsed && isActive(item.href, item.match)"
                        class="ml-auto h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-600 dark:bg-indigo-400"
                    />
                </Link>
            </nav>

            <!-- PWA Install -->
            <div v-if="canInstallApp" class="mt-6 px-2">
                <button
                    type="button"
                    @click="handleInstallApp"
                    :class="[
                        'flex w-full items-center gap-2.5 rounded-xl border px-3 py-2.5 text-[13px] font-bold shadow-sm transition-all duration-150',
                        collapsed
                            ? 'justify-center border-slate-900 bg-slate-900 text-white hover:bg-slate-800 hover:shadow-md dark:border-white dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100'
                            : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:hover:border-slate-600 dark:hover:bg-slate-700',
                    ]"
                >
                    <MaterialIcon name="download" :size="20" />
                    <span v-if="!collapsed">Install App</span>
                </button>
            </div>
        </div>

        <!-- Footer controls -->
        <div
            class="border-t border-slate-100 bg-slate-50/50 p-2 dark:border-slate-800 dark:bg-slate-900/50"
        >
            <div class="space-y-1">
                <!-- Search -->
                <button
                    type="button"
                    @click="triggerSearch"
                    :class="[
                        'flex w-full items-center gap-3 rounded-lg px-3 py-2 text-[13px] font-medium transition-colors duration-150',
                        'text-slate-600 hover:bg-white hover:text-slate-900 hover:shadow-sm dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100',
                        collapsed ? 'justify-center' : '',
                    ]"
                >
                    <MaterialIcon name="search" :size="20" />
                    <span v-if="!collapsed">Search</span>
                </button>

                <!-- Theme -->
                <button
                    type="button"
                    @click="toggle"
                    :class="[
                        'flex w-full items-center gap-3 rounded-lg px-3 py-2 text-[13px] font-medium transition-colors duration-150',
                        'text-slate-600 hover:bg-white hover:text-slate-900 hover:shadow-sm dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100',
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
                        'flex',
                        collapsed ? 'justify-center px-0' : 'px-2 py-1',
                    ]"
                >
                    <NotificationDropdown />
                </div>

                <!-- Auth -->
                <div :class="collapsed ? 'px-0' : 'px-2 pt-1'">
                    <Link
                        v-if="!user"
                        href="/login"
                        :class="[
                            'flex items-center justify-center gap-2 rounded-xl px-3 py-2.5 text-[13px] font-bold shadow-sm transition-all duration-150',
                            'bg-indigo-600 text-white hover:bg-indigo-700 hover:shadow-md dark:bg-indigo-500 dark:hover:bg-indigo-400',
                            collapsed ? 'px-2' : '',
                        ]"
                    >
                        <MaterialIcon name="login" :size="20" />
                        <span v-if="!collapsed">Login</span>
                    </Link>
                    <div
                        v-else
                        :class="[
                            'flex items-center gap-3 rounded-xl border bg-white px-3 py-2.5 shadow-sm dark:border-slate-700 dark:bg-slate-800',
                            collapsed ? 'justify-center' : 'justify-between',
                        ]"
                    >
                        <div class="flex min-w-0 items-center gap-2.5">
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
                            <div v-if="!collapsed" class="min-w-0">
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
                        </div>
                        <Link
                            v-if="!collapsed && canAccessAdmin"
                            :href="isAdminRoute ? '/' : '/admin'"
                            class="shrink-0 rounded-lg bg-slate-50 p-1.5 text-slate-500 transition-colors hover:bg-white hover:text-slate-900 hover:shadow-sm dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-100"
                            :title="isAdminRoute ? 'Home' : 'Staff Panel'"
                        >
                            <MaterialIcon name="dashboard" :size="20" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <AuthModal
            v-model="showAuthModal"
            title="Sign in required"
            :message="authModalMessage"
        />
    </aside>
</template>
