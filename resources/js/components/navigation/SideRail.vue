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
            'flex shrink-0 flex-col bg-white shadow-[4px_0_24px_rgba(0,0,0,0.04)] transition-all duration-300 dark:bg-gray-900',
            'sticky top-0 h-screen border-r border-slate-200/70 dark:border-gray-800',
            collapsed ? 'w-[72px]' : 'w-[280px]',
        ]"
        aria-label="Side navigation"
    >
        <!-- Header: Logo + Collapse toggle -->
        <div
            class="flex h-16 shrink-0 items-center border-b border-slate-100 bg-white px-3 dark:border-gray-800 dark:bg-gray-900"
            :class="collapsed ? 'justify-center gap-0' : 'justify-between'"
        >
            <AppLogo v-if="!collapsed" />
            <div
                v-else
                class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 text-white shadow-sm"
            >
                <span class="text-xs font-black">H</span>
            </div>
            <button
                type="button"
                @click="emit('toggle')"
                class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-600 hover:bg-white hover:text-slate-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
            >
                <MaterialIcon
                    :name="collapsed ? 'chevron_right' : 'chevron_left'"
                    :size="20"
                />
            </button>
        </div>

        <!-- Scrollable nav -->
        <div class="flex flex-1 flex-col overflow-y-auto py-4">
            <div v-if="!collapsed" class="px-4 pb-2">
                <p
                    class="text-[11px] font-bold tracking-widest text-slate-400 uppercase dark:text-gray-500"
                >
                    Navigation
                </p>
            </div>
            <!-- Primary + Overflow combined for desktop rail -->
            <nav class="space-y-1 px-2">
                <Link
                    v-for="item in allNavItems"
                    :key="item.href"
                    :href="item.href === '/' ? homeHref : item.href"
                    :class="[
                        'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition-all',
                        isActive(item.href, item.match)
                            ? 'bg-slate-900 text-white shadow-sm dark:bg-white dark:text-slate-900'
                            : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100',
                        collapsed
                            ? 'justify-center'
                            : isActive(item.href, item.match)
                              ? 'shadow-sm'
                              : '',
                    ]"
                    :title="collapsed ? item.label : undefined"
                >
                    <MaterialIcon
                        :name="item.icon"
                        :size="20"
                        :class="[
                            'shrink-0 transition-colors',
                            isActive(item.href, item.match)
                                ? 'text-white dark:text-slate-900'
                                : 'text-slate-500 group-hover:text-slate-900 dark:text-gray-500',
                        ]"
                    />
                    <span v-if="!collapsed" class="truncate">{{
                        item.label
                    }}</span>
                </Link>
            </nav>

            <!-- PWA Install -->
            <div v-if="canInstallApp" class="mt-6 px-2">
                <button
                    type="button"
                    @click="handleInstallApp"
                    :class="[
                        'flex w-full items-center gap-3 rounded-xl border px-3 py-2.5 text-sm font-bold shadow-sm transition-colors',
                        collapsed
                            ? 'justify-center border-slate-900 bg-slate-900 text-white dark:border-white dark:bg-white dark:text-slate-900'
                            : 'border-slate-900 bg-slate-900 text-white hover:bg-slate-800 dark:border-white dark:bg-white dark:text-slate-900 dark:hover:bg-gray-100',
                    ]"
                >
                    <MaterialIcon name="download" :size="18" />
                    <span v-if="!collapsed">Install App</span>
                </button>
            </div>
        </div>

        <!-- Footer controls -->
        <div class="border-t border-slate-100 p-2 dark:border-gray-800">
            <div class="space-y-1">
                <!-- Search -->
                <button
                    type="button"
                    @click="triggerSearch"
                    :class="[
                        'flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800',
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
                        'flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800',
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
                        collapsed ? 'justify-center px-0' : 'px-3 py-1',
                    ]"
                >
                    <NotificationDropdown />
                </div>

                <!-- Auth -->
                <div :class="collapsed ? 'px-0' : 'px-2 pt-2'">
                    <Link
                        v-if="!user"
                        href="/login"
                        :class="[
                            'flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-3 py-2.5 text-sm font-bold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900',
                            collapsed ? 'px-2' : '',
                        ]"
                    >
                        <MaterialIcon name="login" :size="18" />
                        <span v-if="!collapsed">Login</span>
                    </Link>
                    <div
                        v-else
                        :class="[
                            'flex items-center gap-3 rounded-xl border bg-slate-50 px-3 py-2.5 dark:border-gray-800 dark:bg-gray-800',
                            collapsed ? 'justify-center' : 'justify-between',
                        ]"
                    >
                        <div class="flex min-w-0 items-center gap-2">
                            <img
                                v-if="user.image_url"
                                :src="user.image_url"
                                :alt="user.name"
                                class="h-8 w-8 rounded-full object-cover"
                            />
                            <span
                                v-else
                                class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white"
                                >{{ user.name.charAt(0).toUpperCase() }}</span
                            >
                            <div v-if="!collapsed" class="min-w-0">
                                <p
                                    class="truncate text-xs font-semibold text-slate-900 dark:text-gray-100"
                                >
                                    {{ user.name }}
                                </p>
                                <p class="truncate text-[11px] text-slate-500">
                                    {{ user.email }}
                                </p>
                            </div>
                        </div>
                        <Link
                            v-if="!collapsed && canAccessAdmin"
                            :href="isAdminRoute ? '/' : '/admin'"
                            class="shrink-0 rounded-lg p-1.5 text-slate-500 hover:bg-white dark:hover:bg-gray-700"
                            :title="isAdminRoute ? 'Home' : 'Staff Panel'"
                        >
                            <MaterialIcon name="dashboard" :size="18" />
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
