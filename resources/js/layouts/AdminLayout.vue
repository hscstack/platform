<script setup lang="ts">
import {
    LayoutDashboard,
    Users,
    BookOpen,
    Bell,
    Book,
    Mail,
} from 'lucide-vue-next';
import { ref } from 'vue';
import DesktopSidebar from '@/components/admin/DesktopSidebar.vue';
import MobileSideBar from '@/components/admin/MobileSideBar.vue';
import LoadingSpinner from '@/components/LoadingSpinner.vue';
import NavBar from '@/components/NavBar.vue';
import ToastNotification from '@/components/ToastNotification.vue';

const isMobileSidebarOpen = ref(false);
const navigation = [
    { name: 'Dashboard', to: '/admin', icon: LayoutDashboard },
    { name: 'Manage Contents', to: '/admin/subjects', icon: BookOpen },
    { name: 'Manage Blogs', to: '/admin/blogs', icon: Book },
    { name: 'Site Notice', to: '/admin/notice', icon: Bell },
    { name: 'Users', to: '/admin/users', icon: Users },
    { name: 'Send Emails', to: '/admin/emails/send', icon: Mail },
];

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
        class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased selection:bg-indigo-600 selection:text-white dark:bg-gray-950 dark:text-gray-100"
    >
        <div class="flex min-h-screen flex-col">
            <NavBar :is-admin="true" />

            <div
                class="flex items-center gap-3 border-b border-slate-200 bg-white px-4 py-2.5 md:hidden dark:border-gray-800 dark:bg-gray-900"
            >
                <button
                    @click="openMobileSidebar"
                    class="rounded-lg p-1 text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800"
                >
                    ☰
                </button>
                <span
                    class="text-xs font-semibold tracking-wider text-slate-500 uppercase dark:text-gray-400"
                    >Menu</span
                >
            </div>

            <div class="flex flex-1">
                <aside
                    class="hidden w-60 shrink-0 border-r border-slate-200 bg-white md:block dark:border-gray-800 dark:bg-gray-900"
                >
                    <div
                        class="sticky top-16 flex h-[calc(100vh-4rem)] flex-col justify-between p-4"
                    >
                        <DesktopSidebar :navigation="navigation" />
                    </div>
                </aside>

                <MobileSideBar
                    :navigation="navigation"
                    :is-open="isMobileSidebarOpen"
                    @close="closeMobileSidebar"
                />

                <main class="flex flex-1 flex-col overflow-x-hidden bg-white p-4 sm:p-6 lg:p-8 dark:bg-gray-900">
                    <slot />
                </main>
            </div>
        </div>
        <ToastNotification />
    </div>
</template>
