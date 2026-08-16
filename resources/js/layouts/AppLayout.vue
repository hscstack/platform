<script setup lang="ts">
import { kPage } from 'konsta/vue';
import Footer from '@/components/Footer.vue';
import KonstaProvider from '@/components/KonstaProvider.vue';
import LoadingSpinner from '@/components/LoadingSpinner.vue';
import NavBar from '@/components/NavBar.vue';
import ToastNotification from '@/components/ToastNotification.vue';
</script>

<template>
    <KonstaProvider>
        <LoadingSpinner />
        <div
            class="relative min-h-screen bg-slate-50 font-sans text-slate-900 antialiased selection:bg-indigo-600 selection:text-white dark:bg-gray-950 dark:text-gray-100"
        >
            <div class="pointer-events-none fixed inset-0 z-0 overflow-hidden">
                <div
                    class="absolute -top-[30%] left-1/2 h-[900px] w-[1200px] -translate-x-1/2 rounded-full bg-[radial-gradient(ellipse_at_center,rgba(99,102,241,0.18)_0%,rgba(165,180,252,0.05)_50%,transparent_70%)] blur-[120px] dark:bg-[radial-gradient(ellipse_at_center,rgba(99,102,241,0.06)_0%,rgba(165,180,252,0.02)_50%,transparent_70%)]"
                ></div>

                <div
                    class="absolute top-[20%] -right-[10%] h-[600px] w-[600px] rounded-full bg-[radial-gradient(circle_at_center,rgba(56,189,248,0.15)_0%,transparent_65%)] blur-[100px] dark:bg-[radial-gradient(circle_at_center,rgba(56,189,248,0.05)_0%,transparent_65%)]"
                ></div>
                <div
                    class="absolute -bottom-[10%] -left-[10%] h-[700px] w-[700px] rounded-full bg-[radial-gradient(circle_at_center,rgba(244,63,94,0.06)_0%,transparent_70%)] blur-[110px] dark:bg-[radial-gradient(circle_at_center,rgba(244,63,94,0.02)_0%,transparent_70%)]"
                ></div>
            </div>

            <kPage>
                <div>
                    <NavBar />
                    <slot />
                </div>

                <Footer />
            </kPage>
            <ToastNotification />
        </div>
    </KonstaProvider>
</template>
