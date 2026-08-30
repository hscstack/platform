<script setup lang="ts">
import { Download, X, CheckCircle2, Loader2 } from 'lucide-vue-next';
import { ref, onMounted, watch } from 'vue';
import { usePwa } from '@/lib/usePwa';

defineOptions({
    inheritAttrs: false,
});

const props = defineProps({
    variant: {
        type: String,
        default: 'modal',
    },
    title: {
        type: String,
        default: 'HSCStack অ্যাপ ইনস্টল করুন',
    },
    description: {
        type: String,
        default: 'দ্রুতগতির ব্রাউজিং ও নিরবচ্ছিন্ন স্টাডি এক্সপেরিয়েন্স',
    },
});

const { deferredPrompt, isInstalled, promptInstall } = usePwa();
const isVisible = ref(false);
const isInstalling = ref(false);

const updateVisibility = () => {
    if (isInstalled.value || !deferredPrompt.value) {
        isVisible.value = false;

        return;
    }

    if (props.variant === 'modal') {
        if (!sessionStorage.getItem('pwa_prompt_dismissed')) {
            isVisible.value = true;
        }
    } else {
        isVisible.value = true;
    }
};

onMounted(() => {
    updateVisibility();
});

watch([deferredPrompt, isInstalled], () => {
    updateVisibility();
});

const handleInstall = async () => {
    if (!deferredPrompt.value) {
        return;
    }

    isInstalling.value = true;

    try {
        const success = await promptInstall();

        if (success) {
            isVisible.value = false;
        }
    } finally {
        isInstalling.value = false;
    }
};

const handleDismiss = () => {
    isVisible.value = false;

    if (props.variant === 'modal') {
        sessionStorage.setItem('pwa_prompt_dismissed', 'true');
    }
};
</script>

<template>
    <Teleport v-if="variant === 'modal'" to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="isVisible && deferredPrompt"
                class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center sm:p-6"
                role="dialog"
                aria-modal="true"
            >
                <!-- Backdrop -->
                <div
                    @click="handleDismiss"
                    class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity dark:bg-black/75"
                ></div>

                <!-- Modal Box -->
                <div
                    class="relative w-full max-w-sm overflow-hidden rounded-3xl border border-slate-200/80 bg-white/95 p-6 shadow-2xl backdrop-blur-xl transition-all sm:p-7 dark:border-gray-800 dark:bg-gray-900/95"
                >
                    <!-- Ambient Blur Blob -->
                    <div
                        class="pointer-events-none absolute -top-20 -right-20 h-40 w-40 rounded-full bg-indigo-500/10 blur-2xl dark:bg-indigo-500/20"
                    ></div>

                    <!-- Close Button -->
                    <button
                        @click="handleDismiss"
                        class="absolute top-4 right-4 flex h-7 w-7 cursor-pointer items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                        title="Close"
                    >
                        <X class="h-4 w-4" />
                    </button>

                    <!-- Header -->
                    <div class="flex items-center gap-3.5">
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-slate-200/80 bg-slate-900 shadow-md shadow-indigo-500/10 dark:border-gray-700 dark:bg-gray-100"
                        >
                            <img
                                src="/favicon.svg"
                                alt="HSCStack App"
                                class="h-8 w-8 scale-110 object-contain"
                            />
                        </div>
                        <div class="min-w-0 flex-1 pr-3">
                            <h3
                                class="text-base font-bold text-slate-900 sm:text-lg dark:text-gray-100"
                            >
                                {{ title }}
                            </h3>
                            <p
                                class="text-xs text-slate-500 dark:text-gray-400"
                            >
                                {{ description }}
                            </p>
                        </div>
                    </div>

                    <!-- Highlights / Feature description in Bengali -->
                    <div
                        class="mt-4 space-y-2.5 rounded-2xl border border-slate-100 bg-slate-50/80 p-3.5 text-xs dark:border-gray-800 dark:bg-gray-800/40"
                    >
                        <div
                            class="flex items-start gap-2.5 text-slate-700 dark:text-gray-300"
                        >
                            <CheckCircle2
                                class="mt-0.5 h-3.5 w-3.5 shrink-0 text-emerald-500"
                            />
                            <div>
                                <strong
                                    class="font-semibold text-slate-900 dark:text-gray-100"
                                    >দ্রুতগতির লাইভ চ্যাট ও আপডেট:</strong
                                >
                                <p
                                    class="mt-0.5 text-[11px] text-slate-500 dark:text-gray-400"
                                >
                                    ব্রাউজার ব্যাকগ্রাউন্ড ল্যাগ ছাড়াই
                                    ইনস্ট্যান্ট মেসেজিং ও লেকচার নোটস।
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex items-start gap-2.5 text-slate-700 dark:text-gray-300"
                        >
                            <CheckCircle2
                                class="mt-0.5 h-3.5 w-3.5 shrink-0 text-indigo-500"
                            />
                            <div>
                                <strong
                                    class="font-semibold text-slate-900 dark:text-gray-100"
                                    >ফুলস্ক্রিন অ্যাপ মোড:</strong
                                >
                                <p
                                    class="mt-0.5 text-[11px] text-slate-500 dark:text-gray-400"
                                >
                                    অ্যাড্রেস বার বা অপ্রয়োজনীয় ট্যাবের
                                    ঝামেলাহীন পরিচ্ছন্ন ইন্টারফেস।
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex items-start gap-2.5 text-slate-700 dark:text-gray-300"
                        >
                            <CheckCircle2
                                class="mt-0.5 h-3.5 w-3.5 shrink-0 text-violet-500"
                            />
                            <div>
                                <strong
                                    class="font-semibold text-slate-900 dark:text-gray-100"
                                    >কম ডাটা খরচ ও ১-ক্লিক ওপেন:</strong
                                >
                                <p
                                    class="mt-0.5 text-[11px] text-slate-500 dark:text-gray-400"
                                >
                                    হোমস্ক্রিন বা অ্যাপ ড্রয়ার থেকে সরাসরি ওপেন
                                    ও অপ্টিমাইজড স্পিড।
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <div class="mt-5">
                        <button
                            @click="handleInstall"
                            :disabled="isInstalling"
                            class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-3 text-xs font-bold text-white shadow-md shadow-indigo-600/20 transition-all hover:bg-indigo-700 active:scale-[0.99] disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                        >
                            <Loader2
                                v-if="isInstalling"
                                class="h-4 w-4 animate-spin"
                            />
                            <Download v-else class="h-4 w-4" />
                            <span>{{
                                isInstalling
                                    ? 'ইনস্টল হচ্ছে...'
                                    : 'অ্যাপ ইনস্টল করুন'
                            }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>

    <!-- Compact Inline Banner -->
    <div
        v-else-if="isVisible && deferredPrompt"
        v-bind="$attrs"
        class="flex items-center justify-between gap-2.5 rounded-2xl border border-indigo-100 bg-gradient-to-r from-indigo-50/80 to-slate-50/80 px-3.5 py-2.5 shadow-sm backdrop-blur-sm sm:gap-4 sm:px-5 sm:py-3.5 dark:border-indigo-500/20 dark:from-indigo-500/5 dark:to-gray-900/80"
    >
        <div class="flex min-w-0 items-center gap-2.5 sm:gap-3.5">
            <div
                class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-200/80 bg-slate-900 shadow-xs sm:h-10 sm:w-10 dark:border-gray-700 dark:bg-gray-100"
            >
                <img
                    src="/favicon.svg"
                    alt="HSCStack App"
                    class="h-6 w-6 scale-110 object-contain sm:h-7 sm:w-7"
                />
            </div>
            <div class="min-w-0">
                <h3
                    class="truncate text-xs font-bold text-slate-900 sm:text-sm dark:text-gray-100"
                >
                    {{ title }}
                </h3>
                <p
                    class="truncate text-[11px] text-slate-500 sm:text-xs dark:text-gray-400"
                >
                    {{ description }}
                </p>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
            <button
                @click="handleInstall"
                class="flex cursor-pointer items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-xs transition hover:bg-indigo-700 active:scale-95 sm:px-4 sm:py-2 dark:bg-indigo-500 dark:hover:bg-indigo-600"
            >
                <Download class="h-3.5 w-3.5" />
                <span>ইনস্টল করুন</span>
            </button>
            <button
                @click="handleDismiss"
                class="flex h-7 w-7 cursor-pointer items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-200/50 hover:text-slate-600 sm:h-8 sm:w-8 dark:text-gray-500 dark:hover:bg-gray-700 dark:hover:text-gray-300"
                title="Dismiss"
            >
                ✕
            </button>
        </div>
    </div>
</template>
