<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { kNavbar, kButton, kList, kListItem, kPopover } from 'konsta/vue';
import {
    LogOut,
    LayoutDashboard,
    Home,
    Moon,
    Sun,
    Monitor,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useDarkMode } from '@/lib/useDarkMode';
import AppLogo from './AppLogo.vue';

defineProps({
    isAdmin: {
        type: Boolean,
        default: false,
    },
});

const { theme, toggle } = useDarkMode();

const user = computed(() => usePage().props.auth?.user);

const showUserMenu = ref(false);

const handleLogout = () => {
    router.post('/logout');
};
</script>

<template>
    <k-navbar class="sticky top-0 z-50">
        <template #left>
            <div class="flex items-center gap-2">
                <AppLogo />

                <span
                    v-if="isAdmin"
                    class="rounded bg-slate-100 px-2 py-0.5 text-xs font-semibold tracking-wider text-slate-400 uppercase dark:bg-gray-800 dark:text-gray-500"
                >
                    Admin
                </span>
            </div>
        </template>

        <template #right>
            <div class="flex items-center gap-4">
                <Link
                    href="/blogs"
                    class="text-sm font-medium text-slate-600 transition-colors hover:text-slate-900 dark:text-gray-400 dark:hover:text-gray-100"
                >
                    Blogs
                </Link>

                <!-- Guest: Login link -->
                <Link
                    v-if="!user"
                    href="/admin"
                    class="text-sm font-medium text-slate-600 transition-colors hover:text-slate-900 dark:text-gray-400 dark:hover:text-gray-100"
                >
                    Login
                </Link>

                <!-- Logged in: User dropdown -->
                <div v-else class="relative">
                    <k-popover
                        :opened="showUserMenu"
                        @close="showUserMenu = false"
                    >
                        <template #target>
                            <k-button
                                @click="showUserMenu = !showUserMenu"
                                clear
                            >
                                <img
                                    v-if="user.image_url"
                                    :src="user.image_url"
                                    :alt="user.name"
                                    class="h-7 w-7 rounded-full object-cover"
                                />
                                <span
                                    v-else
                                    class="flex h-7 w-7 items-center justify-center rounded-full bg-slate-900 text-xs font-bold text-white dark:bg-gray-700"
                                >
                                    {{ user.name.charAt(0).toUpperCase() }}
                                </span>

                                <span class="hidden sm:inline">{{
                                    user.name
                                }}</span>
                            </k-button>
                        </template>

                        <k-list class="m-0">
                            <k-list-item
                                :title="user.name"
                                :subtitle="user.email"
                            >
                                <template #media>
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 dark:bg-gray-700"
                                    >
                                        <span
                                            class="text-sm font-bold text-slate-700 dark:text-gray-200"
                                        >
                                            {{
                                                user.name
                                                    .charAt(0)
                                                    .toUpperCase()
                                            }}
                                        </span>
                                    </div>
                                </template>
                            </k-list-item>

                            <k-list-item
                                :component="Link"
                                :href="isAdmin ? '/' : '/admin'"
                                @click="showUserMenu = false"
                                :title="isAdmin ? 'Home' : 'Staff Panel'"
                            >
                                <template #media>
                                    <component
                                        :is="isAdmin ? Home : LayoutDashboard"
                                        class="h-4 w-4 text-slate-400"
                                    />
                                </template>
                            </k-list-item>

                            <k-list-item
                                link="#"
                                @click="
                                    handleLogout();
                                    showUserMenu = false;
                                "
                                title="Logout"
                            >
                                <template #media>
                                    <LogOut class="h-4 w-4 text-red-500" />
                                </template>
                            </k-list-item>
                        </k-list>
                    </k-popover>
                </div>

                <!-- Dark mode -->
                <k-button
                    @click="toggle"
                    clear
                    icon-only
                    :title="
                        theme === 'system'
                            ? 'System theme (click to cycle)'
                            : theme === 'dark'
                              ? 'Dark mode (click to cycle)'
                              : 'Light mode (click to cycle)'
                    "
                >
                    <Monitor v-if="theme === 'system'" class="h-4 w-4" />
                    <Sun v-else-if="theme === 'light'" class="h-4 w-4" />
                    <Moon v-else class="h-4 w-4" />
                </k-button>
            </div>
        </template>
    </k-navbar>
</template>
