<script setup lang="ts">
import { ref, watch } from 'vue';

import FloatingShareBar from '@/components/FloatingShareBar.vue';
import Footer from '@/components/Footer.vue';
import LoadingSpinner from '@/components/LoadingSpinner.vue';
import BottomNav from '@/components/navigation/BottomNav.vue';
import OverflowDrawer from '@/components/navigation/OverflowDrawer.vue';
import SideRail from '@/components/navigation/SideRail.vue';
import ToastNotification from '@/components/ToastNotification.vue';
import { useOrientation } from '@/lib/useOrientation';

const { isLandscape, isHydrated } = useOrientation();

const railCollapsed = ref(false);

if (typeof window !== 'undefined') {
    try {
        const saved = localStorage.getItem('rail_collapsed');

        if (saved !== null) {
            railCollapsed.value = saved === 'true';
        }
    } catch {}
}

watch(railCollapsed, (v) => {
    try {
        localStorage.setItem('rail_collapsed', String(v));
    } catch {}
});

const toggleRail = () => {
    railCollapsed.value = !railCollapsed.value;
};

const drawerOpen = ref(false);
</script>

<template>
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

        <!-- Orientation-aware shell: landscape = side rail, portrait = bottom nav + top hamburger -->
        <div class="relative z-10 flex min-h-screen">
            <!-- Landscape side rail (desktop) -->
            <SideRail
                v-if="isHydrated ? isLandscape : true"
                :collapsed="railCollapsed"
                class="hidden landscape:flex"
                @toggle="toggleRail"
            />

            <div class="flex min-h-screen flex-1 flex-col">
                <!-- Portrait top bar + hamburger (mobile) -->
                <OverflowDrawer
                    :open="drawerOpen"
                    class="landscape:hidden"
                    @update:open="drawerOpen = $event"
                    @close="drawerOpen = false"
                />

                <main
                    :class="[
                        'flex-1',
                        isHydrated && !isLandscape
                            ? 'pb-16'
                            : 'min-h-[calc(100vh-4rem)]',
                    ]"
                >
                    <slot />
                </main>

                <!-- Footer: hidden on portrait to free space (bottom nav occupies footer area) -->
                <Footer
                    v-if="
                        $page.component !== 'Chat/Index' &&
                        (isHydrated ? isLandscape : true)
                    "
                    class="hidden landscape:block"
                />
            </div>
        </div>

        <BottomNav v-if="isHydrated ? !isLandscape : false" />
        <FloatingShareBar />
        <ToastNotification />
    </div>
</template>
