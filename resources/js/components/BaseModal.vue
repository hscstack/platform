<script setup lang="ts">
import { X } from 'lucide-vue-next';
import { computed, onBeforeUnmount, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        isOpen?: boolean;
        title?: string | null;
        description?: string | null;
        maxWidth?:
            | 'sm'
            | 'md'
            | 'lg'
            | 'xl'
            | '2xl'
            | '3xl'
            | '4xl'
            | '5xl'
            | 'full';
        position?: 'responsive' | 'center';
        closeOnBackdrop?: boolean;
        closeOnEsc?: boolean;
        showCloseButton?: boolean;
        preventScroll?: boolean;
    }>(),
    {
        isOpen: false,
        title: null,
        description: null,
        maxWidth: 'lg',
        position: 'responsive',
        closeOnBackdrop: true,
        closeOnEsc: true,
        showCloseButton: true,
        preventScroll: true,
    },
);

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'update:isOpen', value: boolean): void;
}>();

const maxWidthClass = computed(() => {
    switch (props.maxWidth) {
        case 'sm':
            return 'max-w-sm';
        case 'md':
            return 'max-w-md';
        case 'lg':
            return 'max-w-lg';
        case 'xl':
            return 'max-w-xl';
        case '2xl':
            return 'max-w-2xl';
        case '3xl':
            return 'max-w-3xl';
        case '4xl':
            return 'max-w-4xl';
        case '5xl':
            return 'max-w-5xl';
        case 'full':
            return 'max-w-full';
        default:
            return 'max-w-lg';
    }
});

const handleClose = () => {
    emit('close');
    emit('update:isOpen', false);
};

const handleBackdropClick = () => {
    if (props.closeOnBackdrop) {
        handleClose();
    }
};

const handleKeyDown = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && props.isOpen && props.closeOnEsc) {
        handleClose();
    }
};

watch(
    () => props.isOpen,
    (open) => {
        if (typeof document === 'undefined') {
            return;
        }

        if (open) {
            if (props.preventScroll) {
                document.body.style.overflow = 'hidden';
            }

            window.addEventListener('keydown', handleKeyDown);
        } else {
            if (props.preventScroll) {
                document.body.style.overflow = '';
            }

            window.removeEventListener('keydown', handleKeyDown);
        }
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
        window.removeEventListener('keydown', handleKeyDown);
    }
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="isOpen"
                role="dialog"
                aria-modal="true"
                class="fixed inset-0 z-50 flex justify-center"
                :class="[
                    position === 'responsive'
                        ? 'items-end p-0 sm:items-center sm:p-4'
                        : 'items-center p-4',
                ]"
            >
                <!-- Backdrop -->
                <div
                    class="fixed inset-0 bg-slate-950/40 backdrop-blur-xs transition-opacity dark:bg-black/60"
                    @click="handleBackdropClick"
                />

                <!-- Dialog Surface -->
                <div
                    class="relative flex max-h-[92vh] w-full flex-col border border-slate-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900"
                    :class="[
                        maxWidthClass,
                        position === 'responsive'
                            ? 'rounded-t-2xl sm:rounded-2xl'
                            : 'rounded-2xl',
                    ]"
                >
                    <!-- Header -->
                    <div
                        v-if="$slots.header || title"
                        class="flex items-center justify-between border-b border-slate-100 px-4 py-3.5 sm:px-6 dark:border-gray-800"
                    >
                        <slot name="header">
                            <div
                                class="flex min-w-0 flex-1 items-center gap-3 pr-3"
                            >
                                <slot name="icon" />
                                <div class="min-w-0 flex-1">
                                    <h3
                                        class="truncate text-base font-bold text-slate-900 dark:text-gray-100"
                                    >
                                        {{ title }}
                                    </h3>
                                    <p
                                        v-if="description"
                                        class="truncate text-xs text-slate-500 dark:text-gray-400"
                                    >
                                        {{ description }}
                                    </p>
                                </div>
                            </div>
                        </slot>

                        <button
                            v-if="showCloseButton"
                            type="button"
                            @click="handleClose"
                            class="cursor-pointer rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                            aria-label="Close modal"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="flex-1 overflow-y-auto">
                        <slot />
                    </div>

                    <!-- Footer -->
                    <div
                        v-if="$slots.footer"
                        class="border-t border-slate-100 bg-slate-50/60 px-4 py-3 sm:px-6 dark:border-gray-800 dark:bg-gray-900/60"
                    >
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
