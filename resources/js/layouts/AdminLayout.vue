<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { kPage } from 'konsta/vue';
import {
    LayoutDashboard,
    Users,
    BookOpen,
    Bell,
    Book,
    User,
} from 'lucide-vue-next';
import { ref } from 'vue';
import DesktopSidebar from '@/components/admin/DesktopSidebar.vue';
import MobileSideBar from '@/components/admin/MobileSideBar.vue';
import KonstaProvider from '@/components/KonstaProvider.vue';
import LoadingSpinner from '@/components/LoadingSpinner.vue';
import NavBar from '@/components/NavBar.vue';
import ToastNotification from '@/components/ToastNotification.vue';

const isMobileSidebarOpen = ref(false);
const myId = usePage().props.auth.user.id;
const navigation = [
    { name: 'Dashboard', to: '/admin', icon: LayoutDashboard },
    { name: 'Manage Contents', to: '/admin/subjects', icon: BookOpen },
    { name: 'Manage Blogs', to: '/admin/blogs', icon: Book },
    { name: 'Site Notice', to: '/admin/notice', icon: Bell },
    { name: 'Users', to: '/admin/users', icon: Users },
    { name: 'My Profile', to: `/admin/users/edit/${myId}`, icon: User },
];

const openMobileSidebar = () => {
    isMobileSidebarOpen.value = true;
};

const closeMobileSidebar = () => {
    isMobileSidebarOpen.value = false;
};
</script>

<template>
    <KonstaProvider>
        <LoadingSpinner />
        <div
            class="relative min-h-screen bg-slate-50 font-sans text-slate-900 antialiased selection:bg-indigo-600 selection:text-white dark:bg-gray-950 dark:text-gray-100"
        >
            <div class="pointer-events-none fixed inset-0 z-0 overflow-hidden">
                <div
                    class="absolute -top-[30%] left-1/2 h-[900px] w-[1200px] -translate-x-1/2 rounded-full bg-[radial-gradient(ellipse_at_center,rgba(99,102,241,0.18)_0%,rgba(165,180,252,0.05)_50%,transparent_70%)] blur-[120px] dark:bg-[radial-gradient(ellipse_at_center,rgba(99,102,241,0.06)_0%,rgba(165,180,252,0.02)_50%,transparent_70%)]"
                ></div>
                <div
                    class="absolute top-[20%] -right-[10%] h-[600px] w-[600px] rounded-full bg-[radial-gradient(circle_at_center,rgba(56,189,248,0.15)_0%,transparent_65%)] blur-[100px] dark:bg-[radial-gradient(circle_at_center,rgba(56,189,248,0.05)_0%,transparent_65%)]"
                ></div>
                <div
                    class="absolute -bottom-[10%] -left-[10%] h-[700px] w-[700px] rounded-full bg-[radial-gradient(circle_at_center,rgba(244,63,94,0.06)_0%,transparent_70%)] blur-[110px] dark:bg-[radial-gradient(circle_at_center,rgba(244,63,94,0.02)_0%,transparent_70%)]"
                ></div>
            </div>

            <kPage>
                <NavBar :is-admin="true" />

                <div
                    class="flex items-center gap-3 border-b border-slate-200/80 bg-white/60 px-4 py-3 backdrop-blur-md lg:hidden dark:border-gray-700/80 dark:bg-gray-900/60"
                >
                    <button
                        @click="openMobileSidebar"
                        class="rounded-lg p-1.5 text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800"
                    >
                        ☰
                    </button>
                    <span
                        class="text-xs font-semibold tracking-wider text-slate-400 uppercase dark:text-gray-500"
                        >Dashboard Menu</span
                    >
                </div>

                <div class="flex flex-1 gap-6 p-4 lg:p-6">
                    <aside
                        class="sticky top-20 hidden h-[calc(100vh-5rem)] w-64 shrink-0 overflow-y-auto rounded-2xl border border-slate-200/80 bg-white/70 p-4 shadow-lg shadow-slate-200/50 backdrop-blur-xl lg:block dark:border-gray-700/60 dark:bg-gray-900/70 dark:shadow-none dark:ring-1 dark:ring-white/5"
                    >
                        <DesktopSidebar :navigation="navigation" />
                    </aside>

                    <MobileSideBar
                        :navigation="navigation"
                        :is-open="isMobileSidebarOpen"
                        @close="closeMobileSidebar"
                    />

                    <main class="flex flex-1 flex-col overflow-x-hidden">
                        <slot />
                    </main>
                </div>
            </kPage>
            <ToastNotification />
        </div>
    </KonstaProvider>
</template>
