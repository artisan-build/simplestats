<script setup>
import SectionHeader from "@/Pages/Home/Partials/SectionHeader.vue";

const brands = [
    {
        'name': 'Product Hunt',
        'url': 'https://www.producthunt.com/posts/simplestats?embed=true&utm_source=badge-featured&utm_medium=badge&utm_souce=badge-simplestats',
        'img': 'https://api.producthunt.com/widgets/embed-image/v1/featured.svg?post_id=472898&theme=light',
        'alt': 'SimpleStats - The missing statistics tool for Laravel applications | Product Hunt',
    },
    {
        'name': 'Laravel News',
        'url': 'https://laravel-news.com/partner/simplestats',
        'img': '/images/brands/laravel-news.png',
        'alt': 'SimpleStats - The missing analytics tool for Laravel applications | Laravel News',
    },
    {
        'name': 'Reddit',
        'url': 'https://www.reddit.com/r/laravel/comments/1cbryw7/simplestats_a_lightweight_statistics_tool_for/',
        'img': '/images/brands/reddit.png',
        'alt': 'SimpleStats - a lightweight statistics tool for Laravel applications | Reddit',
    },
];
</script>

<template>
    <section class="mt-4 flex justify-center px-4">
        <div class="w-full max-w-screen-xl">
<!--            <SectionHeader id="partners">-->
<!--                Community&nbsp;<span class="text-indigo-500">and</span>&nbsp;Partners-->
<!--            </SectionHeader>-->
            <div class="flex flex-wrap items-center justify-center">
                <div v-for="brand in brands" class="p-3">
                    <a :href="brand.url" rel="noreferrer" target="_blank">
                        <img :src="brand.img" :alt="brand.alt" style="width: 250px; height: 54px;" width="250" height="54" />
                    </a>
                </div>
            </div>
        </div>
    </section>
</template>
