<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { CheckCircle2, AlertTriangle, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface Toast {
    id: number;
    message: string;
    type: 'success' | 'error';
}

const page = usePage();
const toasts = ref<Toast[]>([]);
let toastId = 0;

const addToast = (message: string, type: 'success' | 'error') => {
    const id = toastId++;
    toasts.value.push({ id, message, type });
    setTimeout(() => {
        removeToast(id);
    }, 5000);
};

const removeToast = (id: number) => {
    toasts.value = toasts.value.filter((t) => t.id !== id);
};

// Watch Inertia page flash props for new messages
watch(
    () => page.props.flash,
    (flash: any) => {
        if (flash?.success) {
            addToast(flash.success, 'success');
        }

        if (flash?.error) {
            addToast(flash.error, 'error');
        }
    },
    { deep: true, immediate: true },
);
</script>

<template>
    <div
        class="pointer-events-none fixed top-4 right-4 z-[9999] flex w-full max-w-sm flex-col gap-3 px-4 sm:px-0"
    >
        <TransitionGroup name="toast" tag="div" class="flex flex-col gap-2.5">
            <div
                v-for="toast in toasts"
                :key="toast.id"
                class="pointer-events-auto flex w-full items-start gap-3 rounded-2xl border p-4 shadow-xl backdrop-blur-md transition-all duration-300 hover:scale-[1.01]"
                :class="
                    toast.type === 'success'
                        ? 'border-emerald-500/25 bg-emerald-50/95 text-emerald-950 shadow-emerald-100/30 dark:border-emerald-500/20 dark:bg-emerald-950/90 dark:text-emerald-100 dark:shadow-emerald-900/30'
                        : 'border-rose-500/25 bg-rose-50/95 text-rose-950 shadow-rose-100/30 dark:border-rose-500/20 dark:bg-rose-950/90 dark:text-rose-100 dark:shadow-rose-900/30'
                "
            >
                <!-- Icon -->
                <component
                    :is="
                        toast.type === 'success' ? CheckCircle2 : AlertTriangle
                    "
                    class="h-5 w-5 shrink-0"
                    :class="
                        toast.type === 'success'
                            ? 'text-emerald-600 dark:text-emerald-400'
                            : 'text-rose-600 dark:text-rose-400'
                    "
                />

                <!-- Message -->
                <p class="flex-1 text-sm leading-relaxed font-semibold">
                    {{ toast.message }}
                </p>

                <!-- Close Button -->
                <button
                    @click="removeToast(toast.id)"
                    class="cursor-pointer rounded-lg p-0.5 text-slate-400 transition-colors hover:bg-black/5 hover:text-slate-700 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.toast-enter-from {
    opacity: 0;
    transform: translateX(40px) scale(0.9);
}
.toast-leave-to {
    opacity: 0;
    transform: translateX(40px) scale(0.9);
}
</style>
