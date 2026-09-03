<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import MobileSideBar from '@/components/admin/MobileSideBar.vue';
import AppLogo from '@/components/AppLogo.vue';
import LoadingSpinner from '@/components/LoadingSpinner.vue';
import AdminSideRail from '@/components/navigation/AdminSideRail.vue';
import type { AdminNavItem } from '@/components/navigation/AdminSideRail.vue';
import BottomNav from '@/components/navigation/BottomNav.vue';
import NotificationDropdown from '@/components/NotificationDropdown.vue';
import ToastNotification from '@/components/ToastNotification.vue';
import MaterialIcon from '@/components/ui/MaterialIcon.vue';
import { usePermissions } from '@/lib/usePermissions';

const { can } = usePermissions();
const page = usePage();
const user = computed(
    () => (page.props.auth as any)?.user as App.Data.UserData | undefined,
);

const isMobileSidebarOpen = ref(false);
const adminCollapsed = ref(false);

if (typeof window !== 'undefined') {
    try {
        const saved = localStorage.getItem('admin_rail_collapsed');

        if (saved !== null) {
            adminCollapsed.value = saved === 'true';
        }
    } catch {}
}

watch(adminCollapsed, (v) => {
    try {
        localStorage.setItem('admin_rail_collapsed', String(v));
    } catch {}
});

const allNavigation: AdminNavItem[] = [
    { name: 'Dashboard', to: '/admin', icon: 'dashboard' },
    { name: 'Manage Contents', to: '/admin/subjects', icon: 'menu_book' },
    { name: 'Manage Blogs', to: '/admin/blogs', icon: 'book' },
    {
        name: 'Manage Forum',
        to: '/admin/forums',
        icon: 'forum',
        permission: 'manage forums',
    },
    {
        name: 'Support Tickets',
        to: '/admin/tickets',
        icon: 'confirmation_number',
        permission: 'manage tickets',
    },
    {
        name: 'Site Notice',
        to: '/admin/notice',
        icon: 'notifications',
        permission: 'edit notice',
    },
    {
        name: 'Global Chat',
        to: '/admin/chat',
        icon: 'chat',
        permission: 'manage chat',
    },
    {
        name: 'Users',
        to: '/admin/users',
        icon: 'group',
        permission: 'view users',
    },
    {
        name: 'Send Emails',
        to: '/admin/emails/send',
        icon: 'mail',
        permission: 'send email',
    },
];

const navigation = computed(() =>
    allNavigation.filter((item) => !item.permission || can(item.permission)),
);

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
        <div class="flex min-h-screen">
            <!-- Desktop staff rail — unified with site SideRail -->
            <AdminSideRail
                :navigation="navigation"
                :collapsed="adminCollapsed"
                class="hidden md:flex"
                @toggle="adminCollapsed = !adminCollapsed"
            />

            <div class="flex min-h-screen flex-1 flex-col">
                <!-- Single mobile top bar (replaces old NavBar + subheader duplication) -->
                <div
                    class="sticky top-0 z-30 flex h-[56px] items-center gap-2.5 border-b border-slate-200/70 bg-white/90 px-3.5 backdrop-blur-xl md:hidden dark:border-slate-800 dark:bg-slate-900/90"
                >
                    <button
                        type="button"
                        @click="openMobileSidebar"
                        class="flex h-9 w-9 items-center justify-center rounded-full text-slate-700 transition-colors hover:bg-slate-100 active:bg-slate-200 dark:text-slate-300 dark:hover:bg-slate-800"
                        aria-label="Open staff menu"
                    >
                        <MaterialIcon name="menu" :size="24" />
                    </button>
                    <div class="ml-1">
                        <AppLogo />
                    </div>
                    <span
                        class="ml-1 rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-bold tracking-wider text-indigo-600 uppercase dark:bg-indigo-500/10 dark:text-indigo-300"
                    >
                        Staff
                    </span>
                    <div class="ml-auto flex items-center gap-1.5">
                        <NotificationDropdown v-if="user" />
                    </div>
                </div>

                <!-- Single mobile drawer (admin links only) -->
                <MobileSideBar
                    :navigation="navigation"
                    :is-open="isMobileSidebarOpen"
                    @close="closeMobileSidebar"
                />

                <!-- Main Content Area -->
                <main
                    class="flex flex-1 flex-col overflow-x-hidden p-3.5 pb-[5.5rem] sm:p-6 md:pb-6 lg:p-8"
                >
                    <div
                        class="flex w-full flex-1 flex-col rounded-xl border border-slate-200/90 bg-white p-4.5 shadow-2xs sm:p-7 md:p-8 dark:border-gray-800 dark:bg-gray-900"
                    >
                        <slot />
                    </div>
                </main>
            </div>
        </div>

        <!-- Site bottom nav on mobile (same as AppLayout) -->
        <div class="md:hidden">
            <BottomNav />
        </div>
        <ToastNotification />
    </div>
</template>
