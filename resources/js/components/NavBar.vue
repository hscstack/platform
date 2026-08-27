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
    Info,
    Sparkles,
    Users,
    HeartHandshake,
} from 'lucide-vue-next';
import { computed, ref, onMounted, onBeforeUnmount, watch } from 'vue';
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
const isHomeActive = computed(
    () => currentUrl.value === '/' || currentUrl.value.startsWith('/?'),
);

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

// Prevent background scroll when mobile drawer is open
watch(mobileMenuOpen, (isOpen) => {
    if (typeof document !== 'undefined') {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
});

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

    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }

    if (removeNavListener) {
        removeNavListener();
    }
});
</script>

<template>
    <nav
        class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/80 shadow-xs backdrop-blur-xl transition-colors dark:border-gray-800/80 dark:bg-gray-950/80"
    >
        <div
            class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8"
        >
            <!-- Left: Logo -->
            <div class="flex items-center gap-3">
                <AppLogo />
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
                                    {{ user.email }}
                                </p>
                            </div>

                            <div class="py-1">
                                <Link
                                    :href="
                                        user.username
                                            ? `/u/${user.username}`
                                            : '/profile'
                                    "
                                    class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-50 hover:text-slate-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white"
                                    @click="closeDropdown"
                                >
                                    <User class="h-3.5 w-3.5 text-slate-400" />
                                    Profile
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
                                    {{ isAdmin ? 'Home' : 'Staff Panel' }}
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

            <!-- Mobile Bar Controls (md:hidden) -->
            <div class="flex items-center gap-2 md:hidden">
                <!-- Compact Mobile Login Button (if guest) -->
                <Link
                    v-if="!user"
                    href="/login"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-3 py-2 text-xs font-bold text-white shadow-xs dark:bg-gray-100 dark:text-gray-900"
                >
                    <LogIn class="h-3.5 w-3.5" />
                    <span>Login</span>
                </Link>

                <!-- Mobile Menu Hamburger Button -->
                <button
                    @click="toggleMobileMenu"
                    class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200/80 bg-white text-slate-700 shadow-2xs transition-colors hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
                    aria-label="Open navigation drawer"
                >
                    <Menu class="h-4 w-4" />
                </button>
            </div>
        </div>

        <!-- Off-canvas Mobile Navigation Drawer -->
        <Teleport to="body">
            <div
                v-if="mobileMenuOpen"
                class="fixed inset-0 z-50 flex justify-end md:hidden"
            >
                <!-- Backdrop overlay -->
                <Transition
                    appear
                    enter-active-class="transition-opacity duration-300 ease-out"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="transition-opacity duration-200 ease-in"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div
                        class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm dark:bg-black/60"
                        @click="closeMobileMenu"
                        aria-hidden="true"
                    />
                </Transition>

                <!-- Drawer content panel -->
                <Transition
                    appear
                    enter-active-class="transition-transform duration-300 cubic-bezier(0.16, 1, 0.3, 1)"
                    enter-from-class="translate-x-full"
                    enter-to-class="translate-x-0"
                    leave-active-class="transition-transform duration-200 ease-in"
                    leave-from-class="translate-x-0"
                    leave-to-class="translate-x-full"
                >
                    <aside
                        class="relative flex h-full w-full max-w-xs flex-col justify-between border-l border-slate-200/80 bg-white/95 shadow-2xl backdrop-blur-2xl dark:border-gray-800 dark:bg-gray-950/95"
                    >
                        <!-- Top Header in Drawer -->
                        <div class="flex items-center justify-between border-b border-slate-100 p-4 dark:border-gray-800/80">
                            <AppLogo />
                            <button
                                @click="closeMobileMenu"
                                class="flex h-8 w-8 items-center justify-center rounded-xl text-slate-500 hover:bg-slate-100 hover:text-slate-800 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                                aria-label="Close drawer"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <!-- Scrollable Drawer Body -->
                        <div class="flex-1 overflow-y-auto px-4 py-4 space-y-6">
                            <!-- User Card or Guest Login CTA -->
                            <div v-if="user">
                                <Link
                                    :href="user.username ? `/u/${user.username}` : '/profile'"
                                    @click="closeMobileMenu"
                                    class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-slate-50/80 p-3 transition hover:border-slate-300 hover:bg-slate-100 dark:border-gray-800 dark:bg-gray-900/60 dark:hover:border-gray-700 dark:hover:bg-gray-900"
                                >
                                    <img
                                        v-if="user.image_url"
                                        :src="user.image_url"
                                        :alt="user.name"
                                        class="h-10 w-10 rounded-full object-cover ring-2 ring-indigo-500/20"
                                    />
                                    <div
                                        v-else
                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-600 text-sm font-black text-white dark:bg-indigo-500"
                                    >
                                        {{ user.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-xs font-bold text-slate-900 dark:text-gray-100">
                                            {{ user.name }}
                                        </p>
                                        <p class="truncate text-[11px] text-slate-400 dark:text-gray-500">
                                            {{ user.email }}
                                        </p>
                                    </div>
                                </Link>
                            </div>

                            <div v-else>
                                <Link
                                    href="/login"
                                    @click="closeMobileMenu"
                                    class="flex w-full items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-indigo-600/20 transition-all hover:bg-indigo-500 active:scale-98"
                                >
                                    <LogIn class="h-4 w-4" />
                                    <span>Sign in / Register</span>
                                </Link>
                            </div>

                            <!-- Main Exploration Navigation -->
                            <div>
                                <p class="mb-2 px-2 text-[10px] font-bold tracking-wider text-slate-400 uppercase dark:text-gray-500">
                                    Explore
                                </p>
                                <div class="space-y-1">
                                    <Link
                                        href="/"
                                        @click="closeMobileMenu"
                                        class="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-bold transition-all"
                                        :class="
                                            isHomeActive
                                                ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400'
                                                : 'text-slate-600 hover:bg-slate-100/70 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-gray-900/60 dark:hover:text-gray-200'
                                        "
                                    >
                                        <Home class="h-4 w-4" />
                                        <span>Home</span>
                                    </Link>

                                    <Link
                                        href="/projects"
                                        @click="closeMobileMenu"
                                        class="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-bold transition-all"
                                        :class="
                                            isProjectsActive
                                                ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400'
                                                : 'text-slate-600 hover:bg-slate-100/70 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-gray-900/60 dark:hover:text-gray-200'
                                        "
                                    >
                                        <FolderGit2 class="h-4 w-4" />
                                        <span>Projects</span>
                                    </Link>

                                    <Link
                                        href="/blogs"
                                        @click="closeMobileMenu"
                                        class="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-bold transition-all"
                                        :class="
                                            isBlogsActive
                                                ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400'
                                                : 'text-slate-600 hover:bg-slate-100/70 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-gray-900/60 dark:hover:text-gray-200'
                                        "
                                    >
                                        <BookOpen class="h-4 w-4" />
                                        <span>Blogs</span>
                                    </Link>
                                </div>
                            </div>

                            <!-- Account / Admin Links (if logged in, placed above platform) -->
                            <div v-if="user">
                                <p class="mb-2 px-2 text-[10px] font-bold tracking-wider text-slate-400 uppercase dark:text-gray-500">
                                    Account
                                </p>
                                <div class="space-y-1">
                                    <Link
                                        :href="user.username ? `/u/${user.username}` : '/profile'"
                                        @click="closeMobileMenu"
                                        class="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100/70 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-gray-900/60 dark:hover:text-gray-200"
                                    >
                                        <User class="h-4 w-4 text-slate-400" />
                                        <span>Profile</span>
                                    </Link>

                                    <Link
                                        v-if="canAccessAdmin"
                                        :href="isAdmin ? '/' : '/admin'"
                                        @click="closeMobileMenu"
                                        class="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100/70 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-gray-900/60 dark:hover:text-gray-200"
                                    >
                                        <component
                                            :is="isAdmin ? Home : LayoutDashboard"
                                            class="h-4 w-4 text-slate-400"
                                        />
                                        <span>{{ isAdmin ? 'Home' : 'Staff Panel' }}</span>
                                    </Link>
                                </div>
                            </div>

                            <!-- Platform Links -->
                            <div>
                                <p class="mb-2 px-2 text-[10px] font-bold tracking-wider text-slate-400 uppercase dark:text-gray-500">
                                    Platform
                                </p>
                                <div class="space-y-1">
                                    <Link
                                        href="/ai"
                                        @click="closeMobileMenu"
                                        class="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100/70 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-gray-900/60 dark:hover:text-gray-200"
                                    >
                                        <Sparkles class="h-4 w-4 text-amber-500" />
                                        <span>AI Assistant</span>
                                    </Link>

                                    <Link
                                        href="/about-us"
                                        @click="closeMobileMenu"
                                        class="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100/70 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-gray-900/60 dark:hover:text-gray-200"
                                    >
                                        <Info class="h-4 w-4 text-slate-400" />
                                        <span>About Us</span>
                                    </Link>

                                    <Link
                                        href="/join"
                                        @click="closeMobileMenu"
                                        class="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100/70 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-gray-900/60 dark:hover:text-gray-200"
                                    >
                                        <Users class="h-4 w-4 text-slate-400" />
                                        <span>Join the Team</span>
                                    </Link>

                                    <Link
                                        href="/support"
                                        @click="closeMobileMenu"
                                        class="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100/70 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-gray-900/60 dark:hover:text-gray-200"
                                    >
                                        <HeartHandshake class="h-4 w-4 text-rose-500" />
                                        <span>Support HSCStack</span>
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Actions in Drawer -->
                        <div class="border-t border-slate-100 p-4 dark:border-gray-800/80">
                            <!-- Theme Preference Switcher -->
                            <div>
                                <p class="mb-2 text-[10px] font-bold tracking-wider text-slate-400 uppercase dark:text-gray-500">
                                    Appearance
                                </p>
                                <div class="grid grid-cols-3 gap-1 rounded-xl bg-slate-100 p-1 dark:bg-gray-900">
                                    <button
                                        type="button"
                                        @click="theme = 'light'"
                                        class="flex items-center justify-center gap-1.5 rounded-lg py-1.5 text-xs font-semibold transition-all"
                                        :class="
                                            theme === 'light'
                                                ? 'bg-white text-slate-900 shadow-2xs dark:bg-gray-800 dark:text-white'
                                                : 'text-slate-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200'
                                        "
                                    >
                                        <Sun class="h-3.5 w-3.5" />
                                        <span>Light</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="theme = 'dark'"
                                        class="flex items-center justify-center gap-1.5 rounded-lg py-1.5 text-xs font-semibold transition-all"
                                        :class="
                                            theme === 'dark'
                                                ? 'bg-white text-slate-900 shadow-2xs dark:bg-gray-800 dark:text-white'
                                                : 'text-slate-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200'
                                        "
                                    >
                                        <Moon class="h-3.5 w-3.5" />
                                        <span>Dark</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="theme = 'system'"
                                        class="flex items-center justify-center gap-1.5 rounded-lg py-1.5 text-xs font-semibold transition-all"
                                        :class="
                                            theme === 'system'
                                                ? 'bg-white text-slate-900 shadow-2xs dark:bg-gray-800 dark:text-white'
                                                : 'text-slate-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200'
                                        "
                                    >
                                        <Monitor class="h-3.5 w-3.5" />
                                        <span>System</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </aside>
                </Transition>
            </div>
        </Teleport>
    </nav>
</template>


