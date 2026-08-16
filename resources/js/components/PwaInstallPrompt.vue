<script setup lang="ts">
import { kDialog, kButton } from 'konsta/vue';
import { ref, shallowRef, onMounted, onUnmounted } from 'vue';

defineOptions({
    inheritAttrs: false,
});

const props = defineProps({
    variant: {
        type: String,
        default: 'modal',
    },
});

const isVisible = ref(false);
const deferredPrompt = shallowRef(null);

const handleBeforeInstall = (e: Event) => {
    e.preventDefault();
    deferredPrompt.value = e;

    if (props.variant === 'modal') {
        if (!sessionStorage.getItem('pwa_prompt_dismissed')) {
            isVisible.value = true;
        }
    } else {
        isVisible.value = true;
    }
};

const handleAppInstalled = () => {
    isVisible.value = false;
    deferredPrompt.value = null;
};

onMounted(() => {
    const isStandalone =
        window.matchMedia('(display-mode: standalone)').matches ||
        (window.navigator as Record<string, unknown>).standalone === true;

    if (isStandalone) {
        return;
    }

    window.addEventListener('beforeinstallprompt', handleBeforeInstall);
    window.addEventListener('appinstalled', handleAppInstalled);
});

onUnmounted(() => {
    window.removeEventListener('beforeinstallprompt', handleBeforeInstall);
    window.removeEventListener('appinstalled', handleAppInstalled);
});

const handleInstall = async () => {
    if (!deferredPrompt.value) {
        return;
    }

    try {
        await deferredPrompt.value.prompt();
        const { outcome } = await deferredPrompt.value.userChoice;

        if (outcome === 'accepted') {
            isVisible.value = false;
            deferredPrompt.value = null;
        }
    } catch (error) {
        console.error('PWA install error:', error);
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
    <template v-if="isVisible && deferredPrompt">
        <Teleport v-if="variant === 'modal'" to="body">
            <kDialog :opened="true">
                <div
                    class="w-full max-w-sm rounded-2xl border border-slate-100 bg-white p-6 shadow-xl dark:border-gray-700 dark:bg-gray-900"
                >
                    <div class="mb-4 flex items-center gap-3">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-xl text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                        >
                            📱
                        </div>
                        <div>
                            <h3
                                class="text-base font-bold text-slate-900 dark:text-gray-100"
                            >
                                Install Our App
                            </h3>
                            <p
                                class="text-xs text-slate-500 dark:text-gray-400"
                            >
                                Get fast, offline access right from your home
                                screen.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            @click="handleInstall"
                            class="flex-1 rounded-lg bg-indigo-600 py-2 text-xs font-semibold text-white shadow transition hover:bg-indigo-700"
                        >
                            Install App
                        </button>
                        <button
                            @click="handleDismiss"
                            class="rounded-lg border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-800"
                        >
                            Maybe Later
                        </button>
                    </div>
                </div>
            </kDialog>
        </Teleport>

        <!-- Compact Inline Banner (Mobile) / Scaled Up (PC) -->
        <div
            v-else
            v-bind="$attrs"
            class="flex items-center justify-between gap-2.5 rounded-xl border border-indigo-100 bg-gradient-to-r from-indigo-50/80 to-slate-50/80 px-3 py-2 shadow-sm backdrop-blur-sm sm:gap-4 sm:px-5 sm:py-3.5 dark:border-indigo-500/20 dark:from-indigo-500/5 dark:to-gray-900/80"
        >
            <div class="flex min-w-0 items-center gap-2.5 sm:gap-3.5">
                <div
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-600/10 text-base sm:h-10 sm:w-10 sm:rounded-xl sm:text-xl dark:bg-indigo-500/20"
                >
                    📱
                </div>
                <div class="min-w-0">
                    <h3
                        class="truncate text-xs font-bold text-slate-900 sm:text-sm dark:text-gray-100"
                    >
                        Install Our App
                    </h3>
                    <p
                        class="truncate text-[11px] text-slate-500 sm:text-xs dark:text-gray-400"
                    >
                        Fast, offline home screen access.
                    </p>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                <button
                    @click="handleInstall"
                    class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs transition hover:bg-indigo-700 active:scale-95 sm:rounded-lg sm:px-4 sm:py-2 sm:text-xs"
                >
                    Install
                </button>
                <button
                    @click="handleDismiss"
                    class="flex h-7 w-7 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-200/50 hover:text-slate-600 sm:h-8 sm:w-8 dark:text-gray-500 dark:hover:bg-gray-700 dark:hover:text-gray-300"
                    title="Dismiss"
                >
                    ✕
                </button>
            </div>
        </div>
    </template>
</template>
