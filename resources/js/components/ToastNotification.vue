<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Check, AlertCircle, Info, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface Toast {
    id: number;
    message: string;
    type: 'success' | 'error' | 'info';
}

const page = usePage();
const toasts = ref<Toast[]>([]);
let toastId = 0;

const addToast = (message: string, type: 'success' | 'error' | 'info') => {
    const id = ++toastId;
    toasts.value.unshift({ id, message, type });

    setTimeout(() => {
        removeToast(id);
    }, 4500);
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

        if (flash?.info) {
            addToast(flash.info, 'info');
        }
    },
    { deep: true, immediate: true },
);
</script>

<template>
    <div
        class="pointer-events-none fixed top-4 right-4 z-[9999] flex w-[calc(100vw-2rem)] max-w-sm flex-col gap-2.5 sm:top-6 sm:right-6"
    >
        <TransitionGroup name="toast" tag="div" class="flex flex-col gap-2.5">
            <div
                v-for="toast in toasts"
                :key="toast.id"
                class="pointer-events-auto group relative flex items-start gap-3 rounded-2xl border border-slate-200/90 bg-white/95 p-3.5 shadow-xl shadow-slate-900/5 backdrop-blur-md transition-all duration-200 hover:shadow-2xl dark:border-gray-800 dark:bg-gray-900/95 dark:shadow-black/40"
            >
                <!-- Status Icon Badge -->
                <div
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-xl border transition-transform duration-200 group-hover:scale-105"
                    :class="{
                        'border-emerald-500/20 bg-emerald-50 text-emerald-600 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-400':
                            toast.type === 'success',
                        'border-rose-500/20 bg-rose-50 text-rose-600 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-400':
                            toast.type === 'error',
                        'border-indigo-500/20 bg-indigo-50 text-indigo-600 dark:border-indigo-500/20 dark:bg-indigo-500/10 dark:text-indigo-400':
                            toast.type === 'info',
                    }"
                >
                    <Check
                        v-if="toast.type === 'success'"
                        class="h-4 w-4 stroke-[2.5]"
                    />
                    <AlertCircle
                        v-else-if="toast.type === 'error'"
                        class="h-4 w-4 stroke-[2.2]"
                    />
                    <Info v-else class="h-4 w-4 stroke-[2.2]" />
                </div>

                <!-- Message Content -->
                <div class="min-w-0 flex-1 pt-0.5">
                    <p
                        class="text-[13px] leading-snug font-semibold text-slate-800 break-words dark:text-gray-200"
                    >
                        {{ toast.message }}
                    </p>
                </div>

                <!-- Close Button -->
                <button
                    type="button"
                    @click="removeToast(toast.id)"
                    aria-label="Dismiss notification"
                    class="cursor-pointer rounded-lg p-1 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                >
                    <X class="h-3.5 w-3.5" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}

.toast-enter-from {
    opacity: 0;
    transform: translateY(-8px) scale(0.96);
}

.toast-leave-to {
    opacity: 0;
    transform: translateY(-8px) scale(0.96);
}

.toast-move {
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
</style>
