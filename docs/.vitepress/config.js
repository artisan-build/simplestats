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
        logo: '../svg/logo_only.svg',
        logoLink: { link: '/', target: '_self' },
        search: {
            provider: 'local'
        },
        outline: 'deep',
        nav: [
            { text: 'Help', link: 'https://tawk.to/chat/6601f04ea0c6737bd124880f/1hprp5d8g' },
            { text: 'Demo', link: 'https://demo.simplestats.io' },
        ],
        socialLinks: [
            { icon: 'github', link: 'https://github.com/simplestats-io/laravel-client' },
            { icon: 'twitter', link: 'https://twitter.com/SimpleStats_io' },
        ],
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
                    {text: 'API Documentation', link: '/api-documentation'},
                    {text: 'How to update a payment', link: '/how-to-update-a-payment'},
                    {text: 'How to import existing data', link: '/how-to-import-existing-data'},
                    {text: 'Contribution', link: '/contribution'},
                ]
            },
        ],
    }
})
