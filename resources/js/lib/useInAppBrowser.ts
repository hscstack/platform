import { onMounted, ref } from 'vue';

export function useInAppBrowser() {
    const isInAppBrowser = ref(false);
    const isFacebook = ref(false);
    const isAndroid = ref(false);
    const isIOS = ref(false);
    const isVisible = ref(false);
    const copied = ref(false);

    const checkBrowser = () => {
        if (typeof window === 'undefined' || typeof navigator === 'undefined') {
            return;
        }

        const ua =
            navigator.userAgent ||
            navigator.vendor ||
            (window as unknown as { opera?: string }).opera ||
            '';

        // Detect Facebook, Messenger, Instagram, or general Meta in-app browsers
        const fbMatch = /FBAN|FBAV|FB_IAB|FB4A|FBIOS/i.test(ua);
        const instaMatch = /Instagram/i.test(ua);
        const generalIab = fbMatch || instaMatch;

        const android = /android/i.test(ua);
        const ios = /iPhone|iPad|iPod/i.test(ua);

        isFacebook.value = fbMatch;
        isInAppBrowser.value = generalIab;
        isAndroid.value = android;
        isIOS.value = ios;

        if (generalIab && (android || ios)) {
            try {
                const dismissed = sessionStorage.getItem(
                    'iab_prompt_dismissed',
                );

                if (!dismissed) {
                    isVisible.value = true;
                }
            } catch {
                isVisible.value = true;
            }
        }
    };

    const getAndroidIntentUrl = (): string => {
        if (typeof window === 'undefined') {
            return '';
        }

        const cleanUrl = window.location.href
            .replace(/^https?:\/\//i, '')
            .replace('#', '%23');

        return `intent://${cleanUrl}#Intent;scheme=https;action=android.intent.action.VIEW;end;`;
    };

    const openInExternalBrowser = () => {
        if (typeof window === 'undefined') {
            return;
        }

        if (isAndroid.value) {
            window.location.href = getAndroidIntentUrl();
        }
    };

    const copyUrl = async (): Promise<boolean> => {
        if (typeof window === 'undefined') {
            return false;
        }

        try {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(window.location.href);
            } else {
                const input = document.createElement('input');
                input.value = window.location.href;
                input.style.position = 'fixed';
                input.style.opacity = '0';
                document.body.appendChild(input);
                input.focus();
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
            }

            copied.value = true;
            setTimeout(() => {
                copied.value = false;
            }, 2500);

            return true;
        } catch {
            return false;
        }
    };

    const dismiss = () => {
        isVisible.value = false;

        try {
            sessionStorage.setItem('iab_prompt_dismissed', '1');
        } catch {}
    };

    onMounted(() => {
        checkBrowser();
    });

    return {
        isInAppBrowser,
        isFacebook,
        isAndroid,
        isIOS,
        isVisible,
        copied,
        openInExternalBrowser,
        copyUrl,
        dismiss,
    };
}
