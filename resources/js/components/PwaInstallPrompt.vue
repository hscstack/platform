<script setup lang="ts">
import { kDialog, kButton } from 'konsta/vue';
import { ref, shallowRef, onMounted, onUnmounted } from 'vue';

const isVisible = ref(false);
const deferredPrompt = shallowRef(null);

const handleBeforeInstall = (e: Event) => {
    e.preventDefault();
    deferredPrompt.value = e;

    if (!sessionStorage.getItem('pwa_prompt_dismissed')) {
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
    sessionStorage.setItem('pwa_prompt_dismissed', 'true');
};
</script>

<template>
    <Teleport v-if="isVisible && deferredPrompt" to="body">
        <kDialog :opened="true" @opened:change="handleDismiss">
            <div class="flex flex-col items-center px-4 py-6 text-center">
                <div
                    class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-50 text-3xl dark:bg-indigo-500/10"
                >
                    📱
                </div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                    Install HSCStack
                </h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Add to your home screen for fast, offline access.
                </p>
                <div class="mt-6 flex w-full gap-3">
                    <k-button
                        large
                        rounded
                        class="flex-1"
                        @click="handleInstall"
                    >
                        Install
                    </k-button>
                    <k-button
                        large
                        rounded
                        clear
                        class="flex-1"
                        @click="handleDismiss"
                    >
                        Later
                    </k-button>
                </div>
            </div>
        </kDialog>
    </Teleport>
</template>
