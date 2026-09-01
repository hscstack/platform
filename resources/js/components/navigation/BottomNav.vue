<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import { primaryNavItems } from '@/lib/navigation';

const page = usePage();
const currentUrl = computed(() => String(page.url));

const homeHref = computed(() => {
    if (typeof window !== 'undefined') {
        try {
            const pref = localStorage.getItem('preferred_course');

            if (pref === 'ssc') {
                return '/ssc';
            }
        } catch {}
    }

    return currentUrl.value.startsWith('/ssc') ? '/ssc' : '/';
});

const isActive = (href: string, match?: (url: string) => boolean) => {
    if (match) {
        return match(currentUrl.value);
    }

    return currentUrl.value.startsWith(href);
};
</script>

<template>
    <nav
        class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur-xl landscape:hidden dark:border-gray-800 dark:bg-gray-950/95"
        aria-label="Bottom navigation"
    >
        <div
            class="mx-auto flex max-w-7xl items-center justify-around px-2 py-1"
        >
            <Link
                v-for="item in primaryNavItems"
                :key="item.href"
                :href="item.href === '/' ? homeHref : item.href"
                :class="[
                    'flex flex-col items-center gap-1 rounded-xl px-3 py-1.5 text-[11px] font-medium transition-colors',
                    isActive(item.href, item.match)
                        ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400'
                        : 'text-slate-500 hover:text-slate-900 dark:text-gray-400 dark:hover:text-gray-100',
                ]"
            >
                <component :is="item.icon" class="h-5 w-5" />
                <span class="leading-none">{{ item.label }}</span>
            </Link>
        </div>
    </nav>
</template>
