<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    HelpCircle,
    ArrowLeft,
    Home,
    BookOpen,
    Users,
    Info,
    ChevronRight,
} from 'lucide-vue-next';

const goBack = () => {
    if (window.history.length > 1) {
        window.history.back();
    }
};
</script>

<template>
    <div
        class="relative flex min-h-[calc(100vh-10rem)] items-center justify-center px-4 py-16 sm:px-6 sm:py-24"
    >
        <!-- Glowing aura behind the card -->
        <div
            class="animate-pulse-glow absolute top-1/2 left-1/2 -z-10 h-72 w-72 -translate-x-1/2 -translate-y-1/2 rounded-full bg-indigo-400 opacity-15 blur-[100px] transition-all duration-700"
        ></div>

        <!-- Glass card content -->
        <div
            class="relative z-10 w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200/50 bg-white/70 p-8 text-center shadow-xl backdrop-blur-md transition-all duration-300 hover:border-slate-300/40 hover:shadow-2xl sm:p-10"
        >
            <!-- Animated decorative icon wrapper -->
            <div
                class="relative mx-auto mb-6 flex h-24 w-24 items-center justify-center rounded-2xl bg-slate-50 text-slate-800 shadow-inner"
            >
                <div
                    class="absolute inset-0 animate-ping rounded-2xl bg-slate-100 opacity-60"
                ></div>
                <div
                    class="animate-float flex items-center justify-center text-slate-700"
                >
                    <HelpCircle
                        class="h-12 w-12 stroke-[1.5] text-indigo-600"
                    />
                </div>
            </div>

            <!-- Big Gradient Error Code -->
            <h1
                class="bg-gradient-to-r from-indigo-600 via-violet-600 to-pink-500 bg-clip-text text-8xl font-black tracking-tight text-transparent select-none sm:text-9xl"
            >
                404
            </h1>

            <h2 class="mt-4 text-2xl font-bold tracking-tight text-slate-900">
                Page Lost in Space
            </h2>

            <p
                class="mx-auto mt-3 max-w-sm text-sm leading-relaxed text-slate-500"
            >
                We couldn't find the page you were looking for. It might have
                been moved, deleted, or never existed in the first place.
            </p>

            <!-- Navigation Buttons -->
            <div
                class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row"
            >
                <button
                    @click="goBack"
                    class="inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-slate-100 px-5 py-2.5 text-sm font-semibold text-slate-700 transition-all duration-150 hover:bg-slate-200 active:scale-95 sm:w-auto"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Go Back
                </button>

                <Link
                    href="/"
                    class="inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-100 transition-all duration-150 hover:scale-[1.02] hover:shadow-lg hover:shadow-indigo-200/60 active:scale-[0.98] sm:w-auto"
                >
                    <Home class="h-4 w-4" />
                    Go Home
                </Link>
            </div>

            <!-- Divider -->
            <div class="my-8 h-px bg-slate-200/60"></div>

            <!-- Directory / Helpful Links -->
            <div class="text-left">
                <h3
                    class="text-xs font-bold tracking-wider text-slate-400 uppercase"
                >
                    Quick Links
                </h3>

                <div class="mt-3 grid grid-cols-1 gap-2.5">
                    <Link
                        href="/"
                        class="group flex items-center justify-between rounded-xl border border-slate-100 bg-white/40 p-3 text-sm font-medium text-slate-700 transition-all duration-150 hover:border-slate-200 hover:bg-white/80 hover:shadow-sm"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 transition-colors group-hover:bg-indigo-100"
                            >
                                <BookOpen class="h-4 w-4" />
                            </div>
                            <span>Academic Resources</span>
                        </div>
                        <ChevronRight
                            class="h-4 w-4 text-slate-400 transition-transform duration-200 group-hover:translate-x-0.5"
                        />
                    </Link>

                    <Link
                        href="/join"
                        class="group flex items-center justify-between rounded-xl border border-slate-100 bg-white/40 p-3 text-sm font-medium text-slate-700 transition-all duration-150 hover:border-slate-200 hover:bg-white/80 hover:shadow-sm"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 transition-colors group-hover:bg-emerald-100"
                            >
                                <Users class="h-4 w-4" />
                            </div>
                            <span>Join Our Team</span>
                        </div>
                        <ChevronRight
                            class="h-4 w-4 text-slate-400 transition-transform duration-200 group-hover:translate-x-0.5"
                        />
                    </Link>

                    <Link
                        href="/about-us"
                        class="group flex items-center justify-between rounded-xl border border-slate-100 bg-white/40 p-3 text-sm font-medium text-slate-700 transition-all duration-150 hover:border-slate-200 hover:bg-white/80 hover:shadow-sm"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-sky-50 text-sky-600 transition-colors group-hover:bg-sky-100"
                            >
                                <Info class="h-4 w-4" />
                            </div>
                            <span>About Us</span>
                        </div>
                        <ChevronRight
                            class="h-4 w-4 text-slate-400 transition-transform duration-200 group-hover:translate-x-0.5"
                        />
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes float {
    0%,
    100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-8px);
    }
}

@keyframes pulseGlow {
    0%,
    100% {
        opacity: 0.15;
        transform: translate(-50%, -50%) scale(1);
    }
    50% {
        opacity: 0.25;
        transform: translate(-50%, -50%) scale(1.08);
    }
}

.animate-float {
    animation: float 4s ease-in-out infinite;
}

.animate-pulse-glow {
    animation: pulseGlow 6s ease-in-out infinite;
}
</style>
