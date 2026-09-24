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
    /**
     * Loads a page by its path relative to pages/, preferring .vue over .tsx.
     * Resolves to the default export, or the module if that export is nullish.
     * Throws if neither entry exists; import failures reject the promise.
     */
    resolve: (name) => {
        const pages = import.meta.glob('./pages/**/*.{vue,tsx}') as Record<
            string,
            () => Promise<{ default: unknown }>
        >;
        const loader =
            pages[`./pages/${name}.vue`] ?? pages[`./pages/${name}.tsx`];

        if (!loader) {
            throw new Error(`Page not found: ${name}`);
        }

        return loader().then((module) => module.default ?? module);
    },
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
