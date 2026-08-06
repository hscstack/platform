<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogo from './AppLogo.vue';

defineProps({
    isAdmin: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <nav
        class="sticky top-0 z-50 border-b border-slate-200/60 bg-white/80 backdrop-blur-md"
    >
        <div
            class="mx-auto flex h-14 max-w-7xl items-center justify-between px-4 sm:px-6"
        >
            <div class="flex items-center gap-2">
                <AppLogo />

                <span
                    v-if="isAdmin"
                    class="rounded bg-slate-100 px-2 py-0.5 text-xs font-semibold tracking-wider text-slate-400 uppercase"
                >
                    Admin
                </span>
            </div>

            <div class="flex items-center gap-6">
                <Link
                    href="/blogs"
                    class="text-sm font-medium text-slate-600 transition-colors hover:text-slate-900"
                >
                    Blogs
                </Link>

                <Link
                    :href="isAdmin ? '/' : '/admin'"
                    class="text-sm font-medium text-slate-600 transition-colors hover:text-slate-900"
                >
                    {{ isAdmin ? 'Home' : 'Login' }}
                </Link>
            </div>
        </div>
    </nav>
</template>
