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
        class="sticky top-0 z-50 border-b-4 border-red-700 bg-white/90 shadow-[0_2px_0_0_rgba(29,78,216,0.6)] backdrop-blur-md"
    >
        <!-- faint red/blue tint wash -->
        <div
            class="pointer-events-none absolute inset-0 bg-gradient-to-br from-red-50/70 via-white/90 to-blue-50/70"
        ></div>
        <!-- subtle web-dot pattern accent -->
        <div
            class="pointer-events-none absolute inset-0 [background-image:radial-gradient(circle_at_1px_1px,black_1px,transparent_1px)] [background-size:16px_16px] opacity-[0.04]"
        ></div>
        <!-- top accent stripe -->
        <div
            class="pointer-events-none absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-red-700 via-blue-700 to-red-700"
        ></div>

        <!-- scattered spider glyphs -->
        <svg
            class="pointer-events-none absolute top-2 left-[30%] h-4 w-4 text-red-700/40"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.3"
            stroke-linecap="round"
        >
            <ellipse cx="12" cy="13" rx="3.2" ry="4.2" />
            <circle cx="12" cy="7" r="1.8" />
            <path
                d="M9 10 L4 7 M9 10 L3 11 M9 10 L4 15 M15 10 L20 7 M15 10 L21 11 M15 10 L20 15 M10.5 16 L7 20 M13.5 16 L17 20"
            />
        </svg>
        <svg
            class="pointer-events-none absolute bottom-2 left-[46%] h-3 w-3 text-blue-700/35"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.3"
            stroke-linecap="round"
        >
            <ellipse cx="12" cy="13" rx="3.2" ry="4.2" />
            <circle cx="12" cy="7" r="1.8" />
            <path
                d="M9 10 L4 7 M9 10 L3 11 M9 10 L4 15 M15 10 L20 7 M15 10 L21 11 M15 10 L20 15 M10.5 16 L7 20 M13.5 16 L17 20"
            />
        </svg>
        <svg
            class="pointer-events-none absolute top-3 right-[36%] h-3 w-3 text-red-700/30"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.3"
            stroke-linecap="round"
        >
            <ellipse cx="12" cy="13" rx="3.2" ry="4.2" />
            <circle cx="12" cy="7" r="1.8" />
            <path
                d="M9 10 L4 7 M9 10 L3 11 M9 10 L4 15 M15 10 L20 7 M15 10 L21 11 M15 10 L20 15 M10.5 16 L7 20 M13.5 16 L17 20"
            />
        </svg>

        <!-- decorative web-net graphic, top-left corner -->
        <svg
            class="pointer-events-none absolute -top-3 -left-3 h-24 w-24 text-red-700/40 sm:h-28 sm:w-28"
            viewBox="0 0 100 100"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <!-- radial strands -->
            <line
                x1="0"
                y1="0"
                x2="100"
                y2="0"
                stroke="currentColor"
                stroke-width="1.25"
            />
            <line
                x1="0"
                y1="0"
                x2="0"
                y2="100"
                stroke="currentColor"
                stroke-width="1.25"
            />
            <line
                x1="0"
                y1="0"
                x2="100"
                y2="75"
                stroke="currentColor"
                stroke-width="1"
            />
            <line
                x1="0"
                y1="0"
                x2="75"
                y2="100"
                stroke="currentColor"
                stroke-width="1"
            />
            <line
                x1="0"
                y1="0"
                x2="100"
                y2="50"
                stroke="currentColor"
                stroke-width="1"
            />
            <line
                x1="0"
                y1="0"
                x2="50"
                y2="100"
                stroke="currentColor"
                stroke-width="1"
            />
            <line
                x1="0"
                y1="0"
                x2="100"
                y2="25"
                stroke="currentColor"
                stroke-width="1"
            />
            <line
                x1="0"
                y1="0"
                x2="25"
                y2="100"
                stroke="currentColor"
                stroke-width="1"
            />
            <!-- concentric arcs -->
            <path
                d="M6,0 A6,6 0 0,1 0,6"
                stroke="currentColor"
                stroke-width="1"
            />
            <path
                d="M16,0 A16,16 0 0,1 0,16"
                stroke="currentColor"
                stroke-width="1"
            />
            <path
                d="M28,0 A28,28 0 0,1 0,28"
                stroke="currentColor"
                stroke-width="1"
            />
            <path
                d="M42,0 A42,42 0 0,1 0,42"
                stroke="currentColor"
                stroke-width="1"
            />
            <path
                d="M58,0 A58,58 0 0,1 0,58"
                stroke="currentColor"
                stroke-width="1"
            />
            <path
                d="M76,0 A76,76 0 0,1 0,76"
                stroke="currentColor"
                stroke-width="1"
            />
            <path
                d="M96,0 A96,96 0 0,1 0,96"
                stroke="currentColor"
                stroke-width="1"
            />
        </svg>

        <!-- mirrored web-net graphic, top-right corner -->
        <svg
            class="pointer-events-none absolute -top-3 -right-3 h-20 w-20 text-blue-700/35 sm:h-24 sm:w-24"
            viewBox="0 0 100 100"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <line
                x1="100"
                y1="0"
                x2="0"
                y2="0"
                stroke="currentColor"
                stroke-width="1.25"
            />
            <line
                x1="100"
                y1="0"
                x2="100"
                y2="100"
                stroke="currentColor"
                stroke-width="1.25"
            />
            <line
                x1="100"
                y1="0"
                x2="0"
                y2="75"
                stroke="currentColor"
                stroke-width="1"
            />
            <line
                x1="100"
                y1="0"
                x2="25"
                y2="100"
                stroke="currentColor"
                stroke-width="1"
            />
            <line
                x1="100"
                y1="0"
                x2="0"
                y2="50"
                stroke="currentColor"
                stroke-width="1"
            />
            <line
                x1="100"
                y1="0"
                x2="50"
                y2="100"
                stroke="currentColor"
                stroke-width="1"
            />
            <line
                x1="100"
                y1="0"
                x2="0"
                y2="25"
                stroke="currentColor"
                stroke-width="1"
            />
            <line
                x1="100"
                y1="0"
                x2="75"
                y2="100"
                stroke="currentColor"
                stroke-width="1"
            />
            <path
                d="M94,0 A6,6 0 0,0 100,6"
                stroke="currentColor"
                stroke-width="1"
            />
            <path
                d="M84,0 A16,16 0 0,0 100,16"
                stroke="currentColor"
                stroke-width="1"
            />
            <path
                d="M72,0 A28,28 0 0,0 100,28"
                stroke="currentColor"
                stroke-width="1"
            />
            <path
                d="M58,0 A42,42 0 0,0 100,42"
                stroke="currentColor"
                stroke-width="1"
            />
            <path
                d="M42,0 A58,58 0 0,0 100,58"
                stroke="currentColor"
                stroke-width="1"
            />
        </svg>

        <div
            class="relative mx-auto flex h-14 max-w-7xl items-center justify-between px-4 sm:px-6"
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
                    class="text-sm font-medium text-slate-600 transition-colors hover:text-red-700"
                >
                    Blogs
                </Link>
                <Link
                    :href="isAdmin ? '/' : '/admin'"
                    class="rounded-md bg-gradient-to-r from-red-600 to-blue-700 px-4 py-1.5 text-sm font-semibold text-white shadow-sm ring-1 ring-white/20 transition-all ring-inset hover:opacity-95 hover:shadow-md"
                >
                    {{ isAdmin ? 'Home' : 'Login' }}
                </Link>
            </div>
        </div>
    </nav>
</template>
