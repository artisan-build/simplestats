import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
let appName = import.meta.env.VITE_APP_NAME;

if (import.meta.env.VITE_APP_ENV !== 'production') {
    appName = import.meta.env.VITE_APP_ENV +' '+import.meta.env.VITE_APP_NAME;
}

createInertiaApp({
    title: (title) => `${appName} - ${title}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        app
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);

        import('floating-vue').then(({ vTooltip }) => {
            app.directive('tooltip', vTooltip);
        });

        if (import.meta.env.VITE_SENTRY_DSN_PUBLIC) {
            import('@sentry/vue').then((Sentry) => {
                Sentry.init({
                    app,
                    dsn: import.meta.env.VITE_SENTRY_DSN_PUBLIC,
                    environment: import.meta.env.VITE_APP_ENV,
                });
            });
        }

        return app;
    },
    progress: {
        color: '#4B5563',
    },
});
