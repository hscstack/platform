<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    LogOut,
    LayoutDashboard,
    Home,
    ChevronDown,
    Moon,
    Sun,
    Monitor,
} from 'lucide-vue-next';
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';
import { useDarkMode } from '@/lib/useDarkMode';
import AppLogo from './AppLogo.vue';
import { kNavbar, kButton, kList, kListItem } from 'konsta/vue';

defineProps({
    isAdmin: {
        type: Boolean,
        default: false,
    },
});

const { theme, toggle } = useDarkMode();

const user = computed(() => usePage().props.auth?.user);

const dropdownOpen = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);

const toggleDropdown = () => {
    dropdownOpen.value = !dropdownOpen.value;
};

const closeDropdown = () => {
    dropdownOpen.value = false;
};

const handleLogout = () => {
    router.post('/logout');
};

const handleClickOutside = (e: MouseEvent) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target as Node)) {
        closeDropdown();
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
});
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
                <div v-else ref="dropdownRef" class="relative">
                    <k-button @click="toggleDropdown" clear>
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

                        <span class="hidden sm:inline">{{ user.name }}</span>

                        <ChevronDown
                            class="h-3.5 w-3.5 text-slate-400 transition-transform"
                            :class="{ 'rotate-180': dropdownOpen }"
                        />
                    </k-button>

                    <Transition
                        enter-active-class="transition duration-150 ease-out"
                        enter-from-class="scale-95 opacity-0"
                        enter-to-class="scale-100 opacity-100"
                        leave-active-class="transition duration-100 ease-in"
                        leave-from-class="scale-100 opacity-100"
                        leave-to-class="scale-95 opacity-0"
                    >
                        <div
                            v-if="dropdownOpen"
                            class="absolute right-0 mt-2 w-48 origin-top-right rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg dark:border-gray-700 dark:bg-gray-900"
                        >
                            <div
                                class="border-b border-slate-100 px-3 py-2 dark:border-gray-700"
                            >
                                <p
                                    class="text-sm font-semibold text-slate-900 dark:text-gray-100"
                                >
                                    {{ user.name }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ user.email }}
                                </p>
                            </div>

                            <k-list class="my-0 !outline-none">
                                <k-list-item
                                    :component="Link"
                                    :href="isAdmin ? '/' : '/admin'"
                                    @click="closeDropdown"
                                >
                                    <template #media>
                                        <component
                                            :is="
                                                isAdmin ? Home : LayoutDashboard
                                            "
                                            class="h-4 w-4 text-slate-400"
                                        />
                                    </template>
                                    <template #title>
                                        <span
                                            class="text-sm font-medium text-slate-600 dark:text-gray-400"
                                        >
                                            {{
                                                isAdmin ? 'Home' : 'Staff Panel'
                                            }}
                                        </span>
                                    </template>
                                </k-list-item>

                                <k-list-item @click="handleLogout">
                                    <template #media>
                                        <LogOut class="h-4 w-4 text-red-500" />
                                    </template>
                                    <template #title>
                                        <span
                                            class="text-sm font-medium text-red-600"
                                        >
                                            Logout
                                        </span>
                                    </template>
                                </k-list-item>
                            </k-list>
                        </div>
                    </Transition>
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
