<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Wrench,
    Home,
    Facebook,
    Github,
    Mail,
    ChevronRight,
} from 'lucide-vue-next';
import { kButton, kPage, kNavbar, kBlock } from 'konsta/vue';
</script>

<template>
    <kPage>
        <knavbar title="Error" />
        <div
            class="relative flex min-h-[calc(100vh-10rem)] items-center justify-center px-4 py-16 sm:px-6 sm:py-24"
        >
            <!-- Glowing aura behind the card -->
            <div
                class="animate-pulse-glow absolute top-1/2 left-1/2 -z-10 h-72 w-72 -translate-x-1/2 -translate-y-1/2 rounded-full bg-indigo-400 opacity-15 blur-[100px] transition-all duration-700"
            ></div>

            <!-- Glass card content -->
            <div
                class="relative z-10 w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200/50 bg-white/70 p-8 text-center shadow-xl backdrop-blur-md transition-all duration-300 hover:border-slate-300/40 hover:shadow-2xl sm:p-10 dark:border-gray-700/50 dark:bg-gray-900/70 dark:hover:border-gray-600/40"
            >
                <!-- Animated decorative icon wrapper -->
                <div
                    class="relative mx-auto mb-6 flex h-24 w-24 items-center justify-center rounded-2xl bg-slate-50 text-slate-800 shadow-inner dark:bg-gray-800 dark:text-gray-200"
                >
                    <div
                        class="absolute inset-0 animate-ping rounded-2xl bg-slate-100 opacity-60 dark:bg-gray-800"
                    ></div>
                    <div
                        class="animate-float flex items-center justify-center text-slate-700 dark:text-gray-300"
                    >
                        <Wrench
                            class="h-12 w-12 stroke-[1.5] text-indigo-600 dark:text-indigo-400"
                        />
                    </div>
                </div>

                <!-- Big Gradient Error Code -->
                <h1
                    class="bg-gradient-to-r from-indigo-600 via-violet-600 to-pink-500 bg-clip-text text-8xl font-black tracking-tight text-transparent select-none sm:text-9xl"
                >
                    503
                </h1>

                <h2
                    class="mt-4 text-2xl font-bold tracking-tight text-slate-900 dark:text-gray-100"
                >
                    রক্ষণাবেক্ষণের কাজ চলছে
                </h2>

                <p
                    class="mx-auto mt-3 max-w-sm text-sm leading-relaxed text-slate-500 dark:text-gray-400"
                >
                    আপনার অভিজ্ঞতা আরও উন্নত করতে আমরা সিস্টেমের আধুনিকায়নের
                    কাজ করছি। খুব শীঘ্রই আমরা পুনরায় অনলাইনে ফিরব। আপনার
                    ধৈর্যের জন্য অসংখ্য ধন্যবাদ!
                </p>

                <!-- Navigation Buttons -->
                <div
                    class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row"
                >
                    <Link href="/">
                        <k-button clear>
                            <Home class="h-4 w-4" />
                            পেজটি রিফ্রেশ করুন
                        </k-button>
                    </Link>
                </div>

                <!-- Divider -->
                <div
                    class="my-8 h-px bg-slate-200/60 dark:bg-gray-700/60"
                ></div>

                <!-- Directory / Helpful Links -->
                <div class="text-left">
                    <h3
                        class="text-xs font-bold tracking-wider text-slate-400 uppercase dark:text-gray-500"
                    >
                        Stay Connected & Updates
                    </h3>

                    <div class="mt-3 grid grid-cols-1 gap-2.5">
                        <!-- Facebook Page -->
                        <a
                            href="https://facebook.com/hscstackbd"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="group flex items-center justify-between rounded-xl border border-slate-100 bg-white/40 p-3 text-sm font-medium text-slate-700 transition-all duration-150 hover:border-slate-200 hover:bg-white/80 hover:shadow-sm dark:border-gray-800 dark:bg-gray-900/40 dark:text-gray-300 dark:hover:border-gray-700 dark:hover:bg-gray-900/80"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 transition-colors group-hover:bg-indigo-100 dark:bg-indigo-500/10 dark:text-indigo-400 dark:group-hover:bg-indigo-500/20"
                                >
                                    <Facebook class="h-4 w-4" />
                                </div>
                                <span>Follow us on Facebook</span>
                            </div>
                            <ChevronRight
                                class="h-4 w-4 text-slate-400 transition-transform duration-200 group-hover:translate-x-0.5 dark:text-gray-500"
                            />
                        </a>

                        <!-- GitHub Profile -->
                        <a
                            href="https://github.com/hscstack"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="group flex items-center justify-between rounded-xl border border-slate-100 bg-white/40 p-3 text-sm font-medium text-slate-700 transition-all duration-150 hover:border-slate-200 hover:bg-white/80 hover:shadow-sm dark:border-gray-800 dark:bg-gray-900/40 dark:text-gray-300 dark:hover:border-gray-700 dark:hover:bg-gray-900/80"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-900 text-white transition-colors group-hover:bg-slate-800 dark:bg-gray-100 dark:text-gray-900 dark:group-hover:bg-gray-200"
                                >
                                    <Github class="h-4 w-4" />
                                </div>
                                <span>Check our GitHub</span>
                            </div>
                            <ChevronRight
                                class="h-4 w-4 text-slate-400 transition-transform duration-200 group-hover:translate-x-0.5 dark:text-gray-500"
                            />
                        </a>

                        <!-- Support Email -->
                        <a
                            href="mailto:support@hscstack.com"
                            class="group flex items-center justify-between rounded-xl border border-slate-100 bg-white/40 p-3 text-sm font-medium text-slate-700 transition-all duration-150 hover:border-slate-200 hover:bg-white/80 hover:shadow-sm dark:border-gray-800 dark:bg-gray-900/40 dark:text-gray-300 dark:hover:border-gray-700 dark:hover:bg-gray-900/80"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600 transition-colors group-hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-400 dark:group-hover:bg-rose-500/20"
                                >
                                    <Mail class="h-4 w-4" />
                                </div>
                                <span>Contact Support</span>
                            </div>
                            <ChevronRight
                                class="h-4 w-4 text-slate-400 transition-transform duration-200 group-hover:translate-x-0.5 dark:text-gray-500"
                            />
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </kPage>
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
