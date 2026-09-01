<script setup lang="ts">
import { computed } from 'vue';
import { getSubjectIcon } from '@/lib/subjectIcons';

const props = withDefaults(
    defineProps<{
        name?: string | null;
        className?: string;
    }>(),
    {
        name: null,
        className: 'h-5 w-5 stroke-[2.2]',
    },
);

const iconComponent = computed(() => getSubjectIcon(props.name));
</script>

<template>
    <component :is="iconComponent" :class="className" />
</template>
