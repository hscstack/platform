<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { kToast } from 'konsta/vue';
import { ref, watch } from 'vue';

interface Toast {
    message: string;
    type: 'success' | 'error';
}

const page = usePage();
const isOpened = ref(false);
const toastData = ref<Toast>({ message: '', type: 'success' });

let timeout: ReturnType<typeof setTimeout> | undefined;

const showToast = (message: string, type: 'success' | 'error') => {
    if (timeout) clearTimeout(timeout);
    toastData.value = { message, type };
    isOpened.value = true;
    timeout = setTimeout(() => {
        isOpened.value = false;
    }, 4000);
};

// Watch Inertia page flash props for new messages
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
    <kToast
        :opened="isOpened"
        position="top-right"
        :class="
            toastData.type === 'success' ? 'ktoast-success' : 'ktoast-error'
        "
    >
        <template #default>
            <span>{{ toastData.message }}</span>
        </template>
    </kToast>
</template>

<style scoped>
.ktoast-success {
    --k-toast-bg-color: rgb(16 185 129 / 0.9);
}
.ktoast-error {
    --k-toast-bg-color: rgb(239 68 68 / 0.9);
}
</style>
