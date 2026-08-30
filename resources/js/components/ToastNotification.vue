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
        class="pointer-events-none fixed inset-x-0 top-3.5 z-[9999] flex flex-col items-center gap-2 px-4 sm:top-5"
    >
        <TransitionGroup
            name="toast"
            tag="div"
            class="flex w-full flex-col items-center gap-2"
        >
            <div
                v-for="toast in toasts"
                :key="toast.id"
                @click="removeToast(toast.id)"
                class="group pointer-events-auto relative flex max-w-[calc(100vw-2rem)] cursor-pointer items-center gap-2.5 rounded-full border border-slate-200/80 bg-white/95 px-3.5 py-2 text-xs font-medium text-slate-900 shadow-lg ring-1 shadow-slate-900/5 ring-black/[0.04] backdrop-blur-xl transition-all duration-200 hover:shadow-xl active:scale-[0.98] sm:max-w-md sm:gap-3 sm:px-4 sm:py-2.5 sm:text-sm dark:border-gray-800 dark:bg-gray-900/95 dark:text-gray-100 dark:shadow-2xl dark:shadow-black/60 dark:ring-white/[0.08]"
            >
                <!-- Minimal Status Icon Dot / Pill -->
                <div
                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full sm:h-5.5 sm:w-5.5"
                    :class="{
                        'bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400':
                            toast.type === 'success',
                        'bg-rose-500/10 text-rose-600 dark:bg-rose-500/20 dark:text-rose-400':
                            toast.type === 'error',
                        'bg-indigo-500/10 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400':
                            toast.type === 'info',
                    }"
                >
                    <Check
                        v-if="toast.type === 'success'"
                        class="h-3 w-3 stroke-[2.8] sm:h-3.5 sm:w-3.5"
                    />
                    <AlertCircle
                        v-else-if="toast.type === 'error'"
                        class="h-3 w-3 stroke-[2.8] sm:h-3.5 sm:w-3.5"
                    />
                    <Info
                        v-else
                        class="h-3 w-3 stroke-[2.8] sm:h-3.5 sm:w-3.5"
                    />
                </div>

                <!-- Message -->
                <p
                    class="truncate font-semibold tracking-tight text-slate-800 dark:text-gray-200"
                >
                    {{ toast.message }}
                </p>

                <!-- Subtle Dismiss Action -->
                <button
                    type="button"
                    @click.stop="removeToast(toast.id)"
                    aria-label="Dismiss"
                    class="-mr-1 ml-1 flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                >
                    <X class="h-3 w-3 stroke-[2.5]" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.3s cubic-bezier(0.21, 1.02, 0.73, 1);
}

.toast-enter-from {
    opacity: 0;
    transform: translateY(-16px) scale(0.94);
}

.toast-leave-to {
    opacity: 0;
    transform: translateY(-12px) scale(0.94);
}

.toast-move {
    transition: transform 0.25s cubic-bezier(0.21, 1.02, 0.73, 1);
}
</style>
