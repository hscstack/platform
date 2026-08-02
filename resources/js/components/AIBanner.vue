<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Sparkles, ArrowRight } from 'lucide-vue-next';
</script>

<template>
    <Link
        href="/ai"
        class="group relative mb-6 flex items-center justify-between gap-3 overflow-hidden rounded-2xl border border-indigo-100/80 bg-gradient-to-r from-indigo-50/90 via-violet-50/40 to-fuchsia-50/30 p-3.5 shadow-xs transition-all duration-300 hover:border-indigo-300 hover:shadow-md sm:p-4"
    >
        <!-- Background Ambient Glow -->
        <div
            class="pointer-events-none absolute -top-8 -right-8 h-28 w-28 rounded-full bg-indigo-500/10 blur-xl transition-all duration-300 group-hover:scale-125 sm:-top-10 sm:-right-10 sm:h-32 sm:w-32 sm:blur-2xl"
        ></div>

        <!-- Left Content Section -->
        <div class="relative z-10 flex min-w-0 items-center gap-3">
            <!-- Icon Wrapper -->
            <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-md shadow-indigo-500/20 transition-transform duration-300 group-hover:scale-105 sm:h-10 sm:w-10"
            >
                <Sparkles class="h-4 w-4 animate-pulse sm:h-5 sm:w-5" />
            </div>

            <!-- Text Details -->
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                    <span
                        class="text-sm leading-tight font-extrabold text-slate-900"
                        >HSCStack AI</span
                    >
                </div>
                <p
                    class="mt-0.5 truncate text-[11px] font-medium text-slate-600 sm:text-xs sm:whitespace-normal"
                >
                    বিষয় ও অধ্যায় সিলেক্ট করে সরাসরি প্রশ্ন ও গাণিতিক সমস্যা
                    সলভ করুন।
                </p>
            </div>
        </div>

        <!-- Right Action Arrow (Visible on Mobile & Desktop) -->
        <div
            class="relative z-10 flex shrink-0 items-center gap-1 text-xs font-bold text-indigo-600"
        >
            <span class="hidden sm:inline">এক্সপ্লোর করুন</span>
            <ArrowRight
                class="h-4 w-4 transition-transform duration-200 group-hover:translate-x-1 sm:h-3.5 sm:w-3.5"
            />
        </div>
    </Link>
</template>
