<script setup lang="ts">
import { Download, RotateCcw, X, ZoomIn, ZoomOut } from 'lucide-vue-next';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const modelValue = defineModel<boolean>({ default: false });

const props = withDefaults(
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

const emit = defineEmits<{
    (e: 'download'): void;
    (e: 'close'): void;
}>();

const scale = ref(1);
const translateX = ref(0);
const translateY = ref(0);
const isDragging = ref(false);

let startX = 0;
let startY = 0;
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

const toggleZoom = () => {
    if (scale.value > 1) {
        resetZoom();
    } else {
        scale.value = 2.5;
    }
};

const zoomIn = () => {
    scale.value = Math.min(5, scale.value + 0.5);
};

const zoomOut = () => {
    scale.value = Math.max(1, scale.value - 0.5);

    if (scale.value === 1) {
        resetZoom();
    }
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
    const clientX = 'touches' in e ? e.touches[0].clientX : e.clientX;
    const clientY = 'touches' in e ? e.touches[0].clientY : e.clientY;

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

    if (!isDragging.value || scale.value === 1) {
        return;
    }

    e.preventDefault();

    const clientX = 'touches' in e ? e.touches[0].clientX : e.clientX;
    const clientY = 'touches' in e ? e.touches[0].clientY : e.clientY;

    translateX.value = clientX - startX;
    translateY.value = clientY - startY;
};

const handlePointerUp = () => {
    isDragging.value = false;

    if (scale.value < 1) {
        resetZoom();
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

const handleDownload = () => {
    emit('download');

    if (props.src) {
        const downloadUrl = props.src.includes('?')
            ? `${props.src}&download=1`
            : `${props.src}?download=1`;

        const link = document.createElement('a');
        link.href = downloadUrl;
        link.download = props.title || props.alt || 'download';
        link.target = '_blank';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
};

const handleKeyDown = (e: KeyboardEvent) => {
    if (!modelValue.value) {
        return;
    }

    if (e.key === 'Escape') {
        close();
    } else if (e.key === '+' || e.key === '=') {
        zoomIn();
    } else if (e.key === '-' || e.key === '_') {
        zoomOut();
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
                class="fixed inset-0 z-[120] flex touch-none items-center justify-center bg-slate-950/95 backdrop-blur-sm select-none"
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

                    <!-- Zoom Out -->
                    <button
                        v-if="scale > 1"
                        @click.stop="zoomOut"
                        type="button"
                        class="cursor-pointer rounded-full bg-white/10 p-2.5 text-white backdrop-blur-md transition-all hover:bg-white/20 active:scale-95 sm:p-3 dark:bg-gray-900/10 dark:hover:bg-gray-900/20"
                        title="Zoom out (-)"
                        aria-label="Zoom out"
                    >
                        <ZoomOut class="h-4 w-4 sm:h-5 sm:w-5" />
                    </button>

                    <!-- Zoom In -->
                    <button
                        @click.stop="zoomIn"
                        type="button"
                        class="cursor-pointer rounded-full bg-white/10 p-2.5 text-white backdrop-blur-md transition-all hover:bg-white/20 active:scale-95 sm:p-3 dark:bg-gray-900/10 dark:hover:bg-gray-900/20"
                        title="Zoom in (+)"
                        aria-label="Zoom in"
                    >
                        <ZoomIn class="h-4 w-4 sm:h-5 sm:w-5" />
                    </button>

                    <!-- Reset Zoom Button -->
                    <button
                        v-if="scale > 1"
                        @click.stop="resetZoom"
                        type="button"
                        class="cursor-pointer rounded-full bg-white/10 p-2.5 text-white backdrop-blur-md transition-all hover:bg-white/20 active:scale-95 sm:p-3 dark:bg-gray-900/10 dark:hover:bg-gray-900/20"
                        title="Reset zoom (R)"
                        aria-label="Reset zoom"
                    >
                        <RotateCcw class="h-4 w-4 sm:h-5 sm:w-5" />
                    </button>

                    <!-- Download Button -->
                    <button
                        v-if="showDownload && src"
                        @click.stop="handleDownload"
                        type="button"
                        class="cursor-pointer rounded-full bg-white/10 p-2.5 text-white backdrop-blur-md transition-all hover:bg-white/20 active:scale-95 sm:p-3 dark:bg-gray-900/10 dark:hover:bg-gray-900/20"
                        title="Download image"
                        aria-label="Download image"
                    >
                        <Download class="h-4 w-4 sm:h-5 sm:w-5" />
                    </button>

                    <!-- Close Button -->
                    <button
                        @click.stop="close"
                        type="button"
                        class="cursor-pointer rounded-full bg-white/10 p-2.5 text-white backdrop-blur-md transition-all hover:bg-white/20 active:scale-95 sm:p-3 dark:bg-gray-900/10 dark:hover:bg-gray-900/20"
                        title="Close (Esc)"
                        aria-label="Close"
                    >
                        <X class="h-4 w-4 sm:h-5 sm:w-5" />
                    </button>
                </div>

                <!-- Centered Image with transform -->
                <img
                    :src="src"
                    :alt="alt || title"
                    @dblclick="toggleZoom"
                    class="pointer-events-none max-h-[90vh] max-w-[90vw] rounded object-contain shadow-2xl transition-transform duration-75 ease-out"
                    :style="{
                        transform: `translate(${translateX}px, ${translateY}px) scale(${scale})`,
                    }"
                />
            </div>
        </Transition>
    </Teleport>
</template>
