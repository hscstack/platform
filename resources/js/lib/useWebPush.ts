import { ref } from 'vue';
import { getCsrfToken } from '@/lib/useCsrf';

export const isPushSupported = ref(false);
export const isPushSubscribed = ref(false);
export const isPushLoading = ref(false);

/**
 * Checks if current browser is a Chromium-based browser (Chrome, Chromium, Brave, Edge, Android Chrome)
 * and supports Service Worker & PushManager.
 * Strictly excludes Firefox, Safari/Apple WebKit, etc.
 */
export function isChromiumBrowser(): boolean {
    if (typeof window === 'undefined' || typeof navigator === 'undefined') {
        return false;
    }

    const ua = navigator.userAgent;
    const isFirefox = ua.includes('Firefox');
    const isSafari =
        /Safari/.test(ua) &&
        !/Chrome|Chromium|Edg|Brave|CriOS|Android/.test(ua);

    if (isFirefox || isSafari) {
        return false;
    }

    if (
        !('serviceWorker' in navigator) ||
        !('PushManager' in window) ||
        !('Notification' in window)
    ) {
        return false;
    }

    return /Chrome|Chromium|Edg|CriOS|Android/.test(ua);
}

function urlBase64ToUint8Array(base64String: string): Uint8Array {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding)
        .replace(/-/g, '+')
        .replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }

    return outputArray;
}

/**
 * Initializes the push status for the current browser/user
 */
export async function initWebPush(): Promise<void> {
    if (!isChromiumBrowser()) {
        isPushSupported.value = false;

        return;
    }

    isPushSupported.value = true;

    try {
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();
        isPushSubscribed.value =
            subscription !== null && Notification.permission === 'granted';
    } catch {
        isPushSubscribed.value = false;
    }
}

/**
 * Subscribes user to Web Push
 */
export async function subscribeToPush(): Promise<boolean> {
    if (!isChromiumBrowser()) {
        return false;
    }

    isPushLoading.value = true;

    try {
        const permission = await Notification.requestPermission();

        if (permission !== 'granted') {
            isPushSubscribed.value = false;
            isPushLoading.value = false;

            return false;
        }

        // Use the unified PWA service worker which imports /push-sw.js
        const registration = await navigator.serviceWorker.ready;

        // Fetch public VAPID key
        const keyResponse = await fetch('/api/webpush/key', {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!keyResponse.ok) {
            throw new Error('Failed to fetch VAPID public key');
        }

        const { publicKey } = await keyResponse.json();

        if (!publicKey) {
            throw new Error('VAPID key is empty');
        }

        let subscription = await registration.pushManager.getSubscription();

        if (!subscription) {
            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(publicKey),
            });
        }

        const subJson = subscription.toJSON();

        // Send to backend
        const response = await fetch('/api/webpush/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({
                endpoint: subJson.endpoint,
                keys: subJson.keys,
            }),
        });

        if (response.ok) {
            isPushSubscribed.value = true;
            isPushLoading.value = false;

            return true;
        } else {
            isPushLoading.value = false;

            return false;
        }
    } catch (err) {
        console.error('Web Push subscription error:', err);
        isPushLoading.value = false;

        return false;
    }
}

/**
 * Unsubscribes from Web Push
 */
export async function unsubscribeFromPush(): Promise<boolean> {
    if (!isChromiumBrowser()) {
        return false;
    }

    isPushLoading.value = true;

    try {
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();

        if (subscription) {
            const endpoint = subscription.endpoint;
            await subscription.unsubscribe();

            await fetch('/api/webpush/unsubscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({ endpoint }),
            });
        }

        isPushSubscribed.value = false;
        isPushLoading.value = false;

        return true;
    } catch (err) {
        console.error('Web Push unsubscription error:', err);
        isPushLoading.value = false;

        return false;
    }
}
