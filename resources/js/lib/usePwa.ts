import { ref, shallowRef } from 'vue';

const deferredPrompt = shallowRef<any>(null);
const isInstalled = ref(false);

let isInitialized = false;

export function initPwa() {
    if (typeof window === 'undefined' || isInitialized) return;
    isInitialized = true;

    const checkStandalone = () => {
        const isStandalone =
            window.matchMedia('(display-mode: standalone)').matches ||
            (window.navigator as Record<string, unknown>).standalone === true;
        isInstalled.value = isStandalone;
    };

    checkStandalone();

    window.addEventListener('beforeinstallprompt', (e: Event) => {
        e.preventDefault();
        deferredPrompt.value = e;
    });

    window.addEventListener('appinstalled', () => {
        isInstalled.value = true;
        deferredPrompt.value = null;
    });
}

export function usePwa() {
    if (!isInitialized) {
        initPwa();
    }

    const promptInstall = async (): Promise<boolean> => {
        if (!deferredPrompt.value) return false;
        try {
            await deferredPrompt.value.prompt();
            const { outcome } = await deferredPrompt.value.userChoice;
            if (outcome === 'accepted') {
                isInstalled.value = true;
                deferredPrompt.value = null;
                return true;
            }
        } catch (err) {
            console.error('PWA install error:', err);
        }
        return false;
    };

    return {
        deferredPrompt,
        isInstalled,
        promptInstall,
    };
}
