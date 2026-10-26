<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { BellRing, X, Check, Loader2 } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import BaseModal from '@/components/BaseModal.vue';
import {
    initWebPush,
    isPushLoading,
    isPushSubscribed,
    isPushSupported,
    subscribeToPush,
} from '@/lib/useWebPush';

const DISMISS_KEY = 'webpush_prompt_dismissed_until';
const THREE_DAYS_MS = 3 * 24 * 60 * 60 * 1000;

const page = usePage();
const isAuthenticated = computed(() => Boolean((page.props.auth as any)?.user));

const isOpen = ref(false);

const checkShouldShow = async () => {
    if (!isAuthenticated.value) {
        isOpen.value = false;

        return;
    }

    // Do not show on auth or onboarding screens
    const path = window.location.pathname;

    if (
        ['/login', '/register', '/onboarding'].some((r) => path.startsWith(r))
    ) {
        return;
    }

    // Do not conflict if another modal is currently open in the DOM
    if (
        document.querySelector('[role="dialog"]') ||
        document.body.classList.contains('overflow-hidden')
    ) {
        return;
    }

    await initWebPush();

    // Only prompt for supported Chromium browsers that aren't already subscribed or blocked
    if (!isPushSupported.value || isPushSubscribed.value) {
        isOpen.value = false;

        return;
    }

    if (typeof window !== 'undefined' && 'Notification' in window) {
        if (Notification.permission === 'denied') {
            return;
        }
    }

    // Check if dismissed within the last 2 days
    const dismissedUntil = localStorage.getItem(DISMISS_KEY);

    if (dismissedUntil && Date.now() < Number(dismissedUntil)) {
        return;
    }

    // Small delay so it feels natural after page load and other alerts settle
    setTimeout(() => {
        // Re-check before popping up: no active modal and user still not subscribed
        const hasOpenModal = Boolean(document.querySelector('[role="dialog"]'));

        if (!isPushSubscribed.value && !hasOpenModal) {
            isOpen.value = true;
        }
    }, 3500);
};

const handleDismiss = () => {
    isOpen.value = false;
    // Set 3 days expiration in localStorage
    localStorage.setItem(DISMISS_KEY, String(Date.now() + THREE_DAYS_MS));
};

const handleEnable = async () => {
    const success = await subscribeToPush();

    if (success) {
        isOpen.value = false;
        localStorage.removeItem(DISMISS_KEY);
    }
};

onMounted(() => {
    checkShouldShow();
});
</script>

<template>
    <BaseModal :is-open="isOpen" max-width="sm" @close="handleDismiss">
        <div class="relative overflow-hidden p-6 text-center sm:p-7">
            <!-- Close Button -->
            <button
                type="button"
                @click="handleDismiss"
                class="absolute top-4 right-4 rounded-xl p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                aria-label="Close"
            >
                <X class="h-4 w-4" />
            </button>

            <!-- Bell Icon Header -->
            <div
                class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 shadow-inner dark:bg-indigo-950/50 dark:text-indigo-400"
            >
                <BellRing class="h-7 w-7 animate-bounce" />
            </div>

            <!-- Content -->
            <h3 class="text-base font-bold text-slate-900 dark:text-gray-100">
                নোটিফিকেশন চালু করুন
            </h3>
            <p
                class="mt-2 text-xs leading-relaxed text-slate-500 dark:text-gray-400"
            >
                নতুন কমেন্ট, রিপ্লাই ও প্রয়োজনীয় আপডেট পান তাৎক্ষণিকভাবে — এমনকি
                ব্রাউজার বন্ধ থাকলেও!
            </p>

            <!-- Actions -->
            <div class="mt-6 flex flex-col gap-2">
                <button
                    type="button"
                    @click="handleEnable"
                    :disabled="isPushLoading"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-700 active:scale-95 disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                >
                    <Loader2
                        v-if="isPushLoading"
                        class="h-4 w-4 animate-spin"
                    />
                    <template v-else>
                        <Check class="h-4 w-4" />
                        <span>চালু করুন</span>
                    </template>
                </button>

                <button
                    type="button"
                    @click="handleDismiss"
                    class="inline-flex w-full items-center justify-center rounded-xl py-2 text-xs font-medium text-slate-400 transition-colors hover:text-slate-600 dark:text-gray-500 dark:hover:text-gray-300"
                >
                    ৩ দিন পরে মনে করিয়ে দিন
                </button>
            </div>
        </div>
    </BaseModal>
</template>
