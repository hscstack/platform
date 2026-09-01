<script setup lang="ts">
import ImageViewerModal from '@/components/ImageViewerModal.vue';

const modelValue = defineModel<boolean>({ default: false });

withDefaults(
    defineProps<{
        src: string;
        alt?: string;
        title?: string;
        showDownload?: boolean;
    }>(),
    {
        alt: 'Attached Image',
        title: '',
        showDownload: true,
    },
);

defineEmits<{
    (e: 'download'): void;
    (e: 'close'): void;
}>();
</script>

<template>
    <ImageViewerModal
        v-model="modelValue"
        :src="src"
        :alt="alt"
        :title="title"
        :show-download="showDownload"
        @download="$emit('download')"
        @close="$emit('close')"
    />
</template>
