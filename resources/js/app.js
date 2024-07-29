import './bootstrap';
import '../css/app.css';
import 'floating-vue/dist/style.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/vue.m';
import {vTooltip} from 'floating-vue'
import * as Sentry from "@sentry/vue";

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${appName} - ${title}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        Sentry.init({
            app,
            dsn: import.meta.env.VITE_SENTRY_DSN_PUBLIC,
            environment: import.meta.env.APP_ENV,
        });

        app
            .use(plugin)
            .use(ZiggyVue)
            .directive('tooltip', vTooltip)
            .mount(el)

        return app;
    },
    progress: {
        color: '#4B5563',
    },
});
