<script setup lang="ts">
import { ref, watch } from 'vue';

import { useOrientation } from '@/lib/useOrientation';

import BottomNav from './BottomNav.vue';
import OverflowDrawer from './OverflowDrawer.vue';
import SideRail from './SideRail.vue';

const { isLandscape } = useOrientation();

const railCollapsed = ref<boolean>(false);

// Persist rail state
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
    <div>
        <!-- Landscape: Side rail -->
        <SideRail
            v-if="isLandscape"
            :collapsed="railCollapsed"
            @toggle="toggleRail"
        />

        <!-- Portrait: Top hamburger + drawer + bottom nav -->
        <OverflowDrawer
            v-if="!isLandscape"
            :open="drawerOpen"
            @update:open="drawerOpen = $event"
            @close="drawerOpen = false"
        />
        <BottomNav v-if="!isLandscape" />
    </div>
</template>
