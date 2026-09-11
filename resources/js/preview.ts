import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import '../css/app.css';
import AppLayout from './layouts/AppLayout.vue';
import NodePage from './pages/Node.vue';

createInertiaApp({
    resolve: () => {
        (NodePage as any).layout = (NodePage as any).layout || AppLayout;

        return NodePage;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
});
