<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    Download,
    LayoutDashboard,
    LogIn,
    Monitor,
    Moon,
    Search,
    Sun,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

import AppLogo from '@/components/AppLogo.vue';
import AuthModal from '@/components/AuthModal.vue';
import NotificationDropdown from '@/components/NotificationDropdown.vue';
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

    // Focus is handled by globalSearchQuery watchers in pages; just set flag via store
    // For rail, we dispatch a custom event that Home pages listen to
    window.dispatchEvent(new CustomEvent('hscstack:trigger-search'));
};

// Persist collapsed is handled by parent
</script>

<template>
    <aside
        :class="[
            'hidden flex-col border-r bg-white/80 backdrop-blur-xl transition-all duration-300 dark:border-gray-800 dark:bg-gray-950/80',
            'portrait:hidden landscape:flex',
            collapsed ? 'w-[72px]' : 'w-[256px]',
        ]"
        aria-label="Side navigation"
    >
        <!-- Header: Logo + Collapse toggle -->
        <div
            class="flex h-16 shrink-0 items-center border-b border-slate-100 px-3 dark:border-gray-800"
            :class="collapsed ? 'justify-center' : 'justify-between'"
        >
            <AppLogo v-if="!collapsed" />
            <div
                v-else
                class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-600 text-white"
            >
                <span class="text-xs font-black">H</span>
            </div>
            <button
                type="button"
                @click="emit('toggle')"
                class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
            >
                <ChevronRight v-if="collapsed" class="h-4 w-4" />
                <ChevronLeft v-else class="h-4 w-4" />
            </button>
        </div>

        <!-- Scrollable nav -->
        <div class="flex flex-1 flex-col overflow-y-auto py-3">
            <!-- Primary + Overflow combined for desktop rail -->
            <nav class="space-y-1 px-2">
                <Link
                    v-for="item in allNavItems"
                    :key="item.href"
                    :href="item.href === '/' ? homeHref : item.href"
                    :class="[
                        'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors',
                        isActive(item.href, item.match)
                            ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400'
                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-gray-900 dark:hover:text-gray-100',
                        collapsed ? 'justify-center' : '',
                    ]"
                    :title="collapsed ? item.label : undefined"
                >
                    <component :is="item.icon" class="h-5 w-5 shrink-0" />
                    <span v-if="!collapsed" class="truncate">{{
                        item.label
                    }}</span>
                </Link>
            </nav>

            <!-- PWA Install -->
            <div v-if="canInstallApp" class="mt-4 px-2">
                <button
                    type="button"
                    @click="handleInstallApp"
                    :class="[
                        'flex w-full items-center gap-3 rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2.5 text-sm font-semibold text-indigo-700 hover:bg-indigo-100 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-300',
                        collapsed ? 'justify-center' : '',
                    ]"
                >
                    <Download class="h-5 w-5 shrink-0" />
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
                        'flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-900',
                        collapsed ? 'justify-center' : '',
                    ]"
                >
                    <Search class="h-5 w-5 shrink-0" />
                    <span v-if="!collapsed">Search</span>
                </button>

                <!-- Theme -->
                <button
                    type="button"
                    @click="toggle"
                    :class="[
                        'flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-900',
                        collapsed ? 'justify-center' : '',
                    ]"
                >
                    <Monitor
                        v-if="theme === 'system'"
                        class="h-5 w-5 shrink-0"
                    />
                    <Sun
                        v-else-if="theme === 'light'"
                        class="h-5 w-5 shrink-0"
                    />
                    <Moon v-else class="h-5 w-5 shrink-0" />
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
                        <LogIn class="h-4 w-4 shrink-0" />
                        <span v-if="!collapsed">Login</span>
                    </Link>
                    <div
                        v-else
                        :class="[
                            'flex items-center gap-3 rounded-xl border bg-slate-50 px-3 py-2.5 dark:border-gray-800 dark:bg-gray-900',
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
                            class="shrink-0 rounded-lg p-1.5 text-slate-500 hover:bg-white dark:hover:bg-gray-800"
                            :title="isAdminRoute ? 'Home' : 'Staff Panel'"
                        >
                            <LayoutDashboard class="h-4 w-4" />
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
