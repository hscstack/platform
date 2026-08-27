import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
    interface Window {
        Pusher: typeof Pusher;
        Echo: Echo<'pusher'>;
    }
}

window.Pusher = Pusher;

let echoInstance: Echo<'pusher'> | null = null;

export function getEcho(key?: string, cluster?: string): Echo<'pusher'> | null {
    if (echoInstance) {
        return echoInstance;
    }

    const pusherKey = key || import.meta.env.VITE_PUSHER_APP_KEY;
    const pusherCluster =
        cluster || import.meta.env.VITE_PUSHER_APP_CLUSTER || 'ap2';

    if (!pusherKey) {
        return null;
    }

    echoInstance = new Echo({
        broadcaster: 'pusher',
        key: pusherKey,
        cluster: pusherCluster,
        forceTLS: true,
    });

    window.Echo = echoInstance;

    return echoInstance;
}
