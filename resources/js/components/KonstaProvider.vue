<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { kApp } from 'konsta/vue';
import { useDarkMode } from '@/lib/useDarkMode';

const props = withDefaults(
    defineProps<{
        theme?: 'ios' | 'material' | 'auto';
    }>(),
    {
        theme: 'auto',
    },
);

const { isDark } = useDarkMode();

const detectedTheme = ref<'ios' | 'material'>('material');

onMounted(() => {
    if (props.theme === 'auto') {
        const ua = navigator.userAgent;
        detectedTheme.value = /iPhone|iPad|iPod/.test(ua) ? 'ios' : 'material';
    } else {
        detectedTheme.value = props.theme;
    }
});

const activeTheme = computed(() => detectedTheme.value);
</script>

<template>
    <k-app :theme="activeTheme" :dark="isDark">
        <slot />
    </k-app>
</template>
