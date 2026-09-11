<script setup lang="ts">
import { Minimize2, RotateCcw } from 'lucide-vue-next';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const modelValue = defineModel<boolean>({ default: false });

withDefaults(
    defineProps<{
        src: string;
        alt?: string;
        title?: string;
    }>(),
    {
        alt: 'Attached Image',
        title: '',
    },
);

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const backdropRef = ref<HTMLElement | null>(null);
const scale = ref(1);
const translateX = ref(0);
const translateY = ref(0);
const isDragging = ref(false);

let startX = 0;
let startY = 0;
let pointerDownScreenX = 0;
let pointerDownScreenY = 0;
let hasMoved = false;
let initialScale = 1;
let startTouchDistance = 0;

const resetZoom = () => {
    scale.value = 1;
    translateX.value = 0;
    translateY.value = 0;
};

const close = () => {
    modelValue.value = false;
    resetZoom();
    emit('close');
};

const getTouchDistance = (e: TouchEvent) => {
    return Math.hypot(
        e.touches[0].clientX - e.touches[1].clientX,
        e.touches[0].clientY - e.touches[1].clientY,
    );
};

const handlePointerDown = (e: MouseEvent | TouchEvent) => {
    if ('touches' in e && e.touches.length === 2) {
        isDragging.value = false;
        initialScale = scale.value;
        startTouchDistance = getTouchDistance(e);

        return;
    }

    isDragging.value = true;
    hasMoved = false;
    const clientX = 'touches' in e ? e.touches[0].clientX : e.clientX;
    const clientY = 'touches' in e ? e.touches[0].clientY : e.clientY;

    pointerDownScreenX = clientX;
    pointerDownScreenY = clientY;

    startX = clientX - translateX.value;
    startY = clientY - translateY.value;
};

const handlePointerMove = (e: MouseEvent | TouchEvent) => {
    if ('touches' in e && e.touches.length === 2) {
        e.preventDefault();
        const currentDistance = getTouchDistance(e);

        if (startTouchDistance > 0) {
            const newScale =
                initialScale * (currentDistance / startTouchDistance);
            scale.value = Math.min(Math.max(newScale, 1), 5);
        }

        return;
    }

    if (!isDragging.value) {
        return;
    }

    const clientX = 'touches' in e ? e.touches[0].clientX : e.clientX;
    const clientY = 'touches' in e ? e.touches[0].clientY : e.clientY;

    if (
        Math.hypot(clientX - pointerDownScreenX, clientY - pointerDownScreenY) >
        5
    ) {
        hasMoved = true;
    }

    if (scale.value === 1) {
        return;
    }

    e.preventDefault();

    translateX.value = clientX - startX;
    translateY.value = clientY - startY;
};

const handlePointerUp = () => {
    isDragging.value = false;

    if (scale.value < 1) {
        resetZoom();
    }
};

const handleBackdropClick = (e: MouseEvent) => {
    if (hasMoved) {
        return;
    }

    if (e.target === backdropRef.value) {
        close();
    }
};

const handleWheel = (e: WheelEvent) => {
    e.preventDefault();
    const zoomIntensity = 0.1;
    const delta = e.deltaY < 0 ? 1 : -1;
    const newScale = scale.value + delta * zoomIntensity;
    scale.value = Math.min(Math.max(newScale, 1), 5);

    if (scale.value === 1) {
        resetZoom();
    }
};

const handleKeyDown = (e: KeyboardEvent) => {
    if (!modelValue.value) {
        return;
    }

    if (e.key === 'Escape') {
        close();
    } else if (e.key === 'r' || e.key === '0') {
        resetZoom();
    }
};

watch(modelValue, (val) => {
    if (!val) {
        resetZoom();
    }

    if (typeof document !== 'undefined') {
        document.body.style.overflow = val ? 'hidden' : '';
    }
});

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeyDown);

    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="modelValue"
                ref="backdropRef"
                class="fixed inset-0 z-[120] flex cursor-zoom-out touch-none items-center justify-center bg-slate-950/95 backdrop-blur-sm select-none"
                @click="handleBackdropClick"
                @wheel="handleWheel"
                @mousedown="handlePointerDown"
                @mousemove="handlePointerMove"
                @mouseup="handlePointerUp"
                @mouseleave="handlePointerUp"
                @touchstart="handlePointerDown"
                @touchmove="handlePointerMove"
                @touchend="handlePointerUp"
            >
                <!-- Controls Bar -->
                <div
                    class="fixed top-4 right-4 z-[130] flex items-center gap-2"
                >
                    <!-- Helper Badge -->
                    <div
                        class="hidden items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-[11px] font-medium text-slate-300 backdrop-blur-md sm:flex dark:bg-gray-900/10"
                    >
                        <span>Pinch / Scroll to Zoom</span>
                        <span class="text-slate-500">•</span>
                        <span>Drag to Pan</span>
                    </div>

                    <!-- Reset Zoom Button -->
                    <button
                        v-if="scale > 1"
                        @click.stop="resetZoom"
                        type="button"
                        class="cursor-pointer rounded-full bg-white/10 p-3 text-white backdrop-blur-md transition-all hover:bg-white/20 active:scale-95 dark:bg-gray-900/10 dark:hover:bg-gray-900/20"
                        title="Reset Zoom"
                        aria-label="Reset Zoom"
                    >
                        <RotateCcw class="h-5 w-5" />
                    </button>

                    <!-- Exit Fullscreen Button -->
                    <button
                        @click.stop="close"
                        type="button"
                        class="cursor-pointer rounded-full bg-white/10 p-3 text-white backdrop-blur-md transition-all hover:bg-white/20 active:scale-95 dark:bg-gray-900/10 dark:hover:bg-gray-900/20"
                        title="Exit Fullscreen"
                        aria-label="Exit Fullscreen"
                    >
                        <Minimize2 class="h-5 w-5" />
                    </button>
                </div>

                <!-- Centered Image with transform -->
                <img
                    :src="src"
                    :alt="alt || title"
                    class="pointer-events-auto max-h-[90vh] max-w-[90vw] cursor-default rounded object-contain shadow-2xl transition-transform duration-75 ease-out"
                    :class="{ 'cursor-grab active:cursor-grabbing': scale > 1 }"
                    :style="{
                        transform: `translate(${translateX}px, ${translateY}px) scale(${scale})`,
                    }"
                    @click.stop
                />
            </div>
        </Transition>
    </Teleport>
</template>
