<script setup lang="ts">
import ResourceFormModal from '@/components/resources/ResourceFormModal.vue';

defineProps<{
    isOpen: boolean;
    resource?: any;
    node?: any;
    defaultType?: any;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'update:isOpen', value: boolean): void;
    (e: 'save', payload: any): void;
}>();
</script>

<template>
    <ResourceFormModal
        :is-open="isOpen"
        :resource="resource"
        :node="node"
        :default-type="defaultType"
        @close="emit('close')"
        @update:is-open="emit('update:isOpen', $event)"
        @save="emit('save', $event)"
    />
</template>
