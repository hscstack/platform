import { createInertiaApp, router } from '@inertiajs/vue3';
import { registerSW } from 'virtual:pwa-register';
import AdminLayout from './layouts/AdminLayout.vue';
import AppLayout from './layouts/AppLayout.vue';
import { initPwa } from './lib/usePwa';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

if (import.meta.env.PROD) {
    registerSW({ immediate: true });
}
initPwa();

router.on('navigate', () => {
    if (typeof window !== 'undefined' && (window as any).posthog) {
        (window as any).posthog.capture('$pageview');
    }
});

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name.startsWith('admin/'):
                return AdminLayout;
            case name.startsWith('errors/503'):
                return null;
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4b3aef',
    },
});
