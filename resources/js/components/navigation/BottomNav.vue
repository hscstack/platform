<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import MaterialIcon from '@/components/ui/MaterialIcon.vue';
import { useBottomNavCustomization } from '@/lib/useBottomNavCustomization';

const page = usePage();
const currentUrl = computed(() => String(page.url));

const { bottomNavItems } = useBottomNavCustomization();

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

const resolvedHref = (item: { href: string }) => {
    if (item.href === '/') {
        return homeHref.value;
    }

    return item.href;
};
</script>

<template>
    <!-- YT / YT Music style: full-width bottom bar, icon above label, active filled -->
    <nav
        class="fixed inset-x-0 bottom-0 z-40 w-full border-t border-slate-200/70 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur-xl dark:border-slate-800 dark:bg-slate-900/95"
        aria-label="Bottom navigation"
    >
        <div
            class="mx-auto flex w-full max-w-md items-center justify-around px-1 py-2"
        >
            <Link
                v-for="item in bottomNavItems"
                :key="item.href"
                :href="resolvedHref(item)"
                :class="[
                    'flex min-w-0 flex-1 flex-col items-center gap-1 rounded-xl px-2 py-2 transition-all duration-150 ease-out',
                    isActive(item.href, item.match)
                        ? 'text-slate-900 dark:text-white'
                        : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200',
                ]"
            >
                <MaterialIcon
                    :name="item.icon"
                    :size="26"
                    :filled="isActive(item.href, item.match)"
                    :weight="400"
                    :class="[
                        'shrink-0 transition-transform duration-150',
                        isActive(item.href, item.match)
                            ? 'scale-[1.02] text-slate-900 dark:text-white'
                            : 'text-slate-500 dark:text-slate-400',
                    ]"
                />
                <span
                    :class="[
                        'text-[10px] leading-none tracking-wide antialiased',
                        isActive(item.href, item.match)
                            ? 'font-bold'
                            : 'font-medium',
                    ]"
                    >{{ item.label }}</span
                >
            </Link>
        </div>
    </nav>
</template>
