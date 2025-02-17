<script setup>
import {Link} from "@inertiajs/vue3";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";

let appName = import.meta.env.VITE_APP_NAME;

const linkCategories = [
    {
        "header": `Why ${appName}?`,
        "links": [
            {"title": "Powerful KPIs", "url": "/docs/introduction.html#kpis"},
            {"title": "Simple Setup", "url": "/docs/installation.html"},
            {"title": "Server-side Tracking", "url": "/docs/introduction.html#server-side-vs-client-side-tracking"},
            {"title": "Data Import", "url": "/docs/how-to-import-existing-data.html"},
            {"title": "Live Demo", "url": "https://demo.simplestats.io/login"},
            {"title": "API", "url": "/docs/api-documentation.html"},
        ],
    },
    {
        "header": `Community`,
        "links": [
            {"title": "Twitter", "url": "https://x.com/SimpleStats_io"},
            {"title": "GitHub", "url": "https://github.com/simplestats-io"},
            {"title": "Laravel News", "url": "https://laravel-news.com/partner/simplestats"},
            {"title": "Product Hunt", "url": "https://www.producthunt.com/products/simplestats?utm_source=badge-featured&utm_medium=badge#simplestats"},
            {"title": "Reddit", "url": "https://www.reddit.com/r/laravel/comments/1im7n44/release_simplestats_40_a_lightweight_analytics/"},
            {"title": "Blog", "url": "https://www.laracraft.tech/blog/why-we-build-simplestats-the-missing-statistics-tool-for-your-laravel-application"},
        ],
    },
    {
        "header": `Company`,
        "links": [
            {"title": "Status", "url": "https://status.simplestats.io"},
            {"title": "Help & Support", "url": "https://tawk.to/chat/6601f04ea0c6737bd124880f/1hprp5d8g"},
            {"title": "Documentation", "url": "/docs"},
            {"title": "Imprint", "url": "/imprint"},
            {"title": "Privacy", "url": "/privacy-policy"},
            {"title": "Terms", "url": "/terms-of-service"},
        ],
    },
];
</script>

<template>
    <section class="pb-20 flex justify-center px-4 bg-gray-100 dark:bg-gray-700 text-gray-60 border-t border-gray-200 dark:border-gray-500">
        <div class="hidden grid-cols-1 grid-cols-2 grid-cols-3 grid-cols-4"></div>
        <div class="w-full max-w-screen-xl">
            <div class="container py-12 px-4 sm:px-6 lg:py-16 lg:px-8">
                <div class="xl:grid xl:grid-cols-[1fr_2fr] xl:gap-8">
                    <div class="my-8 xl:my-0">
                        <Link href="/">
                            <ApplicationLogo />
                        </Link>
                        <div class="mt-4 text-base leading-6">
                            Based and hosted in the EU <span class="text-lg">🇪🇺</span>
                        </div>
                        <div class="mt-4">
                            <img class="rounded-xl shadow-md border border-gray-300" src="/images/gdpr.png" loading="lazy" alt="GDPR compliant" width="192" height="66">
                        </div>
                    </div>
                    <div class="grid gap-8 grid-cols-1" :class="`md:grid-cols-${linkCategories.length}`">
                        <div v-for="category in linkCategories">
                            <div class="leading-5 font-semibold tracking-wider uppercase">
                                {{ category.header }}
                            </div>
                            <ul class="mt-4">
                                <li class="mt-4" v-for="link in category.links">
                                    <a :href="link.url" target="_blank" rel="noreferrer" class="text-sm leading-6 hover:underline">{{ link.title }}</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
