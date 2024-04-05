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
                    {text: 'How to track a new user', link: '/how-to-track-a-new-user'},
                    {text: 'How to track a new login', link: '/how-to-track-a-new-login'},
                    {text: 'How to track a new payment', link: '/how-to-track-a-new-payment'},
                    {text: 'Tracking Codes', link: '/tracking-codes'},
                ]
            },
            {
                text: 'Advanced',
                items: [
                    {text: 'Queue', link: '/queue'},
                    {text: 'Data Import', link: '/data-import'},
                    {text: 'API Documentation', link: '/api-documentation'},
                ]
            },
        ],
    }
})
