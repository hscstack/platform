<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { ArrowRight, GraduationCap, X } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const page = usePage();

const DISMISS_KEY = 'guest_login_prompt_dismissed';
const PAGE_COUNT_KEY = 'guest_browsing_page_count';

const isVisible = ref(false);
let timeTimer: ReturnType<typeof setTimeout> | null = null;
let transitionTimer: ReturnType<typeof setTimeout> | null = null;
let unregisterNavigate: (() => void) | null = null;

const isAuthenticated = computed(() => Boolean(page.props.auth?.user));

const isAuthRoute = computed(() => {
    const path = page.url.split('?')[0];

    return [
        '/login',
        '/register',
        '/forgot-password',
        '/reset-password',
        '/verify-email',
        '/confirm-password',
        '/onboarding',
    ].some((route) => path === route || path.startsWith(route + '/'));
});

const isDismissedInSession = (): boolean => {
    if (typeof window === 'undefined') {
        return true;
    }

    try {
        return sessionStorage.getItem(DISMISS_KEY) === 'true';
    } catch {
        return false;
    }
};

const dismiss = () => {
    isVisible.value = false;

    if (typeof window !== 'undefined') {
        try {
            sessionStorage.setItem(DISMISS_KEY, 'true');
        } catch {}
    }
};

const clearTimers = () => {
    if (timeTimer) {
        clearTimeout(timeTimer);
        timeTimer = null;
    }

    if (transitionTimer) {
        clearTimeout(transitionTimer);
        transitionTimer = null;
    }
};

const evaluatePrompt = (isNewNavigation = false) => {
    clearTimers();

    if (isAuthenticated.value || isAuthRoute.value || isDismissedInSession()) {
        isVisible.value = false;

        return;
    }

    if (typeof window === 'undefined') {
        return;
    }

    let count = 1;

    try {
        const stored = sessionStorage.getItem(PAGE_COUNT_KEY);

        if (stored) {
            count = parseInt(stored, 10);

            if (isNewNavigation) {
                count += 1;
                sessionStorage.setItem(PAGE_COUNT_KEY, count.toString());
            }
        } else {
            count = 1;
            sessionStorage.setItem(PAGE_COUNT_KEY, '1');
        }
    } catch {}

    // Condition 1: 2nd or later page navigation in this session
    if (count >= 2) {
        transitionTimer = setTimeout(() => {
            if (
                !isAuthenticated.value &&
                !isAuthRoute.value &&
                !isDismissedInSession()
            ) {
                isVisible.value = true;
            }
        }, 1500);
    } else {
        // Condition 2: Continuous browsing on initial page (25 seconds)
        timeTimer = setTimeout(() => {
            if (
                !isAuthenticated.value &&
                !isAuthRoute.value &&
                !isDismissedInSession()
            ) {
                isVisible.value = true;
            }
        }, 25000);
    }
};

let lastPath = '';

onMounted(() => {
    lastPath = page.url.split('?')[0];
    evaluatePrompt(false);

    // Reliably catch all client-side Inertia page navigations
    unregisterNavigate = router.on('navigate', (event) => {
        const nextUrl = event.detail?.page?.url || page.url;
        const nextPath = nextUrl.split('?')[0];

        if (nextPath !== lastPath) {
            lastPath = nextPath;
            evaluatePrompt(true);
        }
    });
});

onBeforeUnmount(() => {
    clearTimers();

    if (unregisterNavigate) {
        unregisterNavigate();
        unregisterNavigate = null;
    }
});

watch(isAuthenticated, (authed) => {
    if (authed) {
        clearTimers();
        isVisible.value = false;
    }
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-6 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-6 scale-95"
        >
            <div
                v-if="isVisible"
                class="fixed right-4 bottom-[calc(4.5rem+env(safe-area-inset-bottom)+0.75rem)] left-4 z-40 sm:right-6 sm:bottom-6 sm:left-auto sm:max-w-md"
                role="region"
                aria-label="Login Prompt"
            >
                <div
                    class="relative overflow-hidden rounded-2xl border border-slate-200/90 bg-white/95 p-4.5 shadow-xl shadow-slate-900/10 backdrop-blur-xl sm:p-5 dark:border-gray-800 dark:bg-gray-900/95 dark:shadow-2xl dark:shadow-black/70"
                >
                    <!-- Ambient Glow -->
                    <div
                        class="pointer-events-none absolute -top-12 -right-12 h-28 w-28 rounded-full bg-indigo-500/15 blur-2xl dark:bg-indigo-500/20"
                    />

                    <!-- Close Button -->
                    <button
                        type="button"
                        @click="dismiss"
                        class="absolute top-3.5 right-3.5 flex h-7 w-7 cursor-pointer items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                        title="Close"
                        aria-label="Dismiss prompt"
                    >
                        <X class="h-4 w-4" />
                    </button>

                    <!-- Header Content -->
                    <div class="flex items-start gap-3 pr-6">
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                        >
                            <GraduationCap class="h-4.5 w-4.5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4
                                class="text-sm font-bold text-slate-900 dark:text-gray-100"
                            >
                                পড়ার অগ্রগতি সেভ রাখতে চান?
                            </h4>
                            <p
                                class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-gray-400"
                            >
                                ফ্রি অ্যাকাউন্ট খুলে সিলেবাস ট্র্যাক করুন,
                                ফোরামে আলোচনা করুন ও স্টাডি রিসোর্স অ্যাক্সেস
                                করুন।
                            </p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-4 flex items-center justify-end gap-2.5">
                        <button
                            type="button"
                            @click="dismiss"
                            class="cursor-pointer rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            পরে করব
                        </button>

                        <Link
                            :href="`/login?redirect=${encodeURIComponent(page.url)}`"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-indigo-700 active:scale-95 dark:bg-indigo-600 dark:hover:bg-indigo-500"
                        >
                            <span>লগইন বা সাইন আপ</span>
                            <ArrowRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
