<script setup lang="ts">
import { Check, Copy, ExternalLink, X } from 'lucide-vue-next';
import { useInAppBrowser } from '@/lib/useInAppBrowser';

const isDev = import.meta.env.DEV;

const {
    isVisible,
    isAndroid,
    isIOS,
    copied,
    openInExternalBrowser,
    copyUrl,
    dismiss,
} = useInAppBrowser();

const setMode = (mode: 'android' | 'ios') => {
    isAndroid.value = mode === 'android';
    isIOS.value = mode === 'ios';
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-250 ease-out"
            enter-from-class="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
            leave-to-class="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
        >
            <div
                v-if="isVisible"
                class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="iab-dialog-title"
            >
                <!-- Backdrop -->
                <div
                    @click="dismiss"
                    class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity dark:bg-black/75"
                />

                <!-- Bottom Sheet / Modal Box -->
                <div
                    class="relative w-full max-w-md overflow-hidden rounded-t-3xl border border-slate-200/80 bg-white/95 p-6 shadow-2xl backdrop-blur-xl sm:rounded-3xl sm:p-7 dark:border-gray-800 dark:bg-gray-900/95"
                >
                    <!-- Ambient Glow -->
                    <div
                        class="pointer-events-none absolute -top-20 -right-20 h-44 w-44 rounded-full bg-indigo-500/10 blur-2xl dark:bg-indigo-500/20"
                    />

                    <!-- Close Button -->
                    <button
                        type="button"
                        @click="dismiss"
                        class="absolute top-4 right-4 flex h-8 w-8 cursor-pointer items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                        title="Close"
                        aria-label="Close dialog"
                    >
                        <X class="h-4 w-4" />
                    </button>

                    <!-- Preview Mode Switcher (for local development only) -->
                    <div
                        v-if="isDev"
                        class="mb-4 flex items-center justify-end pr-8"
                    >
                        <div
                            class="inline-flex rounded-xl border border-slate-200 bg-slate-100/80 p-0.5 text-xs font-semibold dark:border-gray-700 dark:bg-gray-800"
                        >
                            <button
                                type="button"
                                @click="setMode('android')"
                                :class="[
                                    isAndroid
                                        ? 'bg-white text-indigo-600 shadow-xs dark:bg-gray-700 dark:text-white'
                                        : 'text-slate-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200',
                                ]"
                                class="cursor-pointer rounded-lg px-2.5 py-1 transition"
                            >
                                Android
                            </button>
                            <button
                                type="button"
                                @click="setMode('ios')"
                                :class="[
                                    isIOS
                                        ? 'bg-white text-indigo-600 shadow-xs dark:bg-gray-700 dark:text-white'
                                        : 'text-slate-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200',
                                ]"
                                class="cursor-pointer rounded-lg px-2.5 py-1 transition"
                            >
                                iOS
                            </button>
                        </div>
                    </div>

                    <!-- Header -->
                    <div class="flex items-start gap-3.5">
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-slate-200/80 bg-slate-900 shadow-md shadow-indigo-500/10 dark:border-gray-700 dark:bg-gray-100"
                        >
                            <img
                                src="/favicon.svg"
                                alt="HSCStack"
                                class="h-8 w-8 scale-110 object-contain"
                            />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3
                                id="iab-dialog-title"
                                class="text-base font-bold text-slate-900 sm:text-lg dark:text-gray-100"
                            >
                                {{
                                    isIOS
                                        ? 'Safari বা মূল ব্রাউজারে খুলুন'
                                        : 'মূল ব্রাউজারে সাইটটি খুলুন'
                                }}
                            </h3>
                            <p
                                class="mt-1 text-xs leading-relaxed text-slate-600 sm:text-sm dark:text-gray-300"
                            >
                                আপনি বর্তমানে Facebook অ্যাপের ভেতরে রয়েছেন।
                                সেরা অভিজ্ঞতা ও সব ফিচারের জন্য আপনার ফোনের আসল
                                ব্রাউজার ব্যবহার করুন।
                            </p>
                        </div>
                    </div>

                    <!-- iOS Step-by-Step Guidance Box -->
                    <div
                        v-if="isIOS"
                        class="mt-4 space-y-2.5 rounded-2xl border border-indigo-100 bg-indigo-50/60 p-4 text-xs sm:text-sm dark:border-indigo-900/40 dark:bg-indigo-950/30"
                    >
                        <div
                            class="flex items-start gap-2.5 text-slate-700 dark:text-gray-300"
                        >
                            <span
                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-[11px] font-bold text-white dark:bg-indigo-500"
                            >
                                ১
                            </span>
                            <span class="pt-0.5">
                                স্ক্রিনের কোণায় থাকা
                                <strong
                                    class="font-semibold text-slate-900 dark:text-white"
                                    >three dot মেনু</strong
                                >
                                চাপুন।
                            </span>
                        </div>
                        <div
                            class="flex items-start gap-2.5 text-slate-700 dark:text-gray-300"
                        >
                            <span
                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-[11px] font-bold text-white dark:bg-indigo-500"
                            >
                                ২
                            </span>
                            <span class="pt-0.5">
                                মেনু থেকে
                                <strong
                                    class="font-semibold text-indigo-600 dark:text-indigo-400"
                                    >Open in Safari</strong
                                >
                                বা
                                <strong
                                    class="font-semibold text-slate-900 dark:text-white"
                                    >Open in Browser</strong
                                >
                                বেছে নিন।
                            </span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-5 space-y-2.5">
                        <!-- Android: Open in Browser Button -->
                        <button
                            v-if="isAndroid"
                            type="button"
                            @click="openInExternalBrowser"
                            class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-3 text-xs font-bold text-white shadow-md shadow-indigo-600/20 transition-all hover:bg-indigo-700 active:scale-[0.99] sm:text-sm dark:bg-indigo-500 dark:hover:bg-indigo-600"
                        >
                            <ExternalLink class="h-4 w-4" />
                            <span>ডিফল্ট ব্রাউজারে খুলুন</span>
                        </button>

                        <!-- Copy Link Button -->
                        <button
                            type="button"
                            @click="copyUrl"
                            :class="[
                                isIOS
                                    ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600'
                                    : 'dark:hover:bg-gray-750 border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200',
                            ]"
                            class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold transition-all active:scale-[0.99] sm:text-sm"
                        >
                            <Check
                                v-if="copied"
                                class="h-4 w-4 text-emerald-500"
                            />
                            <Copy v-else class="h-4 w-4" />
                            <span>{{
                                copied ? 'লিংক কপি হয়েছে!' : 'লিংক কপি করে নিন'
                            }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
