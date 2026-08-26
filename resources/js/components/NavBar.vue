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
    User,
    Menu,
    X,
    FolderGit2,
    BookOpen,
    LogIn,
    Shield,
} from 'lucide-vue-next';
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';
import { useDarkMode } from '@/lib/useDarkMode';
import AppLogo from './AppLogo.vue';

defineProps({
    isAdmin: {
        type: Boolean,
        default: false,
    },
});

const { theme, toggle } = useDarkMode();

const page = usePage();
const user = computed(() => page.props.auth?.user);
const canAccessAdmin = computed(() => page.props.auth?.can_access_admin);
const currentUrl = computed(() => page.url);

const isProjectsActive = computed(() =>
    currentUrl.value.startsWith('/projects'),
);
const isBlogsActive = computed(() => currentUrl.value.startsWith('/blogs'));

const dropdownOpen = ref(false);
const mobileMenuOpen = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);

const toggleDropdown = () => {
    dropdownOpen.value = !dropdownOpen.value;
};

const closeDropdown = () => {
    dropdownOpen.value = false;
};

const toggleMobileMenu = () => {
    mobileMenuOpen.value = !mobileMenuOpen.value;
};

const closeMobileMenu = () => {
    mobileMenuOpen.value = false;
};

const handleClickOutside = (e: MouseEvent) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target as Node)) {
        closeDropdown();
    }
};

let removeNavListener: (() => void) | null = null;

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    removeNavListener = router.on('navigate', () => {
        closeDropdown();
        closeMobileMenu();
    });
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);

    if (removeNavListener) {
        removeNavListener();
    }
});
</script>

<template>
    <nav
        class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/80 shadow-xs backdrop-blur-xl transition-colors dark:border-gray-800/80 dark:bg-gray-950/80"
    >
        <div
            class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8"
        >
            <!-- Left: Logo & Context Badge -->
            <div class="flex items-center gap-3">
                <AppLogo />

                <span
                    v-if="isAdmin"
                    class="inline-flex items-center gap-1 rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-0.5 text-[11px] font-bold tracking-wide text-indigo-700 uppercase dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-400"
                >
                    <Shield class="h-3 w-3" />
                    Admin
                </span>
            </div>

            <!-- Center/Desktop Navigation (md and up) -->
            <div class="hidden items-center gap-1.5 md:flex">
                <Link
                    href="/projects"
                    class="flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-bold transition-all"
                    :class="
                        isProjectsActive
                            ? 'bg-slate-100 text-indigo-600 dark:bg-gray-800 dark:text-indigo-400'
                            : 'text-slate-600 hover:bg-slate-100/60 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-gray-800/50 dark:hover:text-gray-100'
                    "
                >
                    <FolderGit2 class="h-3.5 w-3.5" />
                    <span>Projects</span>
                </Link>

                <Link
                    href="/blogs"
                    class="flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-bold transition-all"
                    :class="
                        isBlogsActive
                            ? 'bg-slate-100 text-indigo-600 dark:bg-gray-800 dark:text-indigo-400'
                            : 'text-slate-600 hover:bg-slate-100/60 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-gray-800/50 dark:hover:text-gray-100'
                    "
                >
                    <BookOpen class="h-3.5 w-3.5" />
                    <span>Blogs</span>
                </Link>
            </div>

            <!-- Right: Controls & Profile Cluster -->
            <div class="hidden items-center gap-3 md:flex">
                <!-- Dark Mode Toggle Button -->
                <button
                    @click="toggle"
                    class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200/80 bg-slate-50 text-slate-600 transition-all hover:bg-slate-100 hover:text-slate-900 active:scale-95 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100"
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
                </button>

                <div class="h-5 w-px bg-slate-200 dark:bg-gray-800"></div>

                <!-- Guest: Login CTA -->
                <Link
                    v-if="!user"
                    href="/login"
                    class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white shadow-xs transition-all hover:bg-slate-800 hover:shadow-md active:scale-95 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200"
                >
                    <LogIn class="h-3.5 w-3.5" />
                    <span>Login</span>
                </Link>

                <!-- Authenticated: User Dropdown -->
                <div v-else ref="dropdownRef" class="relative">
                    <button
                        @click="toggleDropdown"
                        class="flex items-center gap-2.5 rounded-full border border-slate-200/90 bg-white py-1 pr-3 pl-1 shadow-2xs transition-all hover:border-slate-300 hover:bg-slate-50 active:scale-98 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-gray-700 dark:hover:bg-gray-800"
                    >
                        <img
                            v-if="user.image_url"
                            :src="user.image_url"
                            :alt="user.name"
                            class="h-7 w-7 rounded-full object-cover ring-1 ring-slate-200 dark:ring-gray-700"
                        />
                        <span
                            v-else
                            class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-600 text-[11px] font-black text-white dark:bg-indigo-500"
                        >
                            {{ user.name.charAt(0).toUpperCase() }}
                        </span>

                        <span
                            class="max-w-[130px] truncate text-xs font-bold text-slate-800 dark:text-gray-200"
                        >
                            {{ user.name }}
                        </span>

                        <ChevronDown
                            class="h-3.5 w-3.5 text-slate-400 transition-transform duration-200"
                            :class="{ 'rotate-180': dropdownOpen }"
                        />
                    </button>

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
                            class="absolute right-0 mt-2 w-56 origin-top-right rounded-2xl border border-slate-200 bg-white p-1.5 shadow-2xl dark:border-gray-800 dark:bg-gray-900"
                        >
                            <!-- User Identity Card -->
                            <div
                                class="border-b border-slate-100 p-2.5 dark:border-gray-800"
                            >
                                <p
                                    class="truncate text-xs font-bold text-slate-900 dark:text-gray-100"
                                >
                                    {{ user.name }}
                                </p>
                                <p
                                    class="truncate text-[11px] font-medium text-slate-400 dark:text-gray-500"
                                >
                                    {{ user.username ? '@' + user.username : user.email }}
                                </p>
                            </div>

                            <div class="py-1">
                                <Link
                                    href="/profile"
                                    class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-50 hover:text-slate-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white"
                                    @click="closeDropdown"
                                >
                                    <User class="h-3.5 w-3.5 text-slate-400" />
                                    Profile Settings
                                </Link>

                                <Link
                                    v-if="canAccessAdmin"
                                    :href="isAdmin ? '/' : '/admin'"
                                    class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-50 hover:text-slate-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white"
                                    @click="closeDropdown"
                                >
                                    <component
                                        :is="isAdmin ? Home : LayoutDashboard"
                                        class="h-3.5 w-3.5 text-slate-400"
                                    />
                                    {{
                                        isAdmin
                                            ? 'Public Site View'
                                            : 'Staff Dashboard'
                                    }}
                                </Link>
                            </div>

                            <div
                                class="border-t border-slate-100 pt-1 dark:border-gray-800"
                            >
                                <Link
                                    href="/logout"
                                    method="post"
                                    as="button"
                                    class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-rose-600 transition-colors hover:bg-rose-50 dark:hover:bg-rose-950/40 dark:hover:text-rose-400"
                                    @click="closeDropdown"
                                >
                                    <LogOut class="h-3.5 w-3.5" />
                                    Sign Out
                                </Link>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>

            <!-- Mobile Controls Bar (md:hidden) -->
            <div class="flex items-center gap-2 md:hidden">
                <!-- Theme Toggle Button -->
                <button
                    @click="toggle"
                    class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200/80 bg-slate-50 text-slate-600 transition-colors hover:bg-slate-100 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800"
                    aria-label="Toggle theme"
                >
                    <Monitor v-if="theme === 'system'" class="h-4 w-4" />
                    <Sun v-else-if="theme === 'light'" class="h-4 w-4" />
                    <Moon v-else class="h-4 w-4" />
                </button>

                <!-- Compact Mobile Login Button (if guest) -->
                <Link
                    v-if="!user"
                    href="/login"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-3 py-2 text-xs font-bold text-white shadow-xs dark:bg-gray-100 dark:text-gray-900"
                >
                    <LogIn class="h-3.5 w-3.5" />
                    <span>Login</span>
                </Link>

                <!-- Mobile Menu Button -->
                <button
                    @click="toggleMobileMenu"
                    class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200/80 bg-white text-slate-700 shadow-2xs transition-colors hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
                    aria-label="Toggle navigation menu"
                >
                    <X v-if="mobileMenuOpen" class="h-4 w-4" />
                    <Menu v-else class="h-4 w-4" />
                </button>
            </div>
        </div>

        <!-- Mobile Navigation Drawer / Panel -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="-translate-y-2 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="-translate-y-2 opacity-0"
        >
            <div
                v-if="mobileMenuOpen"
                class="border-t border-slate-200/80 bg-white/95 px-4 pt-3 pb-6 shadow-2xl backdrop-blur-xl md:hidden dark:border-gray-800 dark:bg-gray-950/95"
            >
                <!-- User Profile Header on Mobile (if logged in) -->
                <div
                    v-if="user"
                    class="mb-3 flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-slate-50/80 p-3 dark:border-gray-800 dark:bg-gray-900/80"
                >
                    <div
                        v-if="user.image_url"
                        class="h-10 w-10 shrink-0 overflow-hidden rounded-full ring-2 ring-indigo-600/20"
                    >
                        <img
                            :src="user.image_url"
                            :alt="user.name"
                            class="h-full w-full object-cover"
                        />
                    </div>
                    <div
                        v-else
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-50 font-bold text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                    >
                        {{ user.name.charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p
                            class="truncate text-xs font-bold text-slate-900 dark:text-gray-100"
                        >
                            {{ user.name }}
                        </p>
                        <p
                            class="truncate text-[11px] text-slate-400 dark:text-gray-500"
                        >
                            {{ user.username ? '@' + user.username : user.email }}
                        </p>
                    </div>
                </div>

                <!-- Navigation Links List -->
                <div class="space-y-1">
                    <Link
                        href="/projects"
                        @click="closeMobileMenu"
                        class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-bold transition-colors"
                        :class="
                            isProjectsActive
                                ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400'
                                : 'text-slate-700 hover:bg-slate-100 dark:text-gray-300 dark:hover:bg-gray-900'
                        "
                    >
                        <FolderGit2
                            class="h-4 w-4 text-indigo-600 dark:text-indigo-400"
                        />
                        <span>Projects</span>
                    </Link>

                    <Link
                        href="/blogs"
                        @click="closeMobileMenu"
                        class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-bold transition-colors"
                        :class="
                            isBlogsActive
                                ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400'
                                : 'text-slate-700 hover:bg-slate-100 dark:text-gray-300 dark:hover:bg-gray-900'
                        "
                    >
                        <BookOpen
                            class="h-4 w-4 text-blue-600 dark:text-blue-400"
                        />
                        <span>Blogs</span>
                    </Link>

                    <!-- Authenticated User Menu Options -->
                    <template v-if="user">
                        <div
                            class="my-2 border-t border-slate-100 dark:border-gray-800"
                        ></div>

                        <Link
                            href="/profile"
                            @click="closeMobileMenu"
                            class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-100 dark:text-gray-300 dark:hover:bg-gray-900"
                        >
                            <User class="h-4 w-4 text-slate-400" />
                            <span>Profile Settings</span>
                        </Link>

                        <Link
                            v-if="canAccessAdmin"
                            :href="isAdmin ? '/' : '/admin'"
                            @click="closeMobileMenu"
                            class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-100 dark:text-gray-300 dark:hover:bg-gray-900"
                        >
                            <component
                                :is="isAdmin ? Home : LayoutDashboard"
                                class="h-4 w-4 text-slate-400"
                            />
                            <span>{{
                                isAdmin ? 'Public Site View' : 'Staff Dashboard'
                            }}</span>
                        </Link>

                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            class="flex w-full items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-rose-600 transition-colors hover:bg-rose-50 dark:hover:bg-rose-950/30"
                            @click="closeMobileMenu"
                        >
                            <LogOut class="h-4 w-4" />
                            <span>Sign Out</span>
                        </Link>
                    </template>
                </div>
            </div>
        </Transition>
    </nav>
</template>
