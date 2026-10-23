<script setup lang="ts">
import ResourceFormModal from '@/components/resources/ResourceFormModal.vue';

defineProps<{
    isOpen: boolean;
    node: {
        id: number;
        name: string;
    };
    defaultType?: 'note' | 'practical' | 'marked_book' | 'video' | 'other';
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'update:isOpen', value: boolean): void;
}>();
</script>

<template>
    <ResourceFormModal
        :is-open="isOpen"
        :node="node"
        :default-type="defaultType"
        @close="emit('close')"
        @update:is-open="emit('update:isOpen', $event)"
    />
</template>
