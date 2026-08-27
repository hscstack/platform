<script setup lang="ts">
import { BadgeCheck } from 'lucide-vue-next';

withDefaults(
    defineProps<{
        size?: string;
        title?: string;
    }>(),
    {
        size: 'h-3.5 w-3.5',
        title: 'Verified Contributor',
    },
);
</script>

<template>
    <BadgeCheck
        :class="[
            size,
            'shrink-0 fill-blue-50 stroke-[2.2] text-blue-600 dark:fill-blue-950/60 dark:text-blue-400',
        ]"
        :title="title"
    />
</template>
