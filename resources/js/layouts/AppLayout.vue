<script setup lang="ts">
import { computed, ref, watch } from 'vue';

import AtmosphericBackground from '@/components/AtmosphericBackground.vue';
import FloatingShareBar from '@/components/FloatingShareBar.vue';
import Footer from '@/components/Footer.vue';
import LoadingSpinner from '@/components/LoadingSpinner.vue';
import {
    SiteBottomNav as BottomNav,
    SiteDrawer as OverflowDrawer,
    SiteRail as SideRail,
} from '@/components/navigation/Navigation';
import ToastNotification from '@/components/ToastNotification.vue';
import { useBreakpoint } from '@/lib/useBreakpoint';
import { useOrientation } from '@/lib/useOrientation';

const { isLandscape, isHydrated: orientationHydrated } = useOrientation();
const { isMobile, isHydrated: breakpointHydrated } = useBreakpoint(1024);
const isHydrated = computed(
    () => orientationHydrated.value && breakpointHydrated.value,
);
// Mobile OR portrait → bottom nav; Desktop + landscape → side rail (keeps spec: "mobile or portrait")
const showSideRail = computed(() =>
    isHydrated.value ? !isMobile.value && isLandscape.value : true,
);
const showBottomNav = computed(() =>
    isHydrated.value ? isMobile.value || !isLandscape.value : false,
);

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
        class="relative min-h-screen overflow-x-clip bg-slate-50 font-sans text-slate-900 antialiased selection:bg-indigo-600 selection:text-white dark:bg-gray-950 dark:text-gray-100"
    >
        <AtmosphericBackground />

        <!-- Responsive shell: desktop + landscape = side rail; mobile OR portrait = bottom nav + top hamburger -->
        <div class="relative z-10 flex min-h-screen">
            <!-- Desktop side rail -->
            <SideRail
                v-if="showSideRail"
                :collapsed="railCollapsed"
                @toggle="toggleRail"
            />

            <div class="flex min-h-screen w-full min-w-0 flex-1 flex-col">
                <!-- Mobile top bar + hamburger -->
                <OverflowDrawer
                    v-if="showBottomNav"
                    :open="drawerOpen"
                    @update:open="drawerOpen = $event"
                    @close="drawerOpen = false"
                />

                <main
                    :class="[
                        'min-w-0 flex-1',
                        showBottomNav
                            ? 'pb-[calc(4.5rem+env(safe-area-inset-bottom))]'
                            : 'min-h-[calc(100vh-4rem)] pb-4',
                    ]"
                >
                    <slot />
                </main>

                <!-- Footer: shown on desktop across pages (except Chat/Index); on mobile/portrait shown ONLY on Home/SSC landing pages -->
                <div
                    v-if="
                        $page.component !== 'Chat/Index' &&
                        (!showBottomNav || $page.component === 'Home')
                    "
                    :class="
                        showBottomNav
                            ? 'pb-[calc(4.5rem+env(safe-area-inset-bottom))]'
                            : ''
                    "
                >
                    <Footer />
                </div>
            </div>
        </div>

        <BottomNav v-if="showBottomNav" />
        <FloatingShareBar />
        <ToastNotification />
    </div>
</template>
