<script setup lang="ts">
import { ExternalLink, Megaphone, Sparkles, X, Clock } from 'lucide-vue-next';
import { ref, watch, onUnmounted } from 'vue';

interface Props {
    modelValue: boolean;
    countdownSeconds?: number;
    title?: string;
    subtitle?: string;
    ctaText?: string;
    ctaHref?: string;
    badgeText?: string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: false,
    countdownSeconds: 5,
    title: 'Your Ad Goes Here — Interstitial Takeover',
    subtitle:
        '100% attention placement. Promote major courses, book launches, or educational software before transitions.',
    ctaText: 'Visit Sponsor',
    ctaHref: 'https://facebook.com/hscstackbd',
    badgeText: 'Sponsored Interstitial',
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
    (e: 'close'): void;
}>();

const remainingSeconds = ref(props.countdownSeconds);
let timer: ReturnType<typeof setInterval> | null = null;

const startTimer = () => {
    remainingSeconds.value = props.countdownSeconds;

    if (timer) {
        clearInterval(timer);
    }

    timer = setInterval(() => {
        if (remainingSeconds.value > 0) {
            remainingSeconds.value -= 1;
        } else {
            if (timer) {
                clearInterval(timer);
            }
        }
    }, 1000);
};

watch(
    () => props.modelValue,
    (isOpen) => {
        if (isOpen) {
            startTimer();
        } else if (timer) {
            clearInterval(timer);
        }
    },
    { immediate: true },
);

onUnmounted(() => {
    if (timer) {
        clearInterval(timer);
    }
});

const handleClose = () => {
    emit('update:modelValue', false);
    emit('close');
};
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
                v-if="modelValue"
                class="fixed inset-0 z-[200] flex items-center justify-center bg-slate-950/85 p-4 backdrop-blur-md"
            >
                <div
                    class="relative w-full max-w-lg overflow-hidden rounded-3xl border border-amber-400/50 bg-gradient-to-b from-slate-900 via-slate-900 to-slate-950 p-6 text-white shadow-2xl sm:p-8"
                >
                    <!-- Header with Badge and Timer / Skip Button -->
                    <div class="mb-5 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span
                                class="inline-flex items-center gap-1 rounded-md bg-amber-500/20 px-2 py-0.5 text-[10px] font-black tracking-widest text-amber-300 uppercase"
                            >
                                <Sparkles class="h-3 w-3" />
                                {{ badgeText }}
                            </span>
                        </div>

                        <!-- Countdown / Skip Button -->
                        <div>
                            <button
                                v-if="remainingSeconds === 0"
                                type="button"
                                @click="handleClose"
                                class="inline-flex cursor-pointer items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-bold text-white transition hover:bg-white/25 active:scale-95"
                            >
                                <span>Skip Ad</span>
                                <X class="h-3.5 w-3.5" />
                            </button>
                            <div
                                v-else
                                class="inline-flex items-center gap-1.5 rounded-full bg-slate-800/80 px-3 py-1 text-xs font-semibold text-slate-300"
                            >
                                <Clock
                                    class="h-3.5 w-3.5 animate-spin text-amber-400"
                                />
                                <span>Skip in {{ remainingSeconds }}s</span>
                            </div>
                        </div>
                    </div>

                    <!-- Visual Ad Canvas / Graphic Box -->
                    <div
                        class="relative flex aspect-[16/9] w-full flex-col items-center justify-center overflow-hidden rounded-2xl border border-dashed border-amber-400/40 bg-gradient-to-tr from-amber-600/30 via-orange-600/20 to-purple-600/20 p-6 text-center"
                    >
                        <div
                            class="mb-3 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-tr from-amber-500 to-orange-500 text-white shadow-lg shadow-amber-500/30"
                        >
                            <Megaphone class="h-8 w-8" />
                        </div>
                        <h3
                            class="text-xl font-black tracking-tight text-white sm:text-2xl"
                        >
                            YOUR AD GOES HERE
                        </h3>
                        <p class="mt-1 text-xs text-amber-200">
                            High-Conversion Full-Screen Interstitial Showcase
                        </p>
                    </div>

                    <!-- Title & Copy -->
                    <div class="mt-5 text-center">
                        <h4 class="text-base font-bold text-white sm:text-lg">
                            {{ title }}
                        </h4>
                        <p class="mt-1.5 text-xs text-slate-300 sm:text-sm">
                            {{ subtitle }}
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="mt-6 flex flex-col gap-2.5 sm:flex-row">
                        <a
                            :href="ctaHref"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 px-5 py-3 text-xs font-bold text-slate-950 shadow-md transition hover:from-amber-400 hover:to-orange-400 active:scale-95"
                        >
                            <span>{{ ctaText }}</span>
                            <ExternalLink class="h-4 w-4" />
                        </a>

                        <button
                            type="button"
                            @click="handleClose"
                            :disabled="remainingSeconds > 0"
                            class="inline-flex items-center justify-center rounded-xl border px-4 py-3 text-xs font-bold transition"
                            :class="[
                                remainingSeconds > 0
                                    ? 'cursor-not-allowed border-slate-800 text-slate-600'
                                    : 'cursor-pointer border-slate-700 bg-slate-800 text-slate-200 hover:bg-slate-700',
                            ]"
                        >
                            {{
                                remainingSeconds > 0
                                    ? `Wait ${remainingSeconds}s`
                                    : 'Close Ad'
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
