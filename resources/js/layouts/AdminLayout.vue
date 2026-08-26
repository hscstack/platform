<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    Users,
    BookOpen,
    Bell,
    Book,
    Mail,
    Menu,
    ExternalLink,
    Sun,
    Moon,
    Monitor,
    LogOut,
    Database,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import DesktopSidebar from '@/components/admin/DesktopSidebar.vue';
import MobileSideBar from '@/components/admin/MobileSideBar.vue';
import AppLogo from '@/components/AppLogo.vue';
import LoadingSpinner from '@/components/LoadingSpinner.vue';
import ToastNotification from '@/components/ToastNotification.vue';
import { useDarkMode } from '@/lib/useDarkMode';

const { theme, toggle } = useDarkMode();
const page = usePage();
const user = computed(() => page.props.auth?.user);
const currentUrl = computed(() => page.url);

const isMobileSidebarOpen = ref(false);
const navigation = [
    { name: 'Dashboard', to: '/admin', icon: LayoutDashboard },
    { name: 'Manage Contents', to: '/admin/subjects', icon: BookOpen },
    { name: 'Manage Blogs', to: '/admin/blogs', icon: Book },
    { name: 'Site Notice', to: '/admin/notice', icon: Bell },
    { name: 'Users', to: '/admin/users', icon: Users },
    { name: 'Send Emails', to: '/admin/emails/send', icon: Mail },
];

const currentPageTitle = computed(() => {
    const matched = navigation.find((n) => currentUrl.value === n.to || (n.to !== '/admin' && currentUrl.value.startsWith(n.to)));
    return matched ? matched.name : 'Admin Panel';
});

const openMobileSidebar = () => {
    isMobileSidebarOpen.value = true;
};

const closeMobileSidebar = () => {
    isMobileSidebarOpen.value = false;
};
</script>

<template>
    <LoadingSpinner />
    <div
        class="min-h-screen bg-slate-100/70 font-sans text-slate-900 antialiased selection:bg-indigo-600 selection:text-white dark:bg-gray-950 dark:text-gray-100"
    >
        <div class="flex min-h-screen flex-col">
            <!-- Compact, Unified Admin Top Bar (52px height) -->
            <header
                class="sticky top-0 z-40 flex h-13 shrink-0 items-center justify-between border-b border-slate-200/90 bg-white/90 px-3.5 backdrop-blur-md sm:px-6 dark:border-gray-800 dark:bg-gray-900/90"
            >
                <!-- Left: Mobile Menu Toggle + App Logo + Section Badge -->
                <div class="flex items-center gap-3">
                    <button
                        @click="openMobileSidebar"
                        class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 md:hidden dark:text-gray-400 dark:hover:bg-gray-800"
                        title="Open menu"
                    >
                        <Menu class="h-4.5 w-4.5" />
                    </button>

                    <AppLogo />

                    <div class="hidden items-center gap-2 border-l border-slate-200 pl-3 sm:flex dark:border-gray-800">
                        <span
                            class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-[11px] font-bold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400"
                        >
                            {{ currentPageTitle }}
                        </span>
                    </div>
                </div>

                <!-- Right: Quick Actions & Profile -->
                <div class="flex items-center gap-2">
                    <!-- View Public Site Link -->
                    <Link
                        href="/"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                        title="Open live site"
                    >
                        <ExternalLink class="h-3.5 w-3.5" />
                        <span class="hidden sm:inline">View Site</span>
                    </Link>

                    <!-- Theme Toggle -->
                    <button
                        @click="toggle"
                        class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800"
                        :title="theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
                    >
                        <Sun v-if="theme === 'light'" class="h-4 w-4" />
                        <Moon v-else class="h-4 w-4" />
                    </button>

                    <div class="hidden h-4 w-px bg-slate-200 sm:block dark:bg-gray-800"></div>

                    <!-- User Pill -->
                    <div v-if="user" class="flex items-center gap-2">
                        <img
                            v-if="user.image_url"
                            :src="user.image_url"
                            :alt="user.name"
                            class="h-7 w-7 rounded-full object-cover ring-1 ring-slate-200 dark:ring-gray-700"
                        />
                        <span
                            v-else
                            class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-600 text-[11px] font-black text-white"
                        >
                            {{ user.name.charAt(0).toUpperCase() }}
                        </span>
                        <span class="hidden max-w-[120px] truncate text-xs font-semibold text-slate-800 sm:inline dark:text-gray-200">
                            {{ user.name }}
                        </span>
                    </div>
                </div>
            </header>

            <div class="flex flex-1">
                <!-- Desktop Sidebar -->
                <aside
                    class="hidden w-60 shrink-0 border-r border-slate-200/90 bg-white md:block dark:border-gray-800 dark:bg-gray-900"
                >
                    <div
                        class="sticky top-13 flex h-[calc(100vh-3.25rem)] flex-col justify-between p-3.5"
                    >
                        <DesktopSidebar :navigation="navigation" />
                    </div>
                </aside>

                <MobileSideBar
                    :navigation="navigation"
                    :is-open="isMobileSidebarOpen"
                    @close="closeMobileSidebar"
                />

                <!-- Main Content Area -->
                <main class="flex flex-1 flex-col overflow-x-hidden p-3.5 sm:p-5 lg:p-6">
                    <div
                        class="flex w-full flex-1 flex-col rounded-xl border border-slate-200/90 bg-white p-4 shadow-2xs sm:p-6 md:p-7 dark:border-gray-800 dark:bg-gray-900"
                    >
                        <slot />
                    </div>
                </main>
            </div>
        </div>
        <ToastNotification />
    </div>
</template>
