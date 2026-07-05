<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Header from "@/Pages/Home/Partials/Header.vue";
import Footer from "@/Components/Footer.vue";
import Gradient from "@/Pages/Home/Partials/Gradient.vue";
import BlogPostCard from "@/Components/Blog/BlogPostCard.vue";

defineProps({
    posts: Object,
});
</script>

<template>
    <Head title="Blog" />

    <Gradient position="fixed" />

    <Header />

    <div class="min-h-screen relative">
        <div class="max-w-7xl mx-auto px-6 py-16">
            <h1 class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-5xl">Blog</h1>
            <p class="mt-4 text-lg text-gray-600 dark:text-dark-400">Insights, tutorials, and updates from the SimpleStats team.</p>

            <div v-if="posts.data.length" class="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                <BlogPostCard v-for="post in posts.data" :key="post.slug" :post="post" />
            </div>

            <div v-else class="mt-12 text-center text-gray-500 dark:text-dark-400">
                No blog posts yet.
            </div>

            <!-- Pagination -->
            <div v-if="posts.links && posts.last_page > 1" class="mt-12 flex justify-center gap-2">
                <Link
                    v-for="link in posts.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    class="px-4 py-2 rounded-lg text-sm transition-colors"
                    :class="{
                        'bg-indigo-600 text-white': link.active,
                        'bg-white dark:bg-dark-800 text-gray-700 dark:text-dark-300 hover:bg-gray-100 dark:hover:bg-dark-700 border border-gray-200 dark:border-dark-700': !link.active && link.url,
                        'text-gray-400 dark:text-dark-600 cursor-default': !link.url,
                    }"
                    v-html="link.label"
                    :preserve-scroll="true"
                />
            </div>
        </div>
    </div>

    <Footer />
</template>
