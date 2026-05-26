import {defineConfig} from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
    base: '/docs/',
    title: "SimpleStats",
    description: "Revenue attribution analytics tool for Laravel apps",
    head: [
        ['link', { rel: 'icon', href: '/favicon.ico' }],
    ],
    transformPageData(pageData) {
        const canonicalUrl = `https://simplestats.io/docs/${pageData.relativePath}`
            .replace(/index\.md$/, '')
            .replace(/\.md$/, '.html')

        pageData.frontmatter.head ??= []
        pageData.frontmatter.head.push([
            'link',
            { rel: 'canonical', href: canonicalUrl }
        ])
    },
    themeConfig: {
        logo: '../svg/app_logo.svg',
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
                    {text: 'How to track a new visitor', link: '/how-to-track-a-new-visitor'},
                    {text: 'How to track a new user', link: '/how-to-track-a-new-user'},
                    {text: 'How to track a new login', link: '/how-to-track-a-new-login'},
                    {text: 'How to track a new payment', link: '/how-to-track-a-new-payment'},
                    {text: 'How to track a custom event', link: '/how-to-track-a-custom-event'},
                    {text: 'Tracking Codes', link: '/tracking-codes'},
                    {text: 'From Visitor to User to Revenue', link: '/from-visitor-to-user-to-revenue'},
                ]
            },
            {
                text: 'Advanced',
                items: [
                    {text: 'Queue', link: '/queue'},
                    {text: 'Headless, SPA & Stateless', link: '/headless-stateless-spa'},
                    {text: 'How to update a payment', link: '/how-to-update-a-payment'},
                    {text: 'How to import existing data', link: '/how-to-import-existing-data'},
                    {text: 'Plans and Limits', link: '/plans-and-limits'},
                    {text: 'Bot Detection', link: '/bot-detection'},
                    {text: 'Troubleshooting', link: '/troubleshooting'},
                    {text: 'Contribution', link: '/contribution'},
                ]
            },
            {
                text: 'API',
                items: [
                    {text: 'Ingest API', link: '/ingest-api'},
                    {text: 'Query API', link: '/query-api'},
                ]
            },
            {
                text: 'Integrations',
                items: [
                    {text: 'Filament', link: '/filament-plugin'},
                    {text: 'Statamic', link: '/statamic-addon'},
                    {text: 'PHP Client', link: '/php-client'},
                ]
            },
            {
                text: 'Self-Hosted',
                items: [
                    {text: 'Overview', link: '/self-hosted'},
                ]
            },
        ],
    }
})
