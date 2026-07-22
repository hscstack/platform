<script setup lang="ts">
import { LayoutDashboard, Users, BookOpen, Bell, Book, User } from 'lucide-vue-next';
import { ref } from 'vue';
import DesktopSidebar from '@/components/admin/DesktopSidebar.vue';
import MobileSideBar from '@/components/admin/MobileSideBar.vue';
import NavBar from '@/components/NavBar.vue';
import ToastNotification from '@/components/ToastNotification.vue';
import { usePage } from '@inertiajs/vue3';

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
    <div
        class="relative min-h-screen bg-slate-50 font-sans text-slate-900 antialiased selection:bg-indigo-600 selection:text-white"
    >
        <div class="pointer-events-none fixed inset-0 z-0 overflow-hidden">
            <div
                class="absolute -top-[30%] left-1/2 h-[900px] w-[1200px] -translate-x-1/2 rounded-full bg-[radial-gradient(ellipse_at_center,rgba(99,102,241,0.18)_0%,rgba(165,180,252,0.05)_50%,transparent_70%)] blur-[120px]"
            ></div>
            <div
                class="absolute top-[20%] -right-[10%] h-[600px] w-[600px] rounded-full bg-[radial-gradient(circle_at_center,rgba(56,189,248,0.15)_0%,transparent_65%)] blur-[100px]"
            ></div>
            <div
                class="absolute -bottom-[10%] -left-[10%] h-[700px] w-[700px] rounded-full bg-[radial-gradient(circle_at_center,rgba(244,63,94,0.06)_0%,transparent_70%)] blur-[110px]"
            ></div>
        </div>

        <div class="relative z-10 flex min-h-screen flex-col">
            <NavBar :is-admin="true" />

            <div
                class="flex items-center gap-3 border-b border-slate-200/80 bg-white/60 px-4 py-3 backdrop-blur-md md:hidden"
            >
                <button
                    @click="openMobileSidebar"
                    class="rounded-lg p-1.5 text-slate-600 hover:bg-slate-100"
                >
                    ☰
                </button>
                <span
                    class="text-xs font-semibold tracking-wider text-slate-400 uppercase"
                    >Dashboard Menu</span
                >
            </div>

            <div class="flex flex-1">
                <aside
                    class="hidden w-64 border-r border-slate-200/80 bg-white/40 backdrop-blur-md md:block"
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

                <main class="flex flex-1 flex-col overflow-x-hidden p-6 lg:p-8">
                    <slot />
                </main>
            </div>
        </div>
        <ToastNotification />
    </div>
</template>
