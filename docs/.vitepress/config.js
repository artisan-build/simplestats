import {defineConfig} from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
    base: '/docs/',
    title: "SimpleStats",
    description: "The missing statistics tool for your Laravel app",
    head: [
        ['link', { rel: 'icon', href: '../favicon.ico' }]
    ],
    themeConfig: {
        logo: '../svg/logo.svg',
        siteTitle: false,
        search: {
            provider: 'local'
        },
        outline: 'deep',
        sidebar: [
            {
                text: 'Getting Started',
                items: [
                    {text: 'Introduction', link: '/introduction'},
                    {text: 'Installation', link: '/installation'},
                ]
            },
            {
                text: 'Tracking',
                items: [
                    {text: 'Track Users', link: '/track-users'},
                    {text: 'Track Logins', link: '/track-logins'},
                    {text: 'Track Payments', link: '/track-payments'},
                    {text: 'API Communication', link: '/api-communication'},
                ]
            },
            {
                text: 'Advanced',
                items: [
                    {text: 'Data Import', link: '/data-import'},
                    {text: 'API Documentation', link: '/api-documentation'},
                ]
            },
        ],
    }
})
