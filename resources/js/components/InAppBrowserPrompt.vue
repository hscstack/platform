<script setup lang="ts">
import {
    Check,
    Compass,
    Copy,
    ExternalLink,
    MoreHorizontal,
    X,
} from 'lucide-vue-next';
import { useInAppBrowser } from '@/lib/useInAppBrowser';

const {
    isVisible,
    isAndroid,
    isIOS,
    copied,
    openInExternalBrowser,
    copyUrl,
    dismiss,
} = useInAppBrowser();
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-300 ease-out transform"
            enter-from-class="-translate-y-full opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in transform"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="-translate-y-full opacity-0"
        >
            <div
                v-if="isVisible"
                class="fixed inset-x-0 top-0 z-[100] px-3 pt-3 pb-2 sm:px-4 sm:pt-4"
                role="alert"
                aria-live="polite"
            >
                <div
                    class="relative mx-auto max-w-xl overflow-hidden rounded-2xl border border-indigo-200/80 bg-white/95 p-4 shadow-xl backdrop-blur-xl sm:p-5 dark:border-indigo-900/50 dark:bg-gray-900/95"
                >
                    <!-- Background ambient glow -->
                    <div
                        class="pointer-events-none absolute -top-10 -right-10 h-28 w-28 rounded-full bg-indigo-500/10 blur-xl dark:bg-indigo-500/20"
                    />

                    <!-- Close button -->
                    <button
                        type="button"
                        @click="dismiss"
                        class="absolute top-3 right-3 flex h-7 w-7 cursor-pointer items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                        title="Dismiss"
                        aria-label="Dismiss banner"
                    >
                        <X class="h-4 w-4" />
                    </button>

                    <div class="flex items-start gap-3.5 pr-6">
                        <!-- Icon Badge -->
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-md shadow-indigo-500/25 dark:bg-indigo-500"
                        >
                            <Compass class="h-5 w-5" />
                        </div>

                        <!-- Content -->
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <h3
                                    class="text-sm font-bold text-slate-900 sm:text-base dark:text-gray-100"
                                >
                                    {{
                                        isIOS
                                            ? 'Safari বা মূল ব্রাউজারে খুলুন'
                                            : 'মূল ব্রাউজারে সাইটটি খুলুন'
                                    }}
                                </h3>
                                <span
                                    class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-semibold text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300"
                                >
                                    Facebook Browser
                                </span>
                            </div>

                            <p
                                class="mt-1 text-xs text-slate-600 sm:text-sm dark:text-gray-300"
                            >
                                <template v-if="isIOS">
                                    সেরা অভিজ্ঞতা ও নিরবচ্ছিন্ন স্টাডির জন্য
                                    স্ক্রিনের কোণায়
                                    <span
                                        class="inline-flex items-center gap-0.5 font-semibold text-slate-900 dark:text-gray-100"
                                    >
                                        <MoreHorizontal
                                            class="inline h-3.5 w-3.5"
                                        />
                                        (••• মেনু)
                                    </span>
                                    থেকে
                                    <span
                                        class="font-semibold text-indigo-600 dark:text-indigo-400"
                                    >
                                        "Open in Safari"
                                    </span>
                                    নির্বাচন করুন।
                                </template>
                                <template v-else>
                                    ডাউনলোড, ভিডিও লেকচার ও লগইন সেশন চালু রাখতে
                                    আপনার ফোনের ডিফল্ট ব্রাউজারে খুলুন।
                                </template>
                            </p>

                            <!-- Actions -->
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <!-- Android Primary: Open in Browser -->
                                <button
                                    v-if="isAndroid"
                                    type="button"
                                    @click="openInExternalBrowser"
                                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-700 active:scale-95 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                                >
                                    <ExternalLink class="h-3.5 w-3.5" />
                                    <span>ব্রাউজারে খুলুন</span>
                                </button>

                                <!-- Copy Link Button -->
                                <button
                                    type="button"
                                    @click="copyUrl"
                                    class="dark:hover:bg-gray-750 inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 active:scale-95 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                                >
                                    <Check
                                        v-if="copied"
                                        class="h-3.5 w-3.5 text-emerald-500"
                                    />
                                    <Copy v-else class="h-3.5 w-3.5" />
                                    <span>{{
                                        copied ? 'লিংক কপি হয়েছে!' : 'লিংক কপি'
                                    }}</span>
                                </button>

                                <!-- Dismiss Button -->
                                <button
                                    type="button"
                                    @click="dismiss"
                                    class="inline-flex cursor-pointer items-center rounded-xl px-2.5 py-2 text-xs font-medium text-slate-500 transition hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200"
                                >
                                    এখানেই থাকুন
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
