import { createInertiaApp } from '@inertiajs/vue3';
import { registerSW } from 'virtual:pwa-register';
import AdminLayout from './layouts/AdminLayout.vue';
import AppLayout from './layouts/AppLayout.vue';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Apply dark mode before paint to prevent flash
const stored = localStorage.getItem('theme');

if (
    stored === 'dark' ||
    (stored !== 'light' &&
        window.matchMedia('(prefers-color-scheme: dark)').matches)
) {
    document.documentElement.classList.add('dark');
}

registerSW({ immediate: true });

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
