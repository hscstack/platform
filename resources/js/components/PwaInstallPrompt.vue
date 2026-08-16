<script setup lang="ts">
import { kDialog, kButton } from 'konsta/vue';
import { ref, shallowRef, onMounted, onUnmounted, computed } from 'vue';

const isVisible = ref(false);
const deferredPrompt = shallowRef(null);

type InstallMethod =
    | 'native'
    | 'safari-ios'
    | 'safari-mac'
    | 'firefox'
    | 'manual';
const installMethod = ref<InstallMethod>('manual');

const isIOS = computed(() => /iPhone|iPad|iPod/.test(navigator.userAgent));
const isSafari = computed(() =>
    /^((?!chrome|android).)*safari/i.test(navigator.userAgent),
);
const isFirefox = computed(() => /Firefox/i.test(navigator.userAgent));
const isMac = computed(() => /Macintosh|Mac OS X/.test(navigator.userAgent));

const handleBeforeInstall = (e: Event) => {
    e.preventDefault();
    deferredPrompt.value = e;

    if (!sessionStorage.getItem('pwa_prompt_dismissed')) {
        installMethod.value = 'native';
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

    setTimeout(() => {
        if (deferredPrompt.value) {
return;
}

        if (!sessionStorage.getItem('pwa_prompt_dismissed')) {
            if (isSafari.value && isIOS.value) {
                installMethod.value = 'safari-ios';
                isVisible.value = true;
            } else if (isSafari.value && isMac.value) {
                installMethod.value = 'safari-mac';
                isVisible.value = true;
            } else if (isFirefox.value) {
                installMethod.value = 'firefox';
                isVisible.value = true;
            }
        }
    }, 3000);
});

onUnmounted(() => {
    window.removeEventListener('beforeinstallprompt', handleBeforeInstall);
    window.removeEventListener('appinstalled', handleAppInstalled);
});

const handleInstall = async () => {
    if (installMethod.value === 'native' && deferredPrompt.value) {
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
    } else {
        isVisible.value = false;
    }
};

const handleDismiss = () => {
    isVisible.value = false;
    sessionStorage.setItem('pwa_prompt_dismissed', 'true');
};
</script>

<template>
    <Teleport v-if="isVisible" to="body">
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

                <div
                    v-if="installMethod === 'safari-ios'"
                    class="mt-4 w-full rounded-xl bg-slate-50 p-4 text-left dark:bg-slate-800"
                >
                    <p
                        class="mb-2 text-sm font-medium text-slate-700 dark:text-slate-200"
                    >
                        On iPhone or iPad:
                    </p>
                    <ol
                        class="list-decimal space-y-1 pl-4 text-xs text-slate-500 dark:text-slate-400"
                    >
                        <li>Tap the <strong>Share</strong> button in Safari</li>
                        <li>
                            Scroll down and tap
                            <strong>Add to Home Screen</strong>
                        </li>
                        <li>Tap <strong>Add</strong> to confirm</li>
                    </ol>
                </div>

                <div
                    v-else-if="installMethod === 'safari-mac'"
                    class="mt-4 w-full rounded-xl bg-slate-50 p-4 text-left dark:bg-slate-800"
                >
                    <p
                        class="mb-2 text-sm font-medium text-slate-700 dark:text-slate-200"
                    >
                        On Mac:
                    </p>
                    <ol
                        class="list-decimal space-y-1 pl-4 text-xs text-slate-500 dark:text-slate-400"
                    >
                        <li>Click <strong>File</strong> in the menu bar</li>
                        <li>Click <strong>Add to Dock</strong></li>
                        <li>Click <strong>Add</strong></li>
                    </ol>
                </div>

                <div
                    v-else-if="installMethod === 'firefox'"
                    class="mt-4 w-full rounded-xl bg-slate-50 p-4 text-left dark:bg-slate-800"
                >
                    <p
                        class="mb-2 text-sm font-medium text-slate-700 dark:text-slate-200"
                    >
                        In Firefox:
                    </p>
                    <ol
                        class="list-decimal space-y-1 pl-4 text-xs text-slate-500 dark:text-slate-400"
                    >
                        <li>
                            Click the <strong>+</strong> icon in the address bar
                        </li>
                        <li>Or go to <strong>Menu > Install</strong></li>
                    </ol>
                </div>

                <div
                    v-else-if="installMethod === 'manual'"
                    class="mt-4 w-full rounded-xl bg-slate-50 p-4 text-left dark:bg-slate-800"
                >
                    <p
                        class="mb-2 text-sm font-medium text-slate-700 dark:text-slate-200"
                    >
                        To install:
                    </p>
                    <ol
                        class="list-decimal space-y-1 pl-4 text-xs text-slate-500 dark:text-slate-400"
                    >
                        <li>
                            Open this site in <strong>Chrome</strong> or
                            <strong>Edge</strong>
                        </li>
                        <li>Click the install icon in the address bar</li>
                        <li>Or use <strong>Menu > Install App</strong></li>
                    </ol>
                </div>

                <div class="mt-6 flex w-full gap-3">
                    <k-button
                        v-if="installMethod === 'native'"
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
                        :fill="installMethod !== 'native'"
                        :clear="installMethod === 'native'"
                        class="flex-1"
                        @click="handleDismiss"
                    >
                        {{ installMethod === 'native' ? 'Later' : 'Got it' }}
                    </k-button>
                </div>
            </div>
        </kDialog>
    </Teleport>
</template>
