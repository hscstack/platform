<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

import AppLogo from '@/components/AppLogo.vue';
import LogoutConfirmModal from '@/components/LogoutConfirmModal.vue';
import NotificationDropdown from '@/components/NotificationDropdown.vue';
import MaterialIcon from '@/components/ui/MaterialIcon.vue';
import { useBottomNavCustomization } from '@/lib/useBottomNavCustomization';
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
const { availableItems } = useBottomNavCustomization();

const showLogoutModal = ref(false);

const close = () => {
    emit('update:open', false);
    emit('close');
};

const askLogout = () => {
    close();
    showLogoutModal.value = true;
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

let previousOverflow: string | null = null;

watch(
    () => props.open,
    (v) => {
        if (typeof document === 'undefined') {
            return;
        }

        if (v) {
            previousOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = previousOverflow ?? '';
            previousOverflow = null;
        }
    },
);

onBeforeUnmount(() => {
    if (typeof document === 'undefined') {
        return;
    }

    if (previousOverflow !== null) {
        document.body.style.overflow = previousOverflow;
        previousOverflow = null;
    } else if (props.open) {
        document.body.style.overflow = '';
    }
});
</script>

<template>
    <div>
        <!-- Top bar hamburger (mobile) — YT-style: compact, hamburger left -->
        <div
            class="sticky top-0 z-30 flex h-[56px] items-center gap-2.5 border-b border-slate-200/70 bg-white/90 px-3.5 backdrop-blur-xl dark:border-slate-800 dark:bg-slate-900/90"
        >
            <button
                type="button"
                @click="emit('update:open', true)"
                class="flex h-9 w-9 items-center justify-center rounded-full text-slate-700 transition-colors hover:bg-slate-100 active:bg-slate-200 dark:text-slate-300 dark:hover:bg-slate-800"
                aria-label="Open navigation menu"
            >
                <MaterialIcon name="menu" :size="24" />
            </button>
            <div class="ml-1">
                <AppLogo />
            </div>
            <div class="ml-auto flex items-center gap-1.5">
                <NotificationDropdown v-if="user" />
            </div>
        </div>

        <!-- Drawer -->
        <Teleport to="body">
            <div v-if="open" class="fixed inset-0 z-50 flex">
                <div
                    class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm dark:bg-black/60"
                    @click="close"
                />
                <Transition
                    appear
                    enter-active-class="transition-transform duration-300 ease-[cubic-bezier(0.32,0.72,0,1)]"
                    enter-from-class="-translate-x-full"
                    enter-to-class="translate-x-0"
                    leave-active-class="transition-transform duration-200 ease-in"
                    leave-from-class="translate-x-0"
                    leave-to-class="-translate-x-full"
                >
                    <aside
                        class="relative flex h-full w-[84%] max-w-[320px] flex-col border-r border-slate-200/60 bg-white/85 shadow-[8px_0_32px_rgba(0,0,0,0.12)] backdrop-blur-xl dark:border-slate-800/60 dark:bg-slate-900/70 dark:backdrop-blur-xl"
                    >
                        <div
                            class="flex h-16 shrink-0 items-center justify-between border-b border-slate-200/60 px-4 dark:border-slate-800/60"
                        >
                            <AppLogo />
                            <button
                                @click="close"
                                class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm transition-all duration-150 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:bg-slate-700 dark:hover:text-slate-100"
                                aria-label="Close menu"
                            >
                                <MaterialIcon name="close" :size="18" />
                            </button>
                        </div>

                        <div class="flex-1 overflow-y-auto py-3.5">
                            <!-- Overflow items (rest not in bottom nav) -->
                            <p
                                class="mb-2 px-4 text-[10px] font-bold tracking-[0.12em] text-slate-400 uppercase dark:text-slate-500"
                            >
                                More
                            </p>
                            <nav class="space-y-0.5 px-2.5">
                                <Link
                                    v-for="item in availableItems"
                                    :key="item.href"
                                    :href="item.href"
                                    @click="close"
                                    :class="[
                                        'group flex items-center gap-2.5 rounded-[10px] px-2.5 py-2 text-[13px] font-medium tracking-tight transition-all duration-150 ease-out',
                                        isActive(item.href, item.match)
                                            ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200/60 dark:bg-indigo-500/10 dark:text-indigo-200 dark:ring-indigo-500/20'
                                            : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/70 dark:hover:text-slate-100',
                                    ]"
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
                                    <span class="truncate">{{
                                        item.label
                                    }}</span>
                                    <span
                                        v-if="isActive(item.href, item.match)"
                                        class="ml-auto h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-600 dark:bg-indigo-400"
                                    />
                                </Link>
                                <p
                                    v-if="availableItems.length === 0"
                                    class="px-3 py-2 text-xs text-slate-500 dark:text-gray-400"
                                >
                                    All items are in your bottom bar. Customize
                                    in Profile → Bottom navigation.
                                </p>
                            </nav>

                            <!-- Install -->
                            <div class="px-2.5">
                                <button
                                    v-if="canInstallApp"
                                    type="button"
                                    @click="handleInstallApp"
                                    class="mt-4 flex h-11 w-full items-center justify-center gap-2.5 rounded-xl border border-indigo-200 bg-indigo-50 px-3 text-[13px] font-bold text-indigo-700 shadow-sm transition-all duration-150 hover:bg-indigo-100 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-300 dark:hover:bg-indigo-500/20"
                                >
                                    <MaterialIcon name="download" :size="20" />
                                    Install App
                                </button>
                            </div>

                            <!-- Theme -->
                            <div class="mt-4 px-2.5">
                                <p
                                    class="mb-2 px-2 text-[11px] font-bold tracking-widest text-slate-400 uppercase dark:text-gray-500"
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
                                            ><MaterialIcon
                                                name="light_mode"
                                                :size="16"
                                            />
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
                                            ><MaterialIcon
                                                name="dark_mode"
                                                :size="16"
                                            />
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
                                            ><MaterialIcon
                                                name="computer"
                                                :size="16"
                                            />
                                            System</span
                                        >
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Account footer (fixed, desktop-style card) -->
                        <div
                            class="shrink-0 border-t border-slate-200/60 bg-white/40 p-2 backdrop-blur-sm dark:border-slate-800/60 dark:bg-slate-900/40 dark:backdrop-blur-sm"
                        >
                            <div
                                v-if="user"
                                class="flex items-center gap-2.5 rounded-xl border bg-white px-3 py-2.5 shadow-sm dark:border-slate-700 dark:bg-slate-800"
                            >
                                <Link
                                    :href="
                                        user.username
                                            ? `/u/${user.username}`
                                            : '/profile'
                                    "
                                    @click="close"
                                    class="flex min-w-0 flex-1 items-center gap-2.5"
                                    title="Profile"
                                    aria-label="Profile"
                                >
                                    <img
                                        v-if="user.image_url"
                                        :src="user.image_url"
                                        :alt="user.name"
                                        class="h-9 w-9 shrink-0 rounded-full object-cover ring-1 ring-slate-200 dark:ring-slate-700"
                                    />
                                    <span
                                        v-else
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-indigo-600 to-violet-600 text-xs font-bold text-white ring-1 ring-indigo-600/20"
                                        >{{
                                            user.name.charAt(0).toUpperCase()
                                        }}</span
                                    >
                                    <span class="min-w-0 text-left">
                                        <span
                                            class="block truncate text-sm font-semibold text-slate-900 dark:text-slate-100"
                                        >
                                            {{ user.name }}
                                        </span>
                                        <span
                                            class="block truncate text-xs text-slate-500 dark:text-slate-400"
                                        >
                                            {{ user.email }}
                                        </span>
                                    </span>
                                </Link>
                                <Link
                                    v-if="canAccessAdmin"
                                    :href="isAdminRoute ? '/' : '/admin'"
                                    @click="close"
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-50 text-slate-500 transition-colors hover:bg-white hover:text-slate-900 hover:shadow-sm dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-100"
                                    :title="
                                        isAdminRoute ? 'Home' : 'Staff Panel'
                                    "
                                    :aria-label="
                                        isAdminRoute ? 'Home' : 'Staff Panel'
                                    "
                                >
                                    <MaterialIcon name="dashboard" :size="20" />
                                </Link>
                                <button
                                    type="button"
                                    @click="askLogout"
                                    title="Log out"
                                    aria-label="Log out"
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-rose-500 transition-colors hover:bg-rose-50 hover:text-rose-600 hover:shadow-sm dark:text-rose-400 dark:hover:bg-rose-950/40 dark:hover:text-rose-300"
                                >
                                    <MaterialIcon name="logout" :size="20" />
                                </button>
                            </div>
                            <Link
                                v-else
                                href="/login"
                                @click="close"
                                class="flex h-11 w-full items-center justify-center gap-2.5 rounded-xl border border-transparent bg-indigo-600 px-3 text-[13px] font-bold text-white shadow-sm transition-all duration-150 hover:bg-indigo-700 hover:shadow-md dark:bg-indigo-500 dark:hover:bg-indigo-400"
                            >
                                <MaterialIcon name="login" :size="20" />
                                Sign in
                            </Link>
                        </div>
                    </aside>
                </Transition>
            </div>
        </Teleport>

        <LogoutConfirmModal v-model:open="showLogoutModal" />
    </div>
</template>
