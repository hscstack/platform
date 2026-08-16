<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { kToast } from 'konsta/vue';
import { ref, computed, watch } from 'vue';

interface Toast {
    message: string;
    type: 'success' | 'error';
}

const page = usePage();
const isOpened = ref(false);
const toastData = ref<Toast>({ message: '', type: 'success' });

let timeout: ReturnType<typeof setTimeout> | undefined;

const showToast = (message: string, type: 'success' | 'error') => {
    if (timeout) {
        clearTimeout(timeout);
    }

    toastData.value = { message, type };
    isOpened.value = true;
    timeout = setTimeout(() => {
        isOpened.value = false;
    }, 4000);
};

const toastColors = computed(() => {
    if (toastData.value.type === 'success') {
        return {
            bgIos: 'bg-green-500/90',
            bgMaterial: 'bg-green-500',
            textIos: 'text-white',
            textMaterial: 'text-white',
        };
    }

    return {
        bgIos: 'bg-red-500/90',
        bgMaterial: 'bg-red-500',
        textIos: 'text-white',
        textMaterial: 'text-white',
    };
});

watch(
    () => page.props.flash,
    (flash: any) => {
        if (flash?.success) {
            showToast(flash.success, 'success');
        }

        if (flash?.error) {
            showToast(flash.error, 'error');
        }
    },
    { deep: true, immediate: true },
);
</script>

<template>
    <kToast :opened="isOpened" position="center" :colors="toastColors">
        <template #default>
            <span>{{ toastData.message }}</span>
        </template>
    </kToast>
</template>
