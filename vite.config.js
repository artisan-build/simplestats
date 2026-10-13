import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import svgLoader from 'vite-svg-loader';
import shikiTransformPlugin from './vite/shikiTransformPlugin.js';

export default defineConfig({
    plugins: [
        shikiTransformPlugin(),
        laravel({
            input: 'resources/js/app.js',
            ssr: 'resources/js/ssr.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        svgLoader({
            defaultImport: 'url',
            svgoConfig: {
                plugins: [
                    {
                        name: 'preset-default',
                        params: { overrides: { removeViewBox: false } },
                    },
                ],
            },
        }),
    ],
    ssr: {
        noExternal: ["country-flag-emoji"],
    },
    server: {
        // The built-in browser pane only loads subresources from the page's own origin,
        // so the app is opened via the Vite port and every non-Vite path goes to Herd.
        proxy: {
            '^/(?!@vite|@id|@fs|@react-refresh|resources/|node_modules/|__vite).*': {
                target: 'https://simplestats.test',
                changeOrigin: true,
                secure: false,
            },
        },
    },
});
