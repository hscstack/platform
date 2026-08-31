<script setup lang="ts">
import { X, ZoomIn, ZoomOut } from 'lucide-vue-next';
import { ref, watch, onMounted, onBeforeUnmount } from 'vue';

const modelValue = defineModel<boolean>({ default: false });

withDefaults(
    defineProps<{
        src: string;
        alt?: string;
    }>(),
    {
        alt: 'Attached Image',
    },
);

const isZoomed = ref(false);

const toggleZoom = () => {
    isZoomed.value = !isZoomed.value;
};

const close = () => {
    modelValue.value = false;
    isZoomed.value = false;
};

const handleKeyDown = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && modelValue.value) {
        close();
    }
};

watch(modelValue, (val) => {
    if (!val) {
        isZoomed.value = false;
    }
});

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeyDown);
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="modelValue"
                class="fixed inset-0 z-[120] flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm sm:p-6"
                @click="close"
            >
                <!-- Controls top right -->
                <div
                    class="fixed top-4 right-4 z-[130] flex items-center gap-2"
                >
                    <button
                        type="button"
                        @click.stop="toggleZoom"
                        class="rounded-xl bg-slate-900/80 p-2.5 text-white/90 shadow-lg backdrop-blur-xs transition hover:bg-slate-800 hover:text-white"
                        :aria-label="isZoomed ? 'Zoom out' : 'Zoom in'"
                    >
                        <ZoomOut v-if="isZoomed" class="h-5 w-5" />
                        <ZoomIn v-else class="h-5 w-5" />
                    </button>
                    <button
                        type="button"
                        @click.stop="close"
                        class="rounded-xl bg-slate-900/80 p-2.5 text-white/90 shadow-lg backdrop-blur-xs transition hover:bg-slate-800 hover:text-white"
                        aria-label="Close"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <!-- Image Container -->
                <div
                    class="flex max-h-full max-w-full items-center justify-center overflow-auto p-2"
                    @click.stop
                >
                    <img
                        :src="src"
                        :alt="alt"
                        @click="toggleZoom"
                        class="cursor-zoom-in rounded-xl object-contain shadow-2xl transition-transform duration-200 select-none"
                        :class="[
                            isZoomed
                                ? 'max-h-none max-w-none scale-125 cursor-zoom-out sm:scale-150'
                                : 'max-h-[85vh] max-w-[90vw]',
                        ]"
                    />
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
