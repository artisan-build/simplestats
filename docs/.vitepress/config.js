import {defineConfig} from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
    base: '/docs/',
    title: "SimpleStats",
    description: "The missing statistic tool for your Laravel app",
    head: [
        ['link', { rel: 'icon', href: '../favicon.ico' }]
    ],
    themeConfig: {
        logo: '../svg/logo.svg',
        siteTitle: false,
        search: {
            provider: 'local'
        },
        sidebar: [
            {
                text: 'Examples',
                items: [
                    {text: 'Introduction', link: '/introduction'},
                    {text: 'Data Import', link: '/data-import'},
                ]
            }
        ],
    }
})
