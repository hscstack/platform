import { createInertiaApp } from '@inertiajs/vue3';
import AdminLayout from './layouts/AdminLayout.vue';
import AppLayout from './layouts/AppLayout.vue';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

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
        color: '#4B5563',
    },
});
