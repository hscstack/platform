<script setup lang="ts">
import { kButton, kCard, kNavbar, kPage } from 'konsta/vue';
import { Wrench, Home, Facebook, Github, Mail } from 'lucide-vue-next';
</script>

<template>
    <kPage>
        <kNavbar title="Service Unavailable" />
        <div
            class="relative flex min-h-[calc(100vh-10rem)] items-center justify-center px-4 py-16 sm:px-6 sm:py-24"
        >
            <!-- Glowing aura behind the card -->
            <div
                class="animate-pulse-glow absolute top-1/2 left-1/2 -z-10 h-72 w-72 -translate-x-1/2 -translate-y-1/2 rounded-full bg-indigo-400 opacity-15 blur-[100px] transition-all duration-700"
            ></div>

            <!-- Glass card content -->
            <k-card outline>
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
                    <k-button fill rounded large href="/">
                        <Home class="h-4 w-4" />
                        পেজটি রিফ্রেশ করুন
                    </k-button>
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
                        <k-button
                            fill
                            rounded
                            large
                            href="https://facebook.com/hscstackbd"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <Facebook class="h-4 w-4" />
                            Follow us on Facebook
                        </k-button>

                        <!-- GitHub Profile -->
                        <k-button
                            fill
                            rounded
                            large
                            href="https://github.com/hscstack"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <Github class="h-4 w-4" />
                            Check our GitHub
                        </k-button>

                        <!-- Support Email -->
                        <k-button
                            fill
                            rounded
                            large
                            href="mailto:support@hscstack.com"
                        >
                            <Mail class="h-4 w-4" />
                            Contact Support
                        </k-button>
                    </div>
                </div>
            </k-card>
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
