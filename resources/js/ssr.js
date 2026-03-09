import '../css/app.css';

import { createInertiaApp } from "@inertiajs/vue3";
import { renderToString } from "@vue/server-renderer";
import createServer from "@inertiajs/vue3/server";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { ZiggyVue } from "ziggy-js";
import { createSSRApp, h } from "vue";

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

            throw new Error(`SSR is disabled for this page: ${name}.`);
        },
        setup({ App, props, plugin }) {
            return createSSRApp({ render: () => h(App, props) })
                .use(plugin)
                .use(ZiggyVue, props.initialPage.props.ziggy);
        },
    })
);
