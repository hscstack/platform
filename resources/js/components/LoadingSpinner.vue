<script setup lang="ts">
import { kPreloader } from 'konsta/vue';
import { isLoading } from '@/loading';
</script>

<template>
    <div v-if="isLoading" class="fixed top-4 right-4 z-50">
        <k-preloader />
    </div>
</template>
