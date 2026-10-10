<script setup lang="ts">
import { computed, ref, watch } from 'vue';

import AtmosphericBackground from '@/components/AtmosphericBackground.vue';
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

        <div class="relative z-10 flex min-h-screen">
            <!-- Desktop side rail - Global uniform navigation -->
            <SideRail
                v-if="showSideRail"
                :collapsed="railCollapsed"
                @toggle="toggleRail"
            />

            <div class="flex min-h-screen w-full min-w-0 flex-1 flex-col">
                <!-- Mobile top bar + drawer -->
                <OverflowDrawer
                    v-if="showBottomNav"
                    :open="drawerOpen"
                    @update:open="drawerOpen = $event"
                    @close="drawerOpen = false"
                />

                <!-- Main Content Area -->
                <main
                    :class="[
                        'min-w-0 flex-1 p-3.5 sm:p-6 lg:p-8',
                        showBottomNav
                            ? 'pb-[calc(4.5rem+env(safe-area-inset-bottom))]'
                            : 'min-h-[calc(100vh-4rem)] pb-6',
                    ]"
                >
                    <div
                        class="flex w-full flex-1 flex-col rounded-2xl border border-slate-200/90 bg-white p-4.5 shadow-2xs sm:p-7 md:p-8 dark:border-gray-800 dark:bg-gray-900"
                    >
                        <slot />
                    </div>
                </main>
            </div>
        </div>

        <BottomNav v-if="showBottomNav" />
        <ToastNotification />
    </div>
</template>
