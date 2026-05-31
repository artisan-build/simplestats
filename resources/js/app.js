import './bootstrap';
import '../css/app.css';

import { createSSRApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import { vTooltip } from 'floating-vue';
import { i18nVue, I18n } from 'laravel-vue-i18n';
let appName = import.meta.env.VITE_APP_NAME;

if (import.meta.env.VITE_APP_ENV !== 'production') {
    appName = import.meta.env.VITE_APP_ENV +' '+import.meta.env.VITE_APP_NAME;
}

createInertiaApp({
    title: (title) => `${appName} - ${title}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createSSRApp({ render: () => h(App, props) });

        app
            .use(plugin)
            .use(ZiggyVue)
            .use(i18nVue, {
                resolve: async (lang) => {
                    const langs = import.meta.glob('../../lang/*.json');
                    return await langs[`../../lang/${lang}.json`]();
                },
                lang: props.initialPage.props.locale || 'en',
                fallbackLang: 'en',
            })
            .directive('tooltip', vTooltip);

        const translations = props.initialPage.props.translations;
        if (translations) {
            const locale = props.initialPage.props.locale || 'en';
            const i18n = I18n.getSharedInstance();
            I18n.loaded.push({ lang: locale, messages: translations });
            for (const [key, value] of Object.entries(translations)) {
                i18n.activeMessages[key] = value;
            }
        }

        app.mount(el);

        if (import.meta.env.VITE_SENTRY_DSN_PUBLIC) {
            import('@sentry/vue').then((Sentry) => {
                Sentry.init({
                    app,
                    dsn: import.meta.env.VITE_SENTRY_DSN_PUBLIC,
                    environment: import.meta.env.VITE_APP_ENV,
                    // Drop noise from the third-party Tawk.to chat widget. These errors
                    // originate in its bundled scripts (twk-chunk-*), not in our code, and
                    // can never be fixed on our side, so they should not consume quota.
                    ignoreErrors: [
                        'BufferLoader: XHR error for undefined',
                        /\$_Tawk/i,
                        'i18next is not a function',
                    ],
                    denyUrls: [/tawk\.to/i, /twk-chunk/i],
                });
            });
        }

        return app;
    },
    progress: {
        color: '#4B5563',
    },
});
