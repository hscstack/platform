<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Download,
    LayoutDashboard,
    LogIn,
    LogOut,
    Menu,
    Monitor,
    Moon,
    Search,
    Sun,
    X,
    Home,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import AppLogo from '@/components/AppLogo.vue';
import AuthModal from '@/components/AuthModal.vue';
import NotificationDropdown from '@/components/NotificationDropdown.vue';
import { overflowNavItems } from '@/lib/navigation';
import { useDarkMode } from '@/lib/useDarkMode';
import { usePwa } from '@/lib/usePwa';

const props = defineProps<{ open: boolean }>();
const emit = defineEmits<{
    (e: 'update:open', v: boolean): void;
    (e: 'close'): void;
}>();

const page = usePage();
const user = computed(
    () => page.props.auth?.user as App.Data.UserData | undefined,
);
const canAccessAdmin = computed(() =>
    Boolean(page.props.auth?.can_access_admin),
);
const isAdminRoute = computed(() => String(page.url).startsWith('/admin'));
const currentUrl = computed(() => String(page.url));

const { theme, setTheme } = useDarkMode();
const { deferredPrompt, isInstalled, promptInstall } = usePwa();
const canInstallApp = computed(
    () => !isInstalled.value && Boolean(deferredPrompt.value),
);

const showAuthModal = ref(false);
const authModalMessage = ref('');

const close = () => {
    emit('update:open', false);
    emit('close');
};

const handleInstallApp = async () => {
    await promptInstall();
    close();
};

const isActive = (href: string, match?: (url: string) => boolean) => {
    if (match) {
        return match(currentUrl.value);
    }

    return currentUrl.value.startsWith(href);
};

watch(
    () => props.open,
    (v) => {
        if (typeof document === 'undefined') {
            return;
        }

        document.body.style.overflow = v ? 'hidden' : '';
    },
);
</script>

<template>
    <div>
        <!-- Top bar hamburger (portrait only) -->
        <div
            class="sticky top-0 z-30 flex h-14 items-center gap-3 border-b border-slate-200 bg-white/80 px-4 backdrop-blur-xl landscape:hidden dark:border-gray-800 dark:bg-gray-950/80"
        >
            <button
                type="button"
                @click="emit('update:open', true)"
                class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                aria-label="Open navigation menu"
            >
                <Menu class="h-5 w-5" />
            </button>
            <AppLogo />
            <div class="ml-auto flex items-center gap-2">
                <button
                    type="button"
                    @click="
                        () => {
                            if (!user) {
                                authModalMessage = 'Please login to search';
                                showAuthModal = true;
                                return;
                            }
                            window.dispatchEvent(
                                new CustomEvent('hscstack:trigger-search'),
                            );
                        }
                    "
                    class="flex h-9 w-9 items-center justify-center rounded-full text-slate-600 hover:bg-slate-100 dark:text-gray-300 dark:hover:bg-gray-800"
                    aria-label="Search"
                >
                    <Search class="h-5 w-5" />
                </button>
                <NotificationDropdown v-if="user" />
            </div>
        </div>

        <AuthModal
            v-model="showAuthModal"
            title="Sign in required"
            :message="authModalMessage"
        />

        <!-- Drawer -->
        <Teleport to="body">
            <div v-if="open" class="fixed inset-0 z-50 flex landscape:hidden">
                <div
                    class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm dark:bg-black/60"
                    @click="close"
                />
                <Transition
                    appear
                    enter-active-class="transition-transform duration-300"
                    enter-from-class="-translate-x-full"
                    enter-to-class="translate-x-0"
                    leave-active-class="transition-transform duration-200"
                    leave-from-class="translate-x-0"
                    leave-to-class="-translate-x-full"
                >
                    <aside
                        class="relative flex h-full w-[86%] max-w-xs flex-col bg-white shadow-2xl dark:bg-gray-950"
                    >
                        <div
                            class="flex items-center justify-between border-b border-slate-100 p-4 dark:border-gray-800"
                        >
                            <AppLogo />
                            <button
                                @click="close"
                                class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-gray-800"
                                aria-label="Close menu"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <div class="flex-1 overflow-y-auto p-4">
                            <!-- User -->
                            <div
                                v-if="user"
                                class="mb-4 flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-3 dark:border-gray-800 dark:bg-gray-900"
                            >
                                <img
                                    v-if="user.image_url"
                                    :src="user.image_url"
                                    :alt="user.name"
                                    class="h-10 w-10 rounded-full object-cover"
                                />
                                <span
                                    v-else
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-600 text-sm font-bold text-white"
                                    >{{
                                        user.name.charAt(0).toUpperCase()
                                    }}</span
                                >
                                <div class="min-w-0">
                                    <p
                                        class="truncate text-sm font-semibold text-slate-900 dark:text-gray-100"
                                    >
                                        {{ user.name }}
                                    </p>
                                    <p class="truncate text-xs text-slate-500">
                                        {{ user.email }}
                                    </p>
                                </div>
                            </div>
                            <Link
                                v-else
                                href="/login"
                                @click="close"
                                class="mb-4 flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-3 text-sm font-bold text-white"
                            >
                                <LogIn class="h-4 w-4" /> Sign in
                            </Link>

                            <!-- Overflow items -->
                            <p
                                class="mb-2 px-2 text-[11px] font-bold tracking-widest text-slate-400 uppercase"
                            >
                                More
                            </p>
                            <nav class="space-y-1">
                                <Link
                                    v-for="item in overflowNavItems"
                                    :key="item.href"
                                    :href="item.href"
                                    @click="close"
                                    :class="[
                                        'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium',
                                        isActive(item.href, item.match)
                                            ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400'
                                            : 'text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-900',
                                    ]"
                                >
                                    <component
                                        :is="item.icon"
                                        class="h-5 w-5"
                                    />
                                    <span>{{ item.label }}</span>
                                </Link>
                            </nav>

                            <!-- Install -->
                            <button
                                v-if="canInstallApp"
                                type="button"
                                @click="handleInstallApp"
                                class="mt-4 flex w-full items-center gap-3 rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2.5 text-sm font-semibold text-indigo-700 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-300"
                            >
                                <Download class="h-5 w-5" /> Install App
                            </button>

                            <!-- Admin / Profile -->
                            <div
                                v-if="user"
                                class="mt-6 space-y-1 border-t border-slate-100 pt-4 dark:border-gray-800"
                            >
                                <Link
                                    :href="
                                        user.username
                                            ? `/u/${user.username}`
                                            : '/profile'
                                    "
                                    @click="close"
                                    class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-900"
                                >
                                    <Home class="h-4 w-4" /> Profile
                                </Link>
                                <Link
                                    v-if="canAccessAdmin"
                                    :href="isAdminRoute ? '/' : '/admin'"
                                    @click="close"
                                    class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-900"
                                >
                                    <LayoutDashboard class="h-4 w-4" />
                                    {{ isAdminRoute ? 'Home' : 'Staff Panel' }}
                                </Link>
                                <Link
                                    href="/logout"
                                    method="post"
                                    as="button"
                                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40"
                                >
                                    <LogOut class="h-4 w-4" /> Sign out
                                </Link>
                            </div>

                            <!-- Theme -->
                            <div class="mt-6">
                                <p
                                    class="mb-2 px-2 text-[11px] font-bold tracking-widest text-slate-400 uppercase"
                                >
                                    Appearance
                                </p>
                                <div
                                    class="grid grid-cols-3 gap-1 rounded-xl bg-slate-100 p-1 dark:bg-gray-900"
                                >
                                    <button
                                        type="button"
                                        @click="setTheme('light')"
                                        :class="[
                                            'rounded-lg py-2 text-xs font-semibold',
                                            theme === 'light'
                                                ? 'bg-white shadow dark:bg-gray-800'
                                                : 'text-slate-500',
                                        ]"
                                    >
                                        <span
                                            class="flex items-center justify-center gap-1"
                                            ><Sun class="h-3.5 w-3.5" />
                                            Light</span
                                        >
                                    </button>
                                    <button
                                        type="button"
                                        @click="setTheme('dark')"
                                        :class="[
                                            'rounded-lg py-2 text-xs font-semibold',
                                            theme === 'dark'
                                                ? 'bg-white shadow dark:bg-gray-800'
                                                : 'text-slate-500',
                                        ]"
                                    >
                                        <span
                                            class="flex items-center justify-center gap-1"
                                            ><Moon class="h-3.5 w-3.5" />
                                            Dark</span
                                        >
                                    </button>
                                    <button
                                        type="button"
                                        @click="setTheme('system')"
                                        :class="[
                                            'rounded-lg py-2 text-xs font-semibold',
                                            theme === 'system'
                                                ? 'bg-white shadow dark:bg-gray-800'
                                                : 'text-slate-500',
                                        ]"
                                    >
                                        <span
                                            class="flex items-center justify-center gap-1"
                                            ><Monitor class="h-3.5 w-3.5" />
                                            System</span
                                        >
                                    </button>
                                </div>
                            </div>
                        </div>
                    </aside>
                </Transition>
            </div>
        </Teleport>
    </div>
</template>
