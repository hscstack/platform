<script setup>
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

const handleBeforeInstall = (e) => {
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
        window.navigator.standalone === true;

    if (isStandalone) return;

    window.addEventListener('beforeinstallprompt', handleBeforeInstall);
    window.addEventListener('appinstalled', handleAppInstalled);
});

onUnmounted(() => {
    window.removeEventListener('beforeinstallprompt', handleBeforeInstall);
    window.removeEventListener('appinstalled', handleAppInstalled);
});

const handleInstall = async () => {
    if (!deferredPrompt.value) return;

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
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm">
                <div class="w-full max-w-sm rounded-2xl border border-slate-100 bg-white p-6 shadow-xl">
                    <div class="mb-4 flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-xl text-indigo-600">
                            📱
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Install Mobile App</h3>
                            <p class="text-xs text-slate-500">Get fast, offline access right from your home screen.</p>
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
                            class="rounded-lg border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"
                        >
                            Maybe Later
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <div
            v-else
            v-bind="$attrs"
            class="flex flex-col gap-3 rounded-2xl border border-indigo-100 bg-gradient-to-r from-indigo-50/80 to-slate-50/80 p-4 shadow-sm backdrop-blur-sm sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-600/10 text-xl text-indigo-600">
                    📱
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Install Mobile App</h3>
                    <p class="text-xs text-slate-500">Fast, offline access right from your home screen.</p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 shrink-0">
                <button
                    @click="handleInstall"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow transition hover:bg-indigo-700 active:scale-95"
                >
                    Install App
                </button>
                <button
                    @click="handleDismiss"
                    class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-200/50 hover:text-slate-600"
                    title="Dismiss"
                >
                    ✕
                </button>
            </div>
        </div>
    </template>
</template>
