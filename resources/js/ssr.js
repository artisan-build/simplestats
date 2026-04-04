import '../css/app.css';

import { createInertiaApp } from "@inertiajs/vue3";
import { renderToString } from "@vue/server-renderer";
import createServer from "@inertiajs/vue3/server";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { ZiggyVue } from "ziggy-js";
import { createSSRApp, h } from "vue";
import { i18nVue } from "laravel-vue-i18n";

createServer((page) =>
    createInertiaApp({
        page,
        render: renderToString,
        resolve: (name) => {
            if (name.startsWith("Home") || name.startsWith("Auth")) {
                return resolvePageComponent(
                    `./Pages/${name}.vue`,
                    import.meta.glob("./Pages/**/*.vue")
                );
            }

            // Fallback: return minimal component, client-side rendering takes over
            return { default: { render: () => '' } };
        },
        setup({ App, props, plugin }) {
            return createSSRApp({ render: () => h(App, props) })
                .use(plugin)
                .use(ZiggyVue, props.initialPage.props.ziggy)
                .use(i18nVue, {
                    shared: false,
                    resolve: (lang) => {
                        const langs = import.meta.glob("../../lang/*.json", { eager: true });
                        return langs[`../../lang/${lang}.json`].default;
                    },
                    lang: props.initialPage.props.locale || "en",
                    fallbackLang: "en",
                });
        },
    })
);
