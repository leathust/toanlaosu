import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h, type DefineComponent } from 'vue';
import './bootstrap';

const pages = import.meta.glob('./Pages/**/*.vue') as Record<string, () => Promise<{ default: DefineComponent }>>;

createInertiaApp({
    resolve: async (name) => (await pages[`./Pages/${name}.vue`]()).default,
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#0f766e',
    },
});
