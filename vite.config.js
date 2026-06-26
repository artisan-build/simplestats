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
});
