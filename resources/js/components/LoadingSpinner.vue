<script setup lang="ts">
import { isLoading } from '@/loading';
</script>

<template>
    <div
        v-if="isLoading"
        class="pointer-events-none fixed top-4 right-4 z-[9999]"
    >
        <div
            class="h-6 w-6 animate-spin rounded-full border-2 border-[#4b3aef] border-t-transparent"
        />
    </div>
</template>
