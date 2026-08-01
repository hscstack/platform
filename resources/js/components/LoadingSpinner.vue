<script setup lang="ts">
import { isLoading } from '@/loading';
</script>

<template>
    <div v-if="isLoading" class="fixed top-4 right-4 z-50">
        <svg
            class="h-7 w-7 animate-spin text-red-600"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.4"
            stroke-linecap="round"
        >
            <!-- web spokes -->
            <line x1="12" y1="12" x2="12" y2="1" />
            <line x1="12" y1="12" x2="20.4" y2="4.6" />
            <line x1="12" y1="12" x2="23" y2="12" />
            <line x1="12" y1="12" x2="20.4" y2="19.4" />
            <line x1="12" y1="12" x2="12" y2="23" />
            <line x1="12" y1="12" x2="3.6" y2="19.4" />
            <line x1="12" y1="12" x2="1" y2="12" />
            <line x1="12" y1="12" x2="3.6" y2="4.6" />
            <!-- concentric web rings -->
            <circle cx="12" cy="12" r="4.2" opacity="0.9" />
            <circle cx="12" cy="12" r="7.8" opacity="0.55" />
            <circle cx="12" cy="12" r="11" opacity="0.25" />
        </svg>
    </div>
</template>
