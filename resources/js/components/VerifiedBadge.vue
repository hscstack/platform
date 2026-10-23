<script setup lang="ts">
import { BadgeCheck } from 'lucide-vue-next';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        size?: 'xs' | 'sm' | 'md' | 'lg' | string;
        title?: string;
    }>(),
    {
        size: 'sm',
        title: 'Verified Contributor',
    },
);

const sizeClass = computed(() => {
    switch (props.size) {
        case 'xs':
            return 'h-3 w-3';
        case 'sm':
            return 'h-3.5 w-3.5';
        case 'md':
            return 'h-4 w-4';
        case 'lg':
            return 'h-5 w-5';
        default:
            return props.size || 'h-3.5 w-3.5';
    }
});
</script>

<template>
    <BadgeCheck
        :class="[
            sizeClass,
            'inline-block shrink-0 fill-blue-50 stroke-[2.2] text-blue-600 dark:fill-blue-950/60 dark:text-blue-400',
        ]"
        :title="title"
    />
</template>
