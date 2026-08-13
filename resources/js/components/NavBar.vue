<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Moon, Sun, Monitor } from 'lucide-vue-next';
import { useDarkMode } from '@/lib/useDarkMode';
import AppLogo from './AppLogo.vue';

defineProps({
    isAdmin: {
        type: Boolean,
        default: false,
    },
});

const { theme, toggle } = useDarkMode();
</script>

<template>
    <nav
        class="sticky top-0 z-50 border-b border-slate-200/60 bg-white/80 backdrop-blur-md dark:border-gray-700/60 dark:bg-gray-900/80"
    >
        <div
            class="mx-auto flex h-14 max-w-7xl items-center justify-between px-4 sm:px-6"
        >
            <div class="flex items-center gap-2">
                <AppLogo />

                <span
                    v-if="isAdmin"
                    class="rounded bg-slate-100 px-2 py-0.5 text-xs font-semibold tracking-wider text-slate-400 uppercase dark:bg-gray-800 dark:text-gray-500"
                >
                    Admin
                </span>
            </div>

            <div class="flex items-center gap-4">
                <Link
                    href="/blogs"
                    class="text-sm font-medium text-slate-600 transition-colors hover:text-slate-900 dark:text-gray-400 dark:hover:text-gray-100"
                >
                    Blogs
                </Link>

                <Link
                    :href="isAdmin ? '/' : '/admin'"
                    class="text-sm font-medium text-slate-600 transition-colors hover:text-slate-900 dark:text-gray-400 dark:hover:text-gray-100"
                >
                    {{ isAdmin ? 'Home' : 'Login' }}
                </Link>

                <button
                    @click="toggle"
                    class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
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
            </div>
        </div>
    </nav>
</template>
