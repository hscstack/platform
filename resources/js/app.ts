import { createInertiaApp } from '@inertiajs/vue3';
import AppLayout from './layouts/AppLayout.vue';
import AdminLayout from './layouts/AdminLayout.vue';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name)=>{
        switch (true) {
            case name.startsWith('admin/'):
                return AdminLayout;
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
});
